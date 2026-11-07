<?php

namespace App\Services;

class SimplePdf
{
    public static function render(array $lines): string
    {
        $commands = ["BT", "/F1 16 Tf", "50 800 Td", self::text('NETCELL PAY'), "/F1 11 Tf"];
        $first = true;
        foreach ($lines as $line) {
            $parts = self::wrap((string) $line, 90);
            if ($parts === []) {
                $parts = [''];
            }
            foreach ($parts as $part) {
                if ($first) {
                    $commands[] = "0 -28 Td";
                    $first = false;
                } else {
                    $commands[] = "0 -16 Td";
                }
                $commands[] = self::text($part);
            }
        }
        $commands[] = "ET";
        $stream = implode("\n", $commands)."\n";

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Count 1 /Kids [3 0 R] >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        $objects[] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private static function text(string $value): string
    {
        $value = preg_replace('/[^\x20-\x7E]/', ' ', $value) ?? '';
        $value = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);

        return '('.$value.') Tj';
    }

    private static function wrap(string $value, int $width): array
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($value === '') {
            return [];
        }

        return explode("\n", wordwrap($value, $width, "\n", true));
    }
}
