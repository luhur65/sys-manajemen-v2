# Pasangan kunci khusus test

Berkas di direktori ini **hanya dipakai oleh unit test** (`tests/unit/SsoTicketTest.php`)
dan tidak pernah dimuat aplikasi. Kunci ini dibuat sekali, sengaja ikut
di-commit, dan tidak punya nilai rahasia apa pun:

- `test-only-private.pem` — menandatangani tiket palsu di dalam test.
- `test-only-public.pem` — dipasang sebagai `sso.ticketPublicKey` di dalam test.
- `test-only-other-private.pem` — kunci asing, dipakai membuktikan tiket dari
  penandatangan lain ditolak.

Kunci SSO yang sesungguhnya tinggal di `.env` (public key saja; private key
hanya ada di auth-sso-api) dan tidak pernah masuk repositori.

Alasan memakai fixture, bukan `openssl_pkey_new()` saat test berjalan:
pembuatan kunci di PHP butuh `openssl.cnf`, yang tidak ada di sebagian
instalasi PHP Windows (Laragon). Verifikasi tanda tangan — yang sebenarnya
diuji — tidak membutuhkannya.
