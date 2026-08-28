<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi klien SSO (auth-sso / auth-sso-api).
 *
 * sys-modern berperan sebagai *consuming app*, sama seperti HR dan CRM: server
 * SSO (auth-sso-api) yang menerbitkan tiket, sys-modern hanya memverifikasinya.
 * Karena tiket ditandatangani RS256, sys-modern cukup memegang PUBLIC key —
 * dengan kunci ini tiket tidak bisa dibuat, hanya diperiksa. Lihat
 * D:\project-next\sso\docs\plans\2026-07-14-sso-signed-jwt-ticket-design.md.
 *
 * Semua nilai di bawah diisi lewat .env dengan awalan `sso.`, contoh:
 *   sso.enabled = true
 *   sso.ticketPublicKey = MIIBIjANBgkq...\n...
 */
class Sso extends BaseConfig
{
    /**
     * Saklar utama. Selama false, route callback menolak semua tiket dan tombol
     * "Masuk dengan SSO" tidak dirender — instalasi yang belum dikonfigurasi
     * tidak menyisakan endpoint autentikasi setengah jadi.
     */
    public bool $enabled = false;

    /**
     * Kode aplikasi ini di sisi SSO. Nilainya jadi klaim `aud` pada tiket, dan
     * harus sama dengan key di TICKET_RELAY_APPS milik auth-sso-api serta
     * `code` di constants/apps.ts milik auth-sso. Pemeriksaan `aud` inilah yang
     * membuat tiket untuk HR/CRM tidak bisa dipakai di sini.
     */
    public string $appCode = 'sys';

    /** Klaim `iss` yang wajib ada pada tiket (SSO_TICKET_ISSUER di auth-sso-api). */
    public string $issuer = 'auth-sso';

    /**
     * PUBLIC key RS256 (SPKI). Boleh PEM utuh, boleh badan base64 saja dengan
     * newline ditulis sebagai `\n` — persis format yang dipakai auth-sso-api
     * dan crm-nest, supaya satu nilai bisa disalin apa adanya antar aplikasi.
     * JANGAN pernah menaruh private key di sini.
     */
    public string $ticketPublicKey = '';

    /** Toleransi selisih jam (detik) saat memeriksa exp/nbf/iat. */
    public int $leeway = 60;

    /**
     * Umur simpan nonce `jti` (detik). Harus lebih panjang dari TICKET_TTL_SECONDS
     * di auth-sso-api ditambah toleransi jam, supaya tiket yang belum kedaluwarsa
     * tidak bisa dipakai dua kali.
     */
    public int $nonceTtl = 300;

    /** URL dashboard auth-sso, tujuan tombol "Masuk dengan SSO" dan logout SSO. */
    public string $dashboardUrl = '';

    /** Base URL auth-sso-api, dipakai untuk polling Single Logout. */
    public string $apiBaseUrl = '';

    /**
     * Secret bersama untuk endpoint Single Logout (header `x-slo-secret`).
     * Harus sama dengan SLO_INTROSPECT_SECRET di auth-sso-api.
     */
    public string $sloSecret = '';

    /**
     * Jeda (detik) antar pemeriksaan liveness sesi SSO. Hasil pemeriksaan
     * disimpan di cache selama rentang ini, jadi satu request per rentang —
     * bukan satu request per halaman.
     */
    public int $sloPollSeconds = 60;

    /**
     * Klaim tiket yang dipakai mencocokkan pengguna: 'karyawanId' atau 'email'.
     *
     * 'karyawanId' adalah pilihan yang benar: id master karyawan yang sama yang
     * dipakai HR dan CRM, dan satu-satunya identitas yang benar-benar diisi
     * konsisten di semua direktori. auth-sso-api mengambil kesimpulan yang sama
     * lebih dulu (lihat assertAppAccountExists()): orang yang sama rutin punya
     * alamat email berbeda, placeholder, atau tidak punya sama sekali.
     *
     * Defaultnya tetap 'email' supaya instalasi yang `tbluser`-nya belum
     * dipetakan tidak langsung menolak seluruh pengguna SSO begitu SSO dinyalakan.
     * Tukar ke 'karyawanId' setelah kolomnya terisi — periksa kesiapannya dengan
     * `php spark sso:match <karyawanid>`.
     *
     * Perhatikan besar-kecil hurufnya: klaimnya `karyawanId` (huruf I besar),
     * kolomnya `karyawanid` (semua kecil).
     */
    public string $matchClaim = 'email';

    /**
     * Kolom `tbluser` yang dicocokkan dengan klaim di atas.
     *
     * Nilainya harus 'karyawanid' saat matchClaim = 'karyawanId'. Kolomnya
     * `int NULL DEFAULT 0`; 0 berarti "belum dipetakan" dan sengaja ditolak
     * sebagai identitas oleh SsoAuth::resolveUser().
     */
    public string $matchColumn = 'email';

    /**
     * Saklar login lokal (userid/password, Quick Login biometrik dari halaman
     * login, dan seluruh alur reset password).
     *
     * true  = jalan berdampingan dengan SSO. Default, dan yang benar selama masa
     *         transisi: kalau SSO bermasalah, masih ada jalan masuk untuk
     *         memperbaikinya.
     * false = SSO satu-satunya cara masuk. Form password disembunyikan DAN
     *         endpoint-nya menolak — penyembunyian di view saja tidak menutup
     *         apa pun, POST langsung ke login/proses tetap akan lolos.
     *
     * Yang TIDAK ikut dimatikan: unlock lock screen memakai biometrik. Itu
     * bukan jalur login melainkan penegasan ulang sesi yang sudah ada, dan
     * mematikannya akan mengurung pengguna di layar terkunci.
     */
    public bool $passwordLoginEnabled = true;
}
