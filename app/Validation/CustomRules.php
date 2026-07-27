<?php

namespace App\Validation;

class CustomRules
{
    /**
     * Memeriksa apakah $str (tanggal saat ini) TIDAK LEBIH BESAR dari tanggal pembanding ($fields).
     * Format: MM-YYYY. Cocok untuk 'Bulan Dari' (tidak boleh > Bulan Sampai).
     */
    public function month_less_than_equal(string $str, string $fields, array $data, &$error = null): bool
    {
        if (empty($str) || empty($data[$fields])) {
            return true;
        }
        return $this->parse_month($str) <= $this->parse_month($data[$fields]);
    }

    /**
     * Memeriksa apakah $str (tanggal saat ini) TIDAK LEBIH KECIL dari tanggal pembanding ($fields).
     * Format: MM-YYYY. Cocok untuk 'Bulan Sampai' (tidak boleh < Bulan Dari).
     */
    public function month_greater_than_equal(string $str, string $fields, array $data, &$error = null): bool
    {
        if (empty($str) || empty($data[$fields])) {
            return true;
        }
        return $this->parse_month($str) >= $this->parse_month($data[$fields]);
    }

    private function parse_month($str)
    {
        return (int) (substr($str, 3, 4) . substr($str, 0, 2));
    }
}
