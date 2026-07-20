<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use App\Services\GeminiService;
use App\Services\FinanceService;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\TransferRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\DebtRepository;
use App\Repositories\SavingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsAppService       $whatsapp,
        private GeminiService         $gemini,
        private FinanceService        $finance,
        private TransactionRepository $txRepo,
        private WalletRepository      $walletRepo,
        private SettingsRepository    $settingsRepo,
        private ReminderRepository    $reminderRepo,
        private TransferRepository    $transferRepo,
        private SubscriptionRepository $subRepo,
        private DebtRepository        $debtRepo,
        private SavingsRepository     $savingsRepo
    ) {}

    // -------------------------------------------------------------------------
    // WEBHOOK VERIFICATION (GET) — handshake awal dari Meta
    // -------------------------------------------------------------------------

    public function verify(Request $request): \Illuminate\Http\Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expected = (string) config('services.whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $token && hash_equals($expected, (string) $token)) {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // -------------------------------------------------------------------------
    // WEBHOOK HANDLER (POST) — pesan masuk
    // -------------------------------------------------------------------------

    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!$this->verifySignature($request)) {
            return response()->json(['ok' => false], 403);
        }

        try {
            $payload = $request->all();
            $this->process($payload);
        } catch (\Throwable $e) {
            Log::error('WhatsAppWebhook::handle exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');
        if (empty($appSecret)) {
            // Belum ada app secret yang diset — lewati verifikasi signature,
            // tetap dijaga oleh allowlist nomor pengirim di process().
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (!str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
        return hash_equals($expected, $header);
    }

    public function process(array $payload): void
    {
        $value = $payload['entry'][0]['changes'][0]['value'] ?? null;
        if (!$value) {
            return;
        }

        $messages = $value['messages'] ?? null;
        if (!$messages) {
            // Bukan pesan masuk (misal status delivered/read), abaikan.
            return;
        }

        $waMessage = $messages[0];
        $fromPhone = (string) ($waMessage['from'] ?? '');

        if (!$fromPhone || !$this->isAuthorizedSender($fromPhone)) {
            Log::warning('WhatsAppWebhook: pesan dari nomor tidak dikenal diabaikan', ['from' => $fromPhone]);
            return;
        }

        $chatId = (string) config('services.telegram.owner_chat_id');
        if (!$chatId) {
            Log::error('WhatsAppWebhook: OWNER_CHAT_ID belum diset, pesan WhatsApp tidak bisa diproses.');
            return;
        }

        // Auto-create dompet Cash untuk user baru
        $this->walletRepo->ensureDefaultWallet($chatId);

        $type = $waMessage['type'] ?? 'text';
        if ($type !== 'text') {
            $this->whatsapp->sendMessage(
                $chatId,
                '📎 Foto/voice note belum didukung lewat WhatsApp. Ketik keterangan transaksinya sebagai teks ya!'
            );
            return;
        }

        $text = trim($waMessage['text']['body'] ?? '');
        if ($text === '') {
            return;
        }

        $this->handleText($chatId, $text);
    }

    private function isAuthorizedSender(string $fromPhone): bool
    {
        $allowed = config('services.whatsapp.recipient_phone');
        if (empty($allowed)) {
            return false;
        }

        return $this->normalizePhone($fromPhone) === $this->normalizePhone((string) $allowed);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    // -------------------------------------------------------------------------
    // TEXT
    // -------------------------------------------------------------------------

    public function handleText(string $chatId, string $text): void
    {
        // Cek pending confirmation (struk confidence < 0.60)
        // Cek pending konfirmasi duplikat langganan
        $pendingSubKey = "pending_sub_confirm:{$chatId}";
        if (Cache::has($pendingSubKey)) {
            $upper   = strtoupper(trim($text));
            $pending = Cache::get($pendingSubKey);
            Cache::forget($pendingSubKey);
            if (in_array($upper, ['BARU', 'YA', 'IYA', 'YES', 'OK', 'OKE', 'TAMBAH'])) {
                $this->confirmNewSubscription($chatId, $pending, forceNew: true);
            } else {
                // Cukup catat transaksi, tidak buat subscription baru
                $this->txRepo->create([
                    'chat_id'   => $chatId,
                    'type'      => 'expense',
                    'wallet_id' => $pending['wallet_id'],
                    'date'      => today_wita(),
                    'total'     => $pending['amount'],
                    'category'  => $pending['category'],
                    'note'      => "Langganan: {$pending['name']}",
                ]);
                $this->whatsapp->sendMessage($chatId, "✅ Transaksi dicatat tanpa menambah langganan baru.\nNominal: " . rp($pending['amount']));
            }
            return;
        }

        // Cek pending confirmation (struk confidence < 0.60)
        $pendingKey = "pending_confirm:{$chatId}";
        if (Cache::has($pendingKey)) {
            $upper = strtoupper(trim($text));
            if (in_array($upper, ['YA', 'IYA', 'YES', 'OK', 'OKE'])) {
                $this->confirmPendingReceipt($chatId, $pendingKey);
                return;
            }
            if (in_array($upper, ['TIDAK', 'BATAL', 'NO', 'CANCEL'])) {
                Cache::forget($pendingKey);
                $this->whatsapp->sendMessage($chatId, '❌ Dibatalkan.');
                return;
            }
            Cache::forget($pendingKey);
        }

        // Perintah khusus /start /help
        if (in_array($text, ['/start', '/help'])) {
            $this->sendHelp($chatId);
            return;
        }

        $now    = wita_now()->format('Y-m-d H:i:s');
        $intent = $this->gemini->parseIntent($text, $now);

        if ($intent === null) {
            $this->whatsapp->sendMessage($chatId, '⚠️ Maaf, AI sedang bermasalah. Coba beberapa saat lagi.');
            return;
        }

        $this->dispatchIntent($chatId, $intent, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT DISPATCHER
    // -------------------------------------------------------------------------

    private function dispatchIntent(string $chatId, array $intent, string $rawText): void
    {
        match ($intent['intent'] ?? 'lainnya') {
            'catat'        => $this->intentCatat($chatId, $intent),
            'set_budget'   => $this->intentSetBudget($chatId, $intent),
            'rekap'        => $this->intentRekap($chatId, $intent),
            'sisa'         => $this->intentSisa($chatId, $intent),
            'saldo'        => $this->intentSaldo($chatId, $intent),
            'hapus'        => $this->intentHapus($chatId),
            'undo'         => $this->intentUndo($chatId),
            'edit'         => $this->intentEdit($chatId, $intent),
            'reminder'     => $this->intentReminder($chatId, $intent),
            'reminder_list'=> $this->intentReminderList($chatId),
            'transfer'     => $this->intentTransfer($chatId, $intent),
            'langganan'    => $this->intentLangganan($chatId, $intent),
            'utang'        => $this->intentUtang($chatId, $intent),
            'lunas'        => $this->intentLunas($chatId, $intent),
            'nabung'       => $this->intentNabung($chatId, $intent),
            default        => $this->intentLainnya($chatId),
        };
    }

    // -------------------------------------------------------------------------
    // INTENT: CATAT
    // -------------------------------------------------------------------------

    private function intentCatat(string $chatId, array $intent): void
    {
        $amount = $intent['amount'] ?? null;
        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }

        $type     = $intent['type'] ?? 'expense';
        $category = $intent['category'] ?? 'Lainnya';
        $note     = $intent['note'] ?? 'Tidak terdeteksi';
        $currency = strtoupper($intent['currency'] ?? 'IDR');

        // Konversi mata uang asing ke IDR
        $originalAmount = (float) $amount;
        $convertedNote  = '';
        if ($currency !== 'IDR') {
            $rate = $this->fetchExchangeRate($currency);
            if ($rate) {
                $amountIdr     = (int) round($originalAmount * $rate);
                $convertedNote = " (dari {$currency} " . number_format($originalAmount, 2) . " × " . number_format($rate, 0, ',', '.') . ")";
                $amount        = $amountIdr;
            } else {
                $this->whatsapp->sendMessage($chatId, "⚠️ Gagal ambil kurs {$currency}/IDR. Coba lagi atau input nominal IDR langsung.");
                return;
            }
        }

        // Resolve wallet
        $walletId   = null;
        $walletName = null;
        if (!empty($intent['wallet'])) {
            $wallet = $this->walletRepo->findByName($chatId, $intent['wallet']);
            if (!$wallet) {
                $wallet = $this->walletRepo->findById(
                    $this->walletRepo->create($chatId, $intent['wallet'])
                );
            }
            $walletId   = $wallet->id;
            $walletName = $wallet->name;
        }

        $this->txRepo->create([
            'chat_id'   => $chatId,
            'type'      => $type,
            'wallet_id' => $walletId,
            'date'      => today_wita(),
            'total'     => (int) $amount,
            'category'  => $category,
            'note'      => $note,
        ]);

        $icon    = $type === 'income' ? '💰' : '💸';
        $typeStr = $type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $reply   = "{$icon} <b>{$typeStr} dicatat!</b>\n"
            . "Nominal: " . rp((int) $amount) . $convertedNote . "\n"
            . "Kategori: {$category}\n"
            . "Catatan: {$note}";

        if ($walletName) {
            $saldo  = $this->walletRepo->balance((int) $walletId);
            $reply .= "\n💼 Saldo {$walletName}: " . rp($saldo);
        }

        if ($type === 'expense') {
            $budgetStatus = $this->finance->buildBudgetStatus($chatId);
            if ($budgetStatus) {
                $reply .= "\n\n{$budgetStatus}";
            }
        }

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    private function fetchExchangeRate(string $currency): ?float
    {
        try {
            $url      = "https://api.frankfurter.app/latest?from={$currency}&to=IDR";
            $response = file_get_contents($url);
            if (!$response) return null;
            $data = json_decode($response, true);
            return $data['rates']['IDR'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // INTENT: SET BUDGET
    // -------------------------------------------------------------------------

    private function intentSetBudget(string $chatId, array $intent): void
    {
        $amount = $intent['amount'] ?? null;
        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }

        $period = $intent['period'] ?? 'mingguan';
        $this->settingsRepo->setBudget($chatId, $period, (int) $amount);
        $this->whatsapp->sendMessage($chatId, "✅ Budget <b>{$period}</b> diset ke " . rp((int) $amount));
    }

    // -------------------------------------------------------------------------
    // INTENT: REKAP
    // -------------------------------------------------------------------------

    private function intentRekap(string $chatId, array $intent): void
    {
        $days = isset($intent['days']) ? (int) $intent['days'] : null;

        if ($days && $days > 0) {
            $text = $this->finance->buildRecapCustomDays($chatId, $days);
        } else {
            $text = $this->finance->buildRecap($chatId, $intent['period'] ?? 'mingguan');
        }

        $this->whatsapp->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: SISA
    // -------------------------------------------------------------------------

    private function intentSisa(string $chatId, array $intent): void
    {
        $period = $intent['period'] ?? 'mingguan';
        $text   = $this->finance->buildSisaText($chatId, $period);
        $this->whatsapp->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: SALDO
    // -------------------------------------------------------------------------

    private function intentSaldo(string $chatId, array $intent): void
    {
        $walletName = $intent['wallet'] ?? null;
        $text       = $this->finance->buildSaldoText($chatId, $walletName);
        $this->whatsapp->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: HAPUS (hapus transaksi terakhir — permanen)
    // -------------------------------------------------------------------------

    private function intentHapus(string $chatId): void
    {
        $tx = $this->txRepo->lastTransaction($chatId);
        if (!$tx) {
            $this->whatsapp->sendMessage($chatId, '❌ Tidak ada transaksi untuk dihapus.');
            return;
        }
        $this->txRepo->deleteById((int) $tx->id);
        $this->whatsapp->sendMessage(
            $chatId,
            '🗑️ Transaksi terakhir dihapus: ' . rp((int) $tx->total) . " ({$tx->category})"
        );
    }

    // -------------------------------------------------------------------------
    // INTENT: UNDO (batal dalam 5 menit)
    // -------------------------------------------------------------------------

    private function intentUndo(string $chatId): void
    {
        $result = $this->txRepo->undoLast($chatId);
        $msg    = match ($result) {
            'ok'        => '↩️ Transaksi terakhir dibatalkan.',
            'expired'   => '⚠️ Batas waktu undo sudah habis (maks 5 menit).',
            'not_found' => '❌ Tidak ada transaksi untuk di-undo.',
            default     => '❌ Gagal undo.',
        };
        $this->whatsapp->sendMessage($chatId, $msg);
    }

    // -------------------------------------------------------------------------
    // INTENT: EDIT
    // -------------------------------------------------------------------------

    private function intentEdit(string $chatId, array $intent): void
    {
        $tx = $this->txRepo->lastTransaction($chatId);
        if (!$tx) {
            $this->whatsapp->sendMessage($chatId, '❌ Tidak ada transaksi untuk diedit.');
            return;
        }

        $updates = [];
        if (!empty($intent['amount'])) {
            if (!$this->validateAmount($chatId, $intent['amount'])) {
                return;
            }
            $updates['total'] = (int) $intent['amount'];
        }
        if (!empty($intent['category'])) {
            $updates['category'] = $intent['category'];
        }
        if (!empty($intent['note'])) {
            $updates['note'] = $intent['note'];
        }

        if (empty($updates)) {
            $this->whatsapp->sendMessage($chatId, '❌ Tidak ada perubahan yang dikenali.');
            return;
        }

        $this->txRepo->update((int) $tx->id, $updates);

        $reply = '✏️ <b>Transaksi diperbarui!</b>';
        if (isset($updates['total'])) {
            $reply .= "\nNominal: " . rp($updates['total']);
        }
        if (isset($updates['category'])) {
            $reply .= "\nKategori: {$updates['category']}";
        }

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: REMINDER
    // -------------------------------------------------------------------------

    private function intentReminder(string $chatId, array $intent): void
    {
        $task     = $intent['task'] ?? null;
        $remindAt = $intent['remind_at'] ?? null;

        if (!$task || !$remindAt) {
            $this->whatsapp->sendMessage($chatId, '❌ Gagal baca detail pengingat. Coba lagi dengan format: "ingetin [tugas] [waktu]"');
            return;
        }

        $repeat = $intent['repeat'] ?? 'none';
        $this->reminderRepo->create($chatId, $task, $remindAt, $repeat);

        $repeatStr = match ($repeat) {
            'daily'   => ' (tiap hari)',
            'weekly'  => ' (tiap minggu)',
            'monthly' => ' (tiap bulan)',
            default   => '',
        };

        $dt = \Carbon\Carbon::parse($remindAt)->setTimezone(config('app.timezone'));
        $this->whatsapp->sendMessage(
            $chatId,
            "⏰ <b>Pengingat diset!</b>\n"
            . "Tugas: {$task}\n"
            . "Waktu: " . $dt->format('d M Y, H:i') . "{$repeatStr}"
        );
    }

    // -------------------------------------------------------------------------
    // INTENT: REMINDER LIST
    // -------------------------------------------------------------------------

    private function intentReminderList(string $chatId): void
    {
        $reminders = $this->reminderRepo->pending($chatId);
        if (empty($reminders)) {
            $this->whatsapp->sendMessage($chatId, '📭 Tidak ada pengingat aktif.');
            return;
        }

        $lines = ['📋 <b>Pengingat Aktif:</b>'];
        foreach ($reminders as $r) {
            $dt       = \Carbon\Carbon::parse($r->remind_at)->setTimezone(config('app.timezone'));
            $repeatStr = $r->repeat !== 'none' ? " ({$r->repeat})" : '';
            $lines[]  = "· {$r->task} — " . $dt->format('d M Y, H:i') . $repeatStr;
        }

        $this->whatsapp->sendMessage($chatId, implode("\n", $lines));
    }

    // -------------------------------------------------------------------------
    // INTENT: TRANSFER
    // -------------------------------------------------------------------------

    private function intentTransfer(string $chatId, array $intent): void
    {
        $amount     = $intent['amount'] ?? null;
        $fromName   = $intent['from_wallet'] ?? null;
        $toName     = $intent['to_wallet'] ?? null;

        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }
        if (!$fromName || !$toName) {
            $this->whatsapp->sendMessage($chatId, '❌ Sebutkan dompet asal dan tujuan. Contoh: "transfer 100rb dari BCA ke DANA"');
            return;
        }

        $from = $this->walletRepo->findByName($chatId, $fromName);
        $to   = $this->walletRepo->findByName($chatId, $toName);

        if (!$from) {
            $this->whatsapp->sendMessage($chatId, "❌ Dompet <b>{$fromName}</b> tidak ditemukan.");
            return;
        }
        if (!$to) {
            $this->whatsapp->sendMessage($chatId, "❌ Dompet <b>{$toName}</b> tidak ditemukan.");
            return;
        }

        $saldoFrom = $this->walletRepo->balance((int) $from->id);
        if ($saldoFrom < (int) $amount) {
            $this->whatsapp->sendMessage(
                $chatId,
                "❌ Saldo <b>{$from->name}</b> tidak cukup.\nSaldo: " . rp($saldoFrom) . ', dibutuhkan: ' . rp((int) $amount)
            );
            return;
        }

        $this->transferRepo->create($chatId, (int) $from->id, (int) $to->id, (int) $amount);

        $newFrom = $this->walletRepo->balance((int) $from->id);
        $newTo   = $this->walletRepo->balance((int) $to->id);

        $this->whatsapp->sendMessage(
            $chatId,
            "🔄 <b>Transfer berhasil!</b>\n"
            . rp((int) $amount) . " dari <b>{$from->name}</b> ke <b>{$to->name}</b>\n\n"
            . "💼 {$from->name}: " . rp($newFrom) . "\n"
            . "💼 {$to->name}: " . rp($newTo)
        );
    }

    // -------------------------------------------------------------------------
    // INTENT: LANGGANAN
    // -------------------------------------------------------------------------

    private function intentLangganan(string $chatId, array $intent): void
    {
        $amount = $intent['amount'] ?? null;
        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }

        $name       = $intent['note'] ?? null;
        $dayOfMonth = (int) ($intent['day_of_month'] ?? now(config('app.timezone'))->day);
        $currency   = strtoupper($intent['currency'] ?? 'IDR');

        if (!$name || $dayOfMonth < 1 || $dayOfMonth > 31) {
            $this->whatsapp->sendMessage($chatId, '❌ Format kurang lengkap. Contoh: "langganan netflix 54rb tiap tanggal 5"');
            return;
        }

        // Konversi mata uang asing ke IDR
        $originalAmount = (float) $amount;
        $convertedNote  = '';
        if ($currency !== 'IDR') {
            $rate = $this->fetchExchangeRate($currency);
            if ($rate) {
                $amount        = (int) round($originalAmount * $rate);
                $convertedNote = "\n💱 Kurs: {$currency} " . number_format($originalAmount, 2) . " × " . number_format($rate, 0, ',', '.') . " = " . rp($amount);
            } else {
                $this->whatsapp->sendMessage($chatId, "⚠️ Gagal ambil kurs {$currency}/IDR. Coba lagi atau input nominal IDR langsung.");
                return;
            }
        }

        $category = $intent['category'] ?? 'Tagihan';
        $walletId = null;
        if (!empty($intent['wallet'])) {
            $wallet   = $this->walletRepo->findByName($chatId, $intent['wallet']);
            $walletId = $wallet?->id;
        }

        $pending = [
            'name'       => $name,
            'amount'     => (int) $amount,
            'category'   => $category,
            'wallet_id'  => $walletId,
            'day'        => $dayOfMonth,
            'converted'  => $convertedNote,
        ];

        // Cek apakah langganan sudah ada
        $existing = \Illuminate\Support\Facades\DB::selectOne(
            'SELECT id, amount FROM subscriptions WHERE chat_id = ? AND LOWER(name) = LOWER(?) AND active = 1',
            [$chatId, $name]
        );

        if ($existing) {
            // Tanya user mau buat baru atau cukup catat transaksi
            Cache::put("pending_sub_confirm:{$chatId}", $pending, now()->addMinutes(5));
            $this->whatsapp->sendMessage(
                $chatId,
                "⚠️ <b>Langganan \"{$name}\" sudah ada</b> (Rp" . number_format((int)$existing->amount, 0, ',', '.') . "/bln).\n\n"
                . "Transaksi bulan ini tetap akan dicatat.\n\n"
                . "Mau <b>tambah sebagai langganan baru</b> juga? (ketik <b>baru</b> / <b>tidak</b>)"
            );
            return;
        }

        $this->confirmNewSubscription($chatId, $pending, forceNew: true);
    }

    private function confirmNewSubscription(string $chatId, array $pending, bool $forceNew): void
    {
        $currentMonth = now(config('app.timezone'))->format('Y-m');

        if ($forceNew) {
            $subId = $this->subRepo->create(
                $chatId, $pending['name'], $pending['amount'],
                $pending['category'], $pending['wallet_id'], $pending['day']
            );
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE subscriptions SET last_charged_month = ? WHERE id = ?',
                [$currentMonth, $subId]
            );
        }

        $this->txRepo->create([
            'chat_id'   => $chatId,
            'type'      => 'expense',
            'wallet_id' => $pending['wallet_id'],
            'date'      => today_wita(),
            'total'     => $pending['amount'],
            'category'  => $pending['category'],
            'note'      => "Langganan: {$pending['name']}",
        ]);

        $reply = "📅 <b>Langganan dicatat!</b>\n"
            . "Nama: {$pending['name']}\n"
            . "Nominal: " . rp($pending['amount']) . $pending['converted'] . "\n"
            . "Tagih tiap tgl: {$pending['day']}\n"
            . "<i>✅ Langganan baru ditambahkan — bulan depan auto-catat tiap tgl {$pending['day']}.</i>";

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: UTANG / PIUTANG
    // -------------------------------------------------------------------------

    private function intentUtang(string $chatId, array $intent): void
    {
        $amount = $intent['amount'] ?? null;
        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }

        $type    = $intent['type'] ?? 'utang';
        $person  = $intent['person'] ?? null;
        $note    = $intent['note'] ?? null;
        $dueDate = null;

        if ($intent['remind_at'] ?? null) {
            $dueDate = \Carbon\Carbon::parse($intent['remind_at'])->format('Y-m-d');
        }

        if (!$person) {
            $this->whatsapp->sendMessage($chatId, '❌ Sebutkan nama orang. Contoh: "utang ke Budi 50rb"');
            return;
        }

        $this->debtRepo->create($chatId, $type, $person, (int) $amount, $note, $dueDate);

        $typeStr = $type === 'piutang' ? 'Piutang' : 'Utang';
        $icon    = $type === 'piutang' ? '🤝' : '💳';
        $reply   = "{$icon} <b>{$typeStr} dicatat!</b>\n"
            . "Orang: {$person}\n"
            . "Nominal: " . rp((int) $amount);
        if ($dueDate) {
            $reply .= "\nJatuh tempo: " . \Carbon\Carbon::parse($dueDate)->format('d M Y');
        }

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: LUNAS
    // -------------------------------------------------------------------------

    private function intentLunas(string $chatId, array $intent): void
    {
        $type   = $intent['type'] ?? 'utang';
        $person = $intent['person'] ?? null;

        if (!$person) {
            $this->whatsapp->sendMessage($chatId, '❌ Sebutkan nama orang. Contoh: "Budi sudah lunas"');
            return;
        }

        $debt = $this->debtRepo->settleByPerson($chatId, $type, $person);
        if (!$debt) {
            $typeStr = $type === 'piutang' ? 'piutang' : 'utang';
            $this->whatsapp->sendMessage($chatId, "❌ Tidak ditemukan {$typeStr} aktif atas nama <b>{$person}</b>.");
            return;
        }

        $typeStr = $type === 'piutang' ? 'Piutang' : 'Utang';
        $this->whatsapp->sendMessage(
            $chatId,
            "✅ <b>{$typeStr} {$person} lunas!</b>\nNominal: " . rp((int) $debt->amount)
        );
    }

    // -------------------------------------------------------------------------
    // INTENT: NABUNG
    // -------------------------------------------------------------------------

    private function intentNabung(string $chatId, array $intent): void
    {
        $amount = $intent['amount'] ?? null;
        if (!$this->validateAmount($chatId, $amount)) {
            return;
        }

        $goalName = $intent['note'] ?? null;
        if (!$goalName) {
            $this->whatsapp->sendMessage($chatId, '❌ Sebutkan nama tujuan tabungan. Contoh: "nabung laptop 500rb"');
            return;
        }

        $existing = $this->savingsRepo->findByName($chatId, $goalName);
        if (!$existing) {
            // Goal belum ada — buat baru dengan target = amount pertama (bisa diubah di dashboard)
            $this->savingsRepo->create($chatId, $goalName, (int) $amount);
        }
        $goal = $this->savingsRepo->addProgress($chatId, $goalName, (int) $amount);

        if (!$goal) {
            $this->whatsapp->sendMessage($chatId, '❌ Gagal menyimpan setoran tabungan.');
            return;
        }

        $pct   = $goal->target_amount > 0 ? round(($goal->saved_amount / $goal->target_amount) * 100) : 0;
        $reply = "🏦 <b>Setoran tabungan dicatat!</b>\n"
            . "Goal: {$goal->name}\n"
            . "Setoran: " . rp((int) $amount) . "\n"
            . "Terkumpul: " . rp((int) $goal->saved_amount) . ' / ' . rp((int) $goal->target_amount) . " ({$pct}%)";

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: LAINNYA
    // -------------------------------------------------------------------------

    private function intentLainnya(string $chatId): void
    {
        $this->whatsapp->sendMessage(
            $chatId,
            "Halo! 👋 Saya bot pencatat keuangan.\n\nKirim /help untuk melihat cara penggunaan."
        );
    }

    // -------------------------------------------------------------------------
    // KONFIRMASI STRUK (dipicu dari cache — struk bisa masuk lewat Telegram,
    // konfirmasi YA/TIDAK boleh dibalas dari channel manapun)
    // -------------------------------------------------------------------------

    private function confirmPendingReceipt(string $chatId, string $pendingKey): void
    {
        $data = Cache::get($pendingKey);
        Cache::forget($pendingKey);

        if (!$data) {
            $this->whatsapp->sendMessage($chatId, '⚠️ Data struk sudah kedaluwarsa. Kirim ulang fotonya ya.');
            return;
        }

        $this->saveReceiptData($chatId, $data);
    }

    private function saveReceiptData(string $chatId, array $data): void
    {
        // Pindahkan file dari temp ke permanent
        $receiptPath = null;
        if (!empty($data['temp_path']) && \Illuminate\Support\Facades\Storage::exists($data['temp_path'])) {
            $date        = $data['date'] ?? today_wita();
            $permPath    = "public/receipts/{$chatId}/{$date}_" . time() . '.jpg';
            \Illuminate\Support\Facades\Storage::move($data['temp_path'], $permPath);
            $receiptPath = $permPath;
        }

        $items = $data['items'] ?? [];
        // Pastikan semua item punya name
        $items = array_map(function ($item) {
            $item['name'] = $item['name'] ?? 'Tidak terdeteksi';
            return $item;
        }, $items);

        $tx = $this->txRepo->create([
            'chat_id'      => $chatId,
            'type'         => 'expense',
            'date'         => $data['date'] ?? today_wita(),
            'total'        => (int) $data['total'],
            'category'     => $data['category'],
            'merchant'     => $data['merchant'] ?? null,
            'note'         => $data['note'] ?? null,
            'receipt_path' => $receiptPath,
            'items'        => $items,
        ]);

        $reply = "✅ <b>Struk berhasil dicatat!</b>\n"
            . "Merchant: " . ($data['merchant'] ?? '-') . "\n"
            . "Total: " . rp((int) $data['total']) . "\n"
            . "Kategori: " . $data['category'];

        if (!empty($items)) {
            $reply .= "\n\n<b>Item:</b>";
            foreach (array_slice($items, 0, 5) as $item) {
                $reply .= "\n· " . ($item['name'] ?? '-') . " x{$item['qty']} = " . rp((int) $item['price']);
            }
            if (count($items) > 5) {
                $reply .= "\n· ... dan " . (count($items) - 5) . " item lainnya";
            }
        }

        $budgetStatus = $this->finance->buildBudgetStatus($chatId);
        if ($budgetStatus) {
            $reply .= "\n\n{$budgetStatus}";
        }

        $this->whatsapp->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    private function validateAmount(string $chatId, mixed $amount): bool
    {
        if ($amount === null || $amount === '' || (is_string($amount) && !ctype_digit((string) $amount))) {
            if (is_float($amount) && floor($amount) != $amount) {
                $this->whatsapp->sendMessage($chatId, '❌ Nominal tidak valid. Masukkan angka bulat, contoh: 15000');
                return false;
            }
        }

        $intAmount = (int) $amount;
        if ($intAmount <= 0) {
            $this->whatsapp->sendMessage($chatId, '❌ Nominal tidak valid. Masukkan angka bulat, contoh: 15000');
            return false;
        }

        return true;
    }

    private function sendHelp(string $chatId): void
    {
        $help = "👋 <b>Catetin</b> — Asisten Pribadi Yujang\n\n"
            . "<b>Catat Transaksi:</b>\n"
            . "· Ketik nominal + keterangan: <i>makan siang 25rb</i>\n\n"
            . "<b>Perintah Cepat:</b>\n"
            . "· <b>rekap</b> — rekap minggu ini\n"
            . "· <b>rekap bulanan</b>\n"
            . "· <b>sisa</b> — sisa budget\n"
            . "· <b>saldo</b> — cek saldo dompet\n"
            . "· <b>undo</b> — batalkan transaksi terakhir (maks 5 menit)\n\n"
            . "<b>Budget:</b>\n"
            . "· <i>budget harian 100rb</i>\n"
            . "· <i>budget bulanan 3jt</i>\n\n"
            . "<b>Transfer Dompet:</b>\n"
            . "· <i>transfer 500rb dari BCA ke DANA</i>\n\n"
            . "<b>Pengingat:</b>\n"
            . "· <i>ingetin minum vitamin tiap jam 8 pagi</i>\n\n"
            . "<b>Utang/Piutang:</b>\n"
            . "· <i>utang ke Budi 100rb</i>\n"
            . "· <i>Budi lunas</i>\n\n"
            . "<b>Tabungan:</b>\n"
            . "· <i>nabung laptop 200rb</i>\n\n"
            . "<i>Catatan: foto struk & voice note masih cuma bisa lewat Telegram.</i>";

        $this->whatsapp->sendMessage($chatId, $help);
    }
}
