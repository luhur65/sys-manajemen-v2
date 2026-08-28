<?php

namespace App\Libraries;

/**
 * Siapa yang tercatat sebagai penulis sebuah baris (`modifiedby`), dan siapa
 * yang sebenarnya menekan tombolnya.
 *
 * Dibuat sebagai kelas, bukan fungsi helper: sebagian pemanggilnya adalah Model
 * (MuserModel, RolesModel, MuserrolesModel) yang bisa dipakai dari CLI, tempat
 * helper yang biasanya dimuat BaseController belum tentu ada. Kelas PSR-4
 * selalu bisa di-autoload.
 *
 * Latar belakang: sebelum ini setiap pemanggil membaca
 * `session()->get('USERNAME')` — kunci huruf besar yang TIDAK PERNAH ditulis di
 * mana pun. Sesi sebenarnya menyimpan `sys_modernusername` (dan `username`
 * huruf kecil untuk kompatibilitas). Akibatnya `?? 'SYSTEM'` selalu menang, dan
 * seluruh kolom `modifiedby` terisi "SYSTEM" untuk SEMUA pengguna — bukan hanya
 * sesi hasil Panel Casting.
 */
class AuditUser
{
    /** Dipakai hanya kalau benar-benar tidak ada sesi (mis. seeder / CLI). */
    public const FALLBACK = 'SYSTEM';

    /**
     * Nama yang ditulis ke kolom `modifiedby`.
     *
     * Untuk sesi hasil Panel Casting (login-as), yang tercatat adalah **admin
     * IT penyetuju** — bukan admin yang menekan tombol, dan bukan pula user
     * yang sedang ditiru. Itu keputusan bisnis yang sudah dipakai HR, CRM, dan
     * DISC; auth-sso-api mengirimkannya lewat klaim `approverUsername` dan
     * memisahkannya dari `actorUsername` justru supaya kedua jejak itu tidak
     * tertukar. Menyimpang di sini akan membuat jejak sys-modern tidak
     * sebanding dengan aplikasi lain saat ditelusuri bersama.
     *
     * Pelaku sebenarnya tidak hilang — lihat actor().
     */
    public static function modifiedBy(): string
    {
        $penyetuju = self::teks(session()->get(SESSION_NAME . 'sso_approver'));

        if ($penyetuju !== null) {
            return $penyetuju;
        }

        return self::teks(session()->get(SESSION_NAME . 'username')) ?? self::FALLBACK;
    }

    /**
     * Username admin yang benar-benar menekan tombol, atau null pada sesi biasa.
     * Untuk log dan jejak audit — bukan untuk `modifiedby`.
     */
    public static function actor(): ?string
    {
        return self::teks(session()->get(SESSION_NAME . 'sso_actor'));
    }

    /** Username user yang sedang ditiru, atau null pada sesi biasa. */
    public static function impersonated(): ?string
    {
        return self::isImpersonating()
            ? self::teks(session()->get(SESSION_NAME . 'username'))
            : null;
    }

    public static function isImpersonating(): bool
    {
        return (bool) session()->get(SESSION_NAME . 'sso_impersonated');
    }

    /**
     * Keterangan satu baris untuk pesan log: cukup untuk menjawab "siapa yang
     * sebenarnya melakukan ini" tanpa perlu membuka tabel lain.
     */
    public static function describe(): string
    {
        if (! self::isImpersonating()) {
            return self::modifiedBy();
        }

        return sprintf(
            '%s (Panel Casting: atas nama %s, dijalankan %s)',
            self::modifiedBy(),
            self::impersonated() ?? '-',
            self::actor() ?? '-'
        );
    }

    /** @param mixed $nilai */
    private static function teks($nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $nilai = trim($nilai);

        return $nilai === '' ? null : $nilai;
    }
}
