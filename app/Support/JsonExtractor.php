<?php

namespace App\Support;

class JsonExtractor
{
    public static function extract(string $text): ?array
    {
        // 1. Hapus markdown fence
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);

        // 2. Trim whitespace
        $text = trim($text);

        // 3. Cari posisi '{' pertama dan '}' terakhir
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        // 4. Ambil substring dari { sampai } (inklusif)
        $json = substr($text, $start, $end - $start + 1);

        // 5. json_decode -> array
        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // 6. Gagal
        return null;
    }
}
