<?php

namespace App\Services;

use App\Support\JsonExtractor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.gemini.key');
        $this->model   = config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}";
    }

    public function extractReceipt(string $base64Image, string $mimeType = 'image/jpeg'): ?array
    {
        $prompt = <<<'PROMPT'
Kamu adalah asisten pencatat pengeluaran. Baca gambar struk/nota dan ekstrak datanya jadi JSON.

ATURAN:
- Balas HANYA JSON valid. Tanpa teks pembuka, penjelasan, atau markdown.
- Semua nominal = angka bulat Rupiah (integer, tanpa titik/koma/"Rp"). Contoh: 15000.
- Gambar BUKAN struk (foto random) -> set "is_receipt": false.
- Tanggal nggak kebaca -> "date": null.
- "category" WAJIB dari: "Makanan & Minuman","Belanja Harian","Transport","Tagihan","Kesehatan","Hiburan","Lainnya".
- Setiap item WAJIB punya "name". Nggak kebaca -> "name": "Tidak terdeteksi".
- "price" per item = SUBTOTAL baris (qty x satuan), bukan harga satuan.
- Total nggak jelas -> hitung dari penjumlahan price semua item.
- "confidence": float 0.00–1.00 seberapa yakin kamu membaca struk ini.

FORMAT:
{"is_receipt":true,"merchant":"nama toko atau null","date":"YYYY-MM-DD atau null","items":[{"name":"nama barang","qty":1,"price":10000}],"total":10000,"category":"kategori","note":null,"confidence":0.95}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data'      => $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ];

        $raw = $this->callApi($payload);
        if ($raw === null) {
            return null;
        }

        return JsonExtractor::extract($raw);
    }

    public function parseIntent(string $message, string $now): ?array
    {
        $prompt = <<<PROMPT
Kamu mesin klasifikasi maksud untuk bot keuangan + pengingat. Balas HANYA JSON.
Waktu sekarang: {$now}

INTENT:
- "catat": transaksi manual. "type": "expense"(default) / "income"(gajian/dapet/transfer masuk).
  Ekstrak: "amount"(angka penuh), "category"(WAJIB dari daftar), "note"(WAJIB, "Tidak terdeteksi" kalau kosong), "wallet"(nama dompet kalau disebut, else null).
- "set_budget": "amount" + "period"(harian/mingguan/bulanan, def mingguan). Hanya kalau nyebut budget/anggaran.
- "rekap": "period"(def mingguan).
- "sisa": "period"(def mingguan).
- "saldo": cek saldo. "wallet"(nama spesifik atau null=semua).
- "hapus": hapus transaksi terakhir.
- "undo": batalkan transaksi terakhir (maks 5 menit).
- "edit": koreksi transaksi terakhir. "amount" dan/atau "category" baru.
- "reminder": "task" + "remind_at"("YYYY-MM-DD HH:MM:SS" dari waktu sekarang; jam lewat -> besok) + "repeat"(none/daily/weekly/monthly, def none).
- "reminder_list": lihat pengingat aktif.
- "transfer": pindah uang antar dompet. "amount","from_wallet","to_wallet". TIDAK masuk expense/income.
- "langganan": tambah langganan. "note"(nama), "amount", "category", "day_of_month"(1-31).
- "utang": "type"("utang"=aku ngutang/"piutang"=orang ngutang ke aku), "person", "amount", "remind_at"(due date jika ada, format YYYY-MM-DD HH:MM:SS), "note".
- "lunas": lunasi. "type"+"person".
- "nabung": tambah setoran ke goal. "note"(nama goal), "amount".
- "lainnya": sapaan/basa-basi/di luar konteks.

Kategori "catat": "Makanan & Minuman","Belanja Harian","Transport","Tagihan","Kesehatan","Hiburan","Lainnya".

Pesan user: "{$message}"

FORMAT (semua field, yg nggak relevan null):
{"intent":"...","type":null,"amount":null,"category":null,"note":null,"wallet":null,"period":null,"task":null,"remind_at":null,"repeat":null,"person":null,"day_of_month":null,"from_wallet":null,"to_wallet":null}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ];

        $raw = $this->callApi($payload);
        if ($raw === null) {
            return null;
        }

        return JsonExtractor::extract($raw);
    }

    public function transcribeAudio(string $base64Audio, string $mimeType): ?string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Transkripsi audio ini ke teks bahasa Indonesia. Balas hanya teks transkripsi, tanpa penjelasan.'],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data'      => $base64Audio,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->callApi($payload);
    }

    private function callApi(array $payload, int $retries = 2): ?string
    {
        $url = "{$this->baseUrl}:generateContent?key={$this->apiKey}";

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }

                Log::warning('GeminiService::callApi non-2xx', [
                    'attempt' => $attempt,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('GeminiService::callApi exception', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
            }

            if ($attempt < $retries) {
                sleep(1);
            }
        }

        return null;
    }
}
