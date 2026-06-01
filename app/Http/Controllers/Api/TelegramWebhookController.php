<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
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
use Illuminate\Support\Facades\Storage;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramService       $telegram,
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

    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validasi Telegram Secret Token
        // Fallback ke $_SERVER untuk shared hosting yang strip custom headers
        $secret   = config('services.telegram.secret');
        $incoming = $request->header('X-Telegram-Bot-Api-Secret-Token')
            ?? ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null);

        if ($secret && $incoming !== $secret) {
            return response()->json(['ok' => false], 403);
        }

        try {
            $update = $request->all();
            $this->process($update);
        } catch (\Throwable $e) {
            Log::error('TelegramWebhook::handle exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return response()->json(['ok' => true]);
    }

    public function process(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!$message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        if (!$chatId) {
            return;
        }

        // Auto-create dompet Cash untuk user baru
        $this->walletRepo->ensureDefaultWallet($chatId);

        // Handle voice note
        if (isset($message['voice'])) {
            $this->handleVoice($chatId, $message['voice']);
            return;
        }

        // Handle foto struk
        if (isset($message['photo'])) {
            $this->handlePhoto($chatId, $message['photo']);
            return;
        }

        // Handle dokumen (bisa juga foto yg dikirim sebagai file)
        if (isset($message['document'])) {
            $mime = $message['document']['mime_type'] ?? '';
            if (str_starts_with($mime, 'image/')) {
                $this->handlePhotoDocument($chatId, $message['document']);
                return;
            }
        }

        $text = trim($message['text'] ?? '');
        if ($text === '') {
            return;
        }

        $this->handleText($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // TEXT
    // -------------------------------------------------------------------------

    public function handleText(string $chatId, string $text): void
    {
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
                $this->telegram->sendMessage($chatId, '❌ Dibatalkan.');
                return;
            }
            // Bukan ya/tidak — teruskan proses normal tapi hapus pending
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
            $this->telegram->sendMessage($chatId, '⚠️ Maaf, AI sedang bermasalah. Coba beberapa saat lagi.');
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

        $tx = $this->txRepo->create([
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
            . "Nominal: " . rp((int) $amount) . "\n"
            . "Kategori: {$category}\n"
            . "Catatan: {$note}";

        if ($walletName) {
            $saldo  = $this->walletRepo->balance((int) $walletId);
            $reply .= "\n💼 Saldo {$walletName}: " . rp($saldo);
        }

        // Tampilkan status budget jika expense
        if ($type === 'expense') {
            $budgetStatus = $this->finance->buildBudgetStatus($chatId);
            if ($budgetStatus) {
                $reply .= "\n\n{$budgetStatus}";
            }
        }

        $this->telegram->sendMessage($chatId, $reply);
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
        $this->telegram->sendMessage($chatId, "✅ Budget <b>{$period}</b> diset ke " . rp((int) $amount));
    }

    // -------------------------------------------------------------------------
    // INTENT: REKAP
    // -------------------------------------------------------------------------

    private function intentRekap(string $chatId, array $intent): void
    {
        $period = $intent['period'] ?? 'mingguan';
        $text   = $this->finance->buildRecap($chatId, $period);
        $this->telegram->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: SISA
    // -------------------------------------------------------------------------

    private function intentSisa(string $chatId, array $intent): void
    {
        $period = $intent['period'] ?? 'mingguan';
        $text   = $this->finance->buildSisaText($chatId, $period);
        $this->telegram->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: SALDO
    // -------------------------------------------------------------------------

    private function intentSaldo(string $chatId, array $intent): void
    {
        $walletName = $intent['wallet'] ?? null;
        $text       = $this->finance->buildSaldoText($chatId, $walletName);
        $this->telegram->sendMessage($chatId, $text);
    }

    // -------------------------------------------------------------------------
    // INTENT: HAPUS (hapus transaksi terakhir — permanen)
    // -------------------------------------------------------------------------

    private function intentHapus(string $chatId): void
    {
        $tx = $this->txRepo->lastTransaction($chatId);
        if (!$tx) {
            $this->telegram->sendMessage($chatId, '❌ Tidak ada transaksi untuk dihapus.');
            return;
        }
        $this->txRepo->deleteById((int) $tx->id);
        $this->telegram->sendMessage(
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
        $this->telegram->sendMessage($chatId, $msg);
    }

    // -------------------------------------------------------------------------
    // INTENT: EDIT
    // -------------------------------------------------------------------------

    private function intentEdit(string $chatId, array $intent): void
    {
        $tx = $this->txRepo->lastTransaction($chatId);
        if (!$tx) {
            $this->telegram->sendMessage($chatId, '❌ Tidak ada transaksi untuk diedit.');
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
            $this->telegram->sendMessage($chatId, '❌ Tidak ada perubahan yang dikenali.');
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

        $this->telegram->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: REMINDER
    // -------------------------------------------------------------------------

    private function intentReminder(string $chatId, array $intent): void
    {
        $task     = $intent['task'] ?? null;
        $remindAt = $intent['remind_at'] ?? null;

        if (!$task || !$remindAt) {
            $this->telegram->sendMessage($chatId, '❌ Gagal baca detail pengingat. Coba lagi dengan format: "ingetin [tugas] [waktu]"');
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
        $this->telegram->sendMessage(
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
            $this->telegram->sendMessage($chatId, '📭 Tidak ada pengingat aktif.');
            return;
        }

        $lines = ['📋 <b>Pengingat Aktif:</b>'];
        foreach ($reminders as $r) {
            $dt       = \Carbon\Carbon::parse($r->remind_at)->setTimezone(config('app.timezone'));
            $repeatStr = $r->repeat !== 'none' ? " ({$r->repeat})" : '';
            $lines[]  = "· {$r->task} — " . $dt->format('d M Y, H:i') . $repeatStr;
        }

        $this->telegram->sendMessage($chatId, implode("\n", $lines));
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
            $this->telegram->sendMessage($chatId, '❌ Sebutkan dompet asal dan tujuan. Contoh: "transfer 100rb dari BCA ke DANA"');
            return;
        }

        $from = $this->walletRepo->findByName($chatId, $fromName);
        $to   = $this->walletRepo->findByName($chatId, $toName);

        if (!$from) {
            $this->telegram->sendMessage($chatId, "❌ Dompet <b>{$fromName}</b> tidak ditemukan.");
            return;
        }
        if (!$to) {
            $this->telegram->sendMessage($chatId, "❌ Dompet <b>{$toName}</b> tidak ditemukan.");
            return;
        }

        $saldoFrom = $this->walletRepo->balance((int) $from->id);
        if ($saldoFrom < (int) $amount) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Saldo <b>{$from->name}</b> tidak cukup.\nSaldo: " . rp($saldoFrom) . ', dibutuhkan: ' . rp((int) $amount)
            );
            return;
        }

        $this->transferRepo->create($chatId, (int) $from->id, (int) $to->id, (int) $amount);

        $newFrom = $this->walletRepo->balance((int) $from->id);
        $newTo   = $this->walletRepo->balance((int) $to->id);

        $this->telegram->sendMessage(
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
        $dayOfMonth = (int) ($intent['day_of_month'] ?? 0);

        if (!$name || $dayOfMonth < 1 || $dayOfMonth > 31) {
            $this->telegram->sendMessage($chatId, '❌ Format kurang lengkap. Contoh: "langganan netflix 54rb tiap tanggal 5"');
            return;
        }

        $category = $intent['category'] ?? 'Lainnya';
        $walletId = null;
        if (!empty($intent['wallet'])) {
            $wallet   = $this->walletRepo->findByName($chatId, $intent['wallet']);
            $walletId = $wallet?->id;
        }

        $this->subRepo->create($chatId, $name, (int) $amount, $category, $walletId, $dayOfMonth);

        $this->telegram->sendMessage(
            $chatId,
            "📅 <b>Langganan ditambahkan!</b>\n"
            . "Nama: {$name}\n"
            . "Nominal: " . rp((int) $amount) . "\n"
            . "Tagih tiap tgl: {$dayOfMonth}"
        );
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
            $this->telegram->sendMessage($chatId, '❌ Sebutkan nama orang. Contoh: "utang ke Budi 50rb"');
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

        $this->telegram->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: LUNAS
    // -------------------------------------------------------------------------

    private function intentLunas(string $chatId, array $intent): void
    {
        $type   = $intent['type'] ?? 'utang';
        $person = $intent['person'] ?? null;

        if (!$person) {
            $this->telegram->sendMessage($chatId, '❌ Sebutkan nama orang. Contoh: "Budi sudah lunas"');
            return;
        }

        $debt = $this->debtRepo->settleByPerson($chatId, $type, $person);
        if (!$debt) {
            $typeStr = $type === 'piutang' ? 'piutang' : 'utang';
            $this->telegram->sendMessage($chatId, "❌ Tidak ditemukan {$typeStr} aktif atas nama <b>{$person}</b>.");
            return;
        }

        $typeStr = $type === 'piutang' ? 'Piutang' : 'Utang';
        $this->telegram->sendMessage(
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
            $this->telegram->sendMessage($chatId, '❌ Sebutkan nama tujuan tabungan. Contoh: "nabung laptop 500rb"');
            return;
        }

        $existing = $this->savingsRepo->findByName($chatId, $goalName);
        if (!$existing) {
            // Goal belum ada — buat baru dengan target = amount pertama (bisa diubah di dashboard)
            $this->savingsRepo->create($chatId, $goalName, (int) $amount);
        }
        $goal = $this->savingsRepo->addProgress($chatId, $goalName, (int) $amount);

        if (!$goal) {
            $this->telegram->sendMessage($chatId, '❌ Gagal menyimpan setoran tabungan.');
            return;
        }

        $pct   = $goal->target_amount > 0 ? round(($goal->saved_amount / $goal->target_amount) * 100) : 0;
        $reply = "🏦 <b>Setoran tabungan dicatat!</b>\n"
            . "Goal: {$goal->name}\n"
            . "Setoran: " . rp((int) $amount) . "\n"
            . "Terkumpul: " . rp((int) $goal->saved_amount) . ' / ' . rp((int) $goal->target_amount) . " ({$pct}%)";

        $this->telegram->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // INTENT: LAINNYA
    // -------------------------------------------------------------------------

    private function intentLainnya(string $chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            "Halo! 👋 Saya bot pencatat keuangan.\n\nKirim /help untuk melihat cara penggunaan."
        );
    }

    // -------------------------------------------------------------------------
    // HANDLE PHOTO (struk)
    // -------------------------------------------------------------------------

    private function handlePhoto(string $chatId, array $photos): void
    {
        // Ambil foto resolusi tertinggi
        $photo  = end($photos);
        $fileId = $photo['file_id'];

        $this->processReceiptFile($chatId, $fileId, 'image/jpeg');
    }

    private function handlePhotoDocument(string $chatId, array $document): void
    {
        $fileId   = $document['file_id'];
        $mimeType = $document['mime_type'] ?? 'image/jpeg';
        $this->processReceiptFile($chatId, $fileId, $mimeType);
    }

    private function processReceiptFile(string $chatId, string $fileId, string $mimeType): void
    {
        $this->telegram->sendMessage($chatId, '📸 Membaca struk, tunggu sebentar...');

        $file = $this->telegram->getFile($fileId);
        if (!$file || empty($file->file_path)) {
            $this->telegram->sendMessage($chatId, '❌ Gagal mengunduh foto.');
            return;
        }

        $rawBytes = $this->telegram->downloadFileRaw($file->file_path);
        if (!$rawBytes) {
            $this->telegram->sendMessage($chatId, '❌ Gagal mengunduh foto.');
            return;
        }

        $base64 = base64_encode($rawBytes);
        $data   = $this->gemini->extractReceipt($base64, $mimeType);

        if ($data === null) {
            $this->telegram->sendMessage($chatId, '⚠️ Maaf, AI sedang bermasalah. Coba beberapa saat lagi.');
            return;
        }

        if (!($data['is_receipt'] ?? true)) {
            $this->telegram->sendMessage($chatId, '❌ Gambar ini bukan struk/nota. Kirim foto struk yang jelas ya!');
            return;
        }

        $confidence = (float) ($data['confidence'] ?? 1.0);
        $total      = (int) ($data['total'] ?? 0);
        $category   = $data['category'] ?? 'Lainnya';

        if ($total <= 0) {
            $this->telegram->sendMessage($chatId, '❌ Tidak bisa membaca total dari struk. Coba foto lebih jelas.');
            return;
        }

        // Simpan raw bytes untuk di-store setelah konfirmasi/langsung
        $tempPath = 'receipts/temp/' . $chatId . '_' . time() . '.jpg';
        Storage::put($tempPath, $rawBytes);

        $receiptData = [
            'chat_id'   => $chatId,
            'type'      => 'expense',
            'date'      => $data['date'] ?? today_wita(),
            'total'     => $total,
            'category'  => $category,
            'merchant'  => $data['merchant'] ?? null,
            'note'      => $data['note'] ?? null,
            'items'     => $data['items'] ?? [],
            'temp_path' => $tempPath,
        ];

        if ($confidence < 0.60) {
            // Simpan ke cache, minta konfirmasi
            $pendingKey = "pending_confirm:{$chatId}";
            Cache::put($pendingKey, $receiptData, now()->addMinutes(5));

            $this->telegram->sendMessage(
                $chatId,
                "⚠️ Saya kurang yakin membaca struk ini.\n"
                . "Total terdeteksi: <b>" . rp($total) . "</b> ({$category}).\n"
                . "Balas <b>YA</b> untuk simpan atau <b>TIDAK</b> untuk batal."
            );
            return;
        }

        // confidence >= 0.60 → simpan langsung
        $this->saveReceiptData($chatId, $receiptData);
    }

    private function confirmPendingReceipt(string $chatId, string $pendingKey): void
    {
        $data = Cache::get($pendingKey);
        Cache::forget($pendingKey);

        if (!$data) {
            $this->telegram->sendMessage($chatId, '⚠️ Data struk sudah kedaluwarsa. Kirim ulang fotonya ya.');
            return;
        }

        $this->saveReceiptData($chatId, $data);
    }

    private function saveReceiptData(string $chatId, array $data): void
    {
        // Pindahkan file dari temp ke permanent
        $receiptPath = null;
        if (!empty($data['temp_path']) && Storage::exists($data['temp_path'])) {
            $date        = $data['date'] ?? today_wita();
            $permPath    = "public/receipts/{$chatId}/{$date}_" . time() . '.jpg';
            Storage::move($data['temp_path'], $permPath);
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

        $this->telegram->sendMessage($chatId, $reply);
    }

    // -------------------------------------------------------------------------
    // HANDLE VOICE NOTE
    // -------------------------------------------------------------------------

    private function handleVoice(string $chatId, array $voice): void
    {
        $supportedMimes = ['audio/ogg', 'audio/oga', 'audio/opus'];
        $mimeType       = $voice['mime_type'] ?? 'audio/ogg';

        if (!in_array(strtolower($mimeType), $supportedMimes) && !str_contains($mimeType, 'ogg') && !str_contains($mimeType, 'opus')) {
            $this->telegram->sendMessage($chatId, '❌ Format audio tidak didukung. Kirim voice note Telegram biasa ya.');
            return;
        }

        $this->telegram->sendMessage($chatId, '🎤 Mendengarkan voice note...');

        $file = $this->telegram->getFile($voice['file_id']);
        if (!$file || empty($file->file_path)) {
            $this->telegram->sendMessage($chatId, '❌ Gagal mengunduh voice note.');
            return;
        }

        $base64 = $this->telegram->downloadFile($file->file_path);
        if (!$base64) {
            $this->telegram->sendMessage($chatId, '❌ Gagal mengunduh voice note.');
            return;
        }

        $transcript = $this->gemini->transcribeAudio($base64, $mimeType);
        if ($transcript === null) {
            $this->telegram->sendMessage($chatId, '⚠️ Maaf, AI sedang bermasalah. Coba beberapa saat lagi.');
            return;
        }

        $transcript = trim($transcript);
        if ($transcript === '') {
            $this->telegram->sendMessage($chatId, '❌ Tidak bisa mendengar suara. Coba rekam ulang yang lebih jelas.');
            return;
        }

        // Konfirmasi transkripsi
        $this->telegram->sendMessage($chatId, "🎤 <i>\"" . htmlspecialchars($transcript) . "\"</i>\n\nMemproses...");

        // Proses sebagai teks biasa
        $this->handleText($chatId, $transcript);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    private function validateAmount(string $chatId, mixed $amount): bool
    {
        if ($amount === null || $amount === '' || (is_string($amount) && !ctype_digit((string) $amount))) {
            if (is_float($amount) && floor($amount) != $amount) {
                $this->telegram->sendMessage($chatId, '❌ Nominal tidak valid. Masukkan angka bulat, contoh: 15000');
                return false;
            }
        }

        $intAmount = (int) $amount;
        if ($intAmount <= 0) {
            $this->telegram->sendMessage($chatId, '❌ Nominal tidak valid. Masukkan angka bulat, contoh: 15000');
            return false;
        }

        return true;
    }

    private function sendHelp(string $chatId): void
    {
        $help = "👋 <b>Catetin</b> — Asisten Pribadi Yujang\n\n"
            . "<b>Catat Transaksi:</b>\n"
            . "· Ketik nominal + keterangan: <i>makan siang 25rb</i>\n"
            . "· Foto struk langsung\n"
            . "· Voice note\n\n"
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
            . "· <i>nabung laptop 200rb</i>";

        $this->telegram->sendMessage($chatId, $help);
    }
}
