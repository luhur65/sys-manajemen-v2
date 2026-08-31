<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Throttle\Throttler;

/**
 * Pembatas laju untuk endpoint autentikasi (H-04).
 *
 * Sebelumnya `Login::proses()`, `Login::unlock()`, dan `Login::forgotPassword()`
 * menerima percobaan tanpa batas. Satu-satunya penghitung yang ada tersimpan di
 * localStorage browser, jadi tidak berarti apa-apa bagi penyerang.
 *
 * Dibangun di atas `service('throttler')` bawaan CI4 (token bucket, disimpan di
 * cache — aplikasi ini memakai handler `file`, jadi tidak perlu infrastruktur
 * tambahan).
 *
 * Dua hal penting dalam pemakaiannya:
 *
 *  1. **Hitungan hanya bertambah saat GAGAL.** {@see self::retryAfter()} memakai
 *     `cost = 0` sehingga hanya mengintip, tidak mengambil token. Token baru
 *     berkurang lewat {@see self::hit()}. Akibatnya user yang selalu berhasil
 *     login tidak pernah menyentuh batas sama sekali — penting karena satu
 *     kantor bisa keluar lewat satu IP publik (NAT).
 *  2. **Dua ember sekaligus.** Per-akun ketat (pertahanan brute force yang
 *     sebenarnya) dan per-IP longgar (menangkap password spraying lintas akun
 *     tanpa mengunci satu kantor).
 */
final class LoginThrottle
{
    /**
     * [kapasitas, jendela-detik] untuk tiap ember.
     *
     * login  : 5 kegagalan/15 menit per akun  -> setelah itu 1 percobaan tiap 3 menit.
     *          20 kegagalan/15 menit per IP   -> 1 percobaan tiap 45 detik.
     * forgot : biayanya nyata (kirim email lewat akun Brevo perusahaan), jadi
     *          dibatasi walaupun requestnya "berhasil".
     */
    private const RULES = [
        'login'  => ['account' => [5, 900], 'ip' => [20, 900]],
        'forgot' => ['account' => [3, 3600], 'ip' => [10, 3600]],
    ];

    private Throttler $throttler;
    private CacheInterface $cache;

    public function __construct(?Throttler $throttler = null, ?CacheInterface $cache = null)
    {
        $this->throttler = $throttler ?? service('throttler');
        $this->cache     = $cache ?? service('cache');
    }

    /**
     * Berapa detik lagi pemanggil harus menunggu? `null` berarti boleh lanjut.
     *
     * Tidak mengurangi jatah — murni mengintip.
     */
    public function retryAfter(string $action, string $ip, string $account): ?int
    {
        $blocked = $this->blockedBucket($action, $ip, $account);

        return $blocked === null ? null : $blocked[1];
    }

    /**
     * Apakah penolakan ini perlu ditulis ke audit trail (M-07)?
     *
     * `true` hanya pada penolakan PERTAMA dalam satu jendela hukuman. Tanpa
     * gerbang ini satu baris `log_activity` ditulis per request, sehingga
     * penyerang yang menghantam endpoint login dapat menumbuhkan tabel itu
     * sebanyak yang ia mau — tabel yang justru dipakai untuk melacak dirinya.
     * Sinyalnya tidak hilang: yang dicatat adalah *episode* penolakan, dan
     * frekuensi mentah percobaan tetap terbaca dari baris `LOGIN_FAILED`.
     *
     * Penandanya dikunci pada **ember yang menahan**, bukan pada pasangan
     * ip+akun. Bedanya baru terasa saat password spraying: seribu akun berbeda
     * dari satu IP sama-sama ditahan oleh ember IP yang sama, jadi menghasilkan
     * satu baris — bukan seribu. Mengunci pada ip+akun akan membuat gerbang ini
     * tidak berguna persis pada serangan yang paling perlu ditahan.
     *
     * Tidak atomik: dua request bersamaan bisa lolos berdua dan menulis dua
     * baris. Untuk dedup catatan audit itu tidak merugikan, dan cache handler
     * `file` yang dipakai aplikasi ini memang tidak menyediakan add-if-absent.
     */
    public function announceOnce(string $action, string $ip, string $account): bool
    {
        $blocked = $this->blockedBucket($action, $ip, $account);

        if ($blocked === null) {
            return false;
        }

        [$bucketKey, $wait] = $blocked;

        $key = 'throttle-announced-' . md5($bucketKey);

        if ($this->cache->get($key) !== null) {
            return false;
        }

        // Sisa waktu tunggu menyusut tiap detik dan bisa tinggal 1 di ujung
        // jendela. Lantai 60 detik menjaga agar detik-detik terakhir setiap
        // siklus tidak berubah menjadi celah untuk menulis banyak baris lagi.
        $this->cache->save($key, 1, max($wait, 60));

        return true;
    }

    /**
     * Ember mana yang sedang menahan pemanggil ini, dan berapa lama lagi.
     *
     * @return array{0: string, 1: int}|null [kunci ember, detik menunggu]
     */
    private function blockedBucket(string $action, string $ip, string $account): ?array
    {
        foreach ($this->buckets($action, $ip, $account) as [$key, $capacity, $seconds]) {
            if (! $this->throttler->check($key, $capacity, $seconds, 0)) {
                return [$key, max(1, $this->throttler->getTokenTime())];
            }
        }

        return null;
    }

    /**
     * Catat satu percobaan gagal (atau satu aksi berbiaya, seperti kirim email).
     */
    public function hit(string $action, string $ip, string $account): void
    {
        foreach ($this->buckets($action, $ip, $account) as [$key, $capacity, $seconds]) {
            $this->throttler->check($key, $capacity, $seconds, 1);
        }
    }

    /**
     * Bersihkan hitungan setelah autentikasi berhasil, supaya user yang sempat
     * salah ketik beberapa kali tidak membawa sisa hukuman.
     *
     * Ember IP sengaja TIDAK dibersihkan: satu login sukses dari IP yang sedang
     * menyemprot ribuan akun tidak boleh menghapus jejak percobaan lainnya.
     */
    public function clear(string $action, string $account): void
    {
        $this->throttler->remove($this->key($action, 'account', $account));
    }

    /**
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private function buckets(string $action, string $ip, string $account): array
    {
        $rules = self::RULES[$action] ?? null;

        if ($rules === null) {
            throw new \InvalidArgumentException('Aksi throttle tidak dikenal: ' . $action);
        }

        $buckets = [];

        // Akun diperiksa lebih dulu supaya pesan tunggu yang dilaporkan ke user
        // berasal dari batas yang paling mungkin ia kenai.
        if ($account !== '') {
            $buckets[] = [$this->key($action, 'account', $account), $rules['account'][0], $rules['account'][1]];
        }

        $buckets[] = [$this->key($action, 'ip', $ip), $rules['ip'][0], $rules['ip'][1]];

        return $buckets;
    }

    /**
     * Kunci cache selalu di-hash. Selain menyamarkan userid, ini juga
     * menghindari karakter yang dilarang `Config\Cache::$reservedCharacters`
     * (`:` misalnya, yang selalu ada di alamat IPv6).
     */
    private function key(string $action, string $scope, string $value): string
    {
        return sprintf('throttle-%s-%s-%s', $action, $scope, md5(strtolower(trim($value))));
    }
}
