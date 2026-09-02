<?php

namespace App\Libraries;

/**
 * Satu-satunya definisi "user ini boleh masuk atau tidak", dibaca dari
 * `tbluser.aktif`.
 *
 * Sebelum ini kolom `aktif` tidak pernah dibaca oleh jalur mana pun: login
 * password, login SSO, login biometrik, maupun Panel Casting semuanya
 * mengabaikannya. Akibatnya kolom itu jadi hiasan — dan lebih buruk lagi,
 * `MuserModel::saveUserData()` menyetel `aktif = 0` pada SETIAP user baru,
 * sehingga hampir seluruh baris bernilai 0 tanpa itu berarti apa-apa.
 *
 * Karena itu penegakannya dipasang di belakang saklar, persis pola yang dipakai
 * AclFilter:
 *
 *   security.userAktifEnforce = false  -> hanya dicatat (WOULD-DENY). DEFAULT.
 *   security.userAktifEnforce = true   -> user nonaktif benar-benar ditolak
 *
 * Default `false` disengaja dan penting. Menyalakan penegakan sebelum kolomnya
 * dirapikan akan mengunci hampir semua orang sekaligus — lewat SEMUA jalur,
 * termasuk password. Itu pemadaman, bukan pengetatan. Urutan yang benar:
 * rapikan datanya lewat halaman User, baca WOULD-DENY di log untuk memastikan
 * tidak ada yang tertinggal, baru setel saklarnya ke true.
 *
 * Daftar penandanya sengaja sama dengan SYS_INACTIVE_MARKERS di auth-sso-api,
 * supaya pre-check di sana dan penolakan di sini tidak pernah berbeda pendapat
 * tentang siapa yang nonaktif.
 */
final class UserStatus
{
    /** Nilai `aktif` yang berarti "tidak boleh masuk". Dibandingkan huruf besar. */
    private const PENANDA_NONAKTIF = ['0', 'N', 'T', 'TIDAK', 'NONAKTIF', 'FALSE'];

    /**
     * Apakah nilai kolom `aktif` ini berarti user boleh masuk?
     *
     * NULL dan string kosong dianggap AKTIF: kolom yang belum pernah diisi
     * berarti belum diputuskan, dan menolak orang karena data yang belum
     * diputuskan adalah kesalahan yang mahal.
     *
     * @param mixed $aktif
     */
    public static function aktif($aktif): bool
    {
        if ($aktif === null) {
            return true;
        }

        $teks = strtoupper(trim((string) $aktif));

        return $teks === '' || ! in_array($teks, self::PENANDA_NONAKTIF, true);
    }

    /** Apakah penegakan sedang menyala? */
    public static function ditegakkan(): bool
    {
        return filter_var(env('security.userAktifEnforce', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Haruskah login user ini ditolak?
     *
     * Selalu mencatat saat user tidak aktif — baik saat menolak maupun saat
     * masih mode transisi. Justru catatan WOULD-DENY itulah yang dipakai untuk
     * menyusun daftar siapa saja yang perlu dirapikan sebelum saklarnya
     * dinyalakan.
     *
     * Levelnya `error` di kedua keadaan, bukan `notice`: Config\Logger memakai
     * ambang 4 di production, jadi apa pun di bawah `error` tidak akan pernah
     * sampai ke berkas — dan mode transisi ini tidak ada gunanya kalau
     * catatannya justru tak terbaca di server tempat datanya dirapikan.
     *
     * @param mixed $aktif Nilai kolom `tbluser.aktif` milik user itu.
     */
    public static function menolak($aktif, string $userid, string $jalur): bool
    {
        if (self::aktif($aktif)) {
            return false;
        }

        $tegakkan = self::ditegakkan();

        log_message('error', sprintf(
            'AKTIF %s user=%s jalur=%s (tbluser.aktif=%s)',
            $tegakkan ? 'DENY' : 'WOULD-DENY',
            $userid !== '' ? $userid : '-',
            $jalur,
            var_export($aktif, true)
        ));

        return $tegakkan;
    }

    /** Pesan seragam untuk user yang ditolak karena nonaktif. */
    public static function pesan(): string
    {
        return 'Akun Anda sudah tidak aktif. Silakan hubungi administrator.';
    }
}
