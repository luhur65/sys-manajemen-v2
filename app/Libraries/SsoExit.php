<?php

namespace App\Libraries;

use Config\Sso as SsoConfig;

/**
 * Satu tempat yang menjawab: setelah sesi sys-modern berakhir, penggunanya
 * diantar ke mana?
 *
 * Sesi bisa berakhir lewat tiga jalan, dan ketiganya harus menjawab sama:
 *
 *   1. Pengguna menekan logout — termasuk lock screen dan timer idle yang
 *      keduanya mengarah ke url yang sama.            (Login::logout)
 *   2. Sesi sys-modern sudah tidak ada saat request datang: habis sendiri,
 *      cookienya hilang, atau memang belum pernah login. (AuthFilter::before)
 *   3. Sesi SSO-nya dicabut di dashboard, lalu Single Logout mengakhiri sesi
 *      lokalnya.                        (AuthFilter::enforceSingleLogout)
 *
 * Kalau aturannya ditulis ulang di masing-masing tempat, cepat atau lambat
 * ketiganya berbeda — satu jalur sudah diarahkan ke SSO sementara dua lainnya
 * masih mendaratkan orang di halaman login lokal. Bagi pengguna itu terbaca
 * sebagai perilaku yang berubah-ubah tanpa pola, dan sumbernya sulit dicari
 * karena tidak ada satu pun tempat yang salah sendirian.
 */
final class SsoExit
{
    /**
     * Alamat tujuan setelah sesi berakhir.
     *
     * @param bool $fromSso Sesi yang baru saja berakhir lahir dari SSO, dan
     *                      karena itu dikembalikan ke dashboard walaupun
     *                      sso.logoutToSso masih mati. Hanya jalur logout yang
     *                      memakainya: di jalur (2) sesinya sudah tidak ada dan
     *                      tidak bisa ditanya lagi, dan di jalur (3) perilaku
     *                      lamanya justru halaman login (lihat AuthFilter).
     * @param string $code  Kode pesan untuk halaman login lokal (`?sso=<kode>`),
     *                      dipakai hanya kalau tujuannya memang halaman itu.
     *                      Wajib kode dari daftar tertutup di Login::ssoMessage(),
     *                      BUKAN teks bebas: view login merender pesannya tanpa
     *                      escaping.
     */
    public static function target(bool $fromSso = false, string $code = ''): string
    {
        $sso       = config(SsoConfig::class);
        $dashboard = rtrim(trim($sso->dashboardUrl), '/');

        if ($sso->enabled && $dashboard !== '' && ($sso->logoutToSso || $fromSso)) {
            return $dashboard;
        }

        // Halaman login lokal adalah default sekaligus jaring pengaman saat SSO
        // belum dikonfigurasi. Ini bukan sekadar kerapian: sesinya sudah
        // terlanjur berakhir sebelum baris ini dijalankan, jadi salah
        // konfigurasi baru menampakkan diri setelah pengguna benar-benar keluar
        // — dan alamat kosong akan meninggalkannya di halaman error tanpa jalan
        // kembali.
        return base_url('login' . ($code !== '' ? '?sso=' . $code : ''));
    }
}
