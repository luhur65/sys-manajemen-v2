<?php
namespace App\Models;

use CodeIgniter\Model;
use Throwable;

// Migrated from CI3: application/models/Mlog.php


class MlogModel extends Model
{
    protected $table      = 'log_activity';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;

    /**
     * M-07: penanda jenis peristiwa.
     *
     * Sebelum ini tabel `log_activity` hanya berisi baris login berhasil, tanpa
     * satu pun kolom yang membedakan jenis kejadian. Akibatnya dua pertanyaan
     * paling dasar saat menelusuri insiden tidak bisa dijawab: "apakah ada yang
     * mencoba menebak password?" dan "siapa yang mengubah baris ini?". Nilai di
     * bawah dipakai sebagai kosakata tetap supaya log bisa disaring
     * (`WHERE event = 'LOGIN_FAILED'`), bukan diraba lewat LIKE pada pesan.
     */
    public const LOGIN_SUCCESS  = 'LOGIN_SUCCESS';
    public const LOGIN_FAILED   = 'LOGIN_FAILED';
    public const LOGIN_BLOCKED  = 'LOGIN_BLOCKED';
    public const LOGOUT         = 'LOGOUT';
    public const UNLOCK_SUCCESS = 'UNLOCK_SUCCESS';
    public const UNLOCK_FAILED  = 'UNLOCK_FAILED';
    public const DATA_CREATE    = 'DATA_CREATE';
    public const DATA_UPDATE    = 'DATA_UPDATE';
    public const DATA_DELETE    = 'DATA_DELETE';

    /**
     * Kunci yang nilainya TIDAK boleh ikut tersimpan di kolom `context`.
     *
     * Isi form CRUD dicatat apa adanya supaya nilai lama/baru bisa dibandingkan;
     * tanpa daftar ini, hash password dan token reset akan ikut mengendap di
     * tabel log yang jangkauan bacanya lebih luas daripada tabel asalnya.
     * Dicocokkan tanpa memandang besar-kecil huruf.
     */
    private const REDAKSI = [
        'password', 'password1', 'password2', 'password3', 'passwordlama',
        'passwordbaru', 'passwd', 'pass', 'token', 'secret', 'apikey', 'api_key',
        'credentialid', 'credentialpublickey', 'csrf_test_name',
    ];

    /**
     * Nama kolom `log_activity` yang benar-benar ada di database.
     *
     * Kolom audit baru (`event`, `userid_login`, `context`, `created_at`) dibawa oleh
     * migrasi AuditTrailLogActivity. Selama migrasi itu belum dijalankan,
     * payload disaring memakai daftar ini supaya insert tidak gagal — mencatat
     * seadanya jauh lebih baik daripada membuat login ikut gagal.
     *
     * @var list<string>|null
     */
    private static ?array $kolomTabel = null;

    private $CI;
    public function __construct() {
        parent::__construct();
        // $this->CI =& get_instance();
        // $this->database=$this->CI->load->database('dbglobal', TRUE);
    }

    /**
     * Catat satu peristiwa ke `log_activity`.
     *
     * @param string               $event        Salah satu konstanta di kelas ini.
     * @param string|null          $message      Deskripsi bisnis, bukan nama method.
     * @param array<string, mixed> $context      Data pendukung (id, nilai lama/baru).
     * @param string|null          $messageError Pesan galat bila peristiwanya gagal.
     */
    public function saveLog(string $event, ?string $message = null, array $context = [], ?string $messageError = null): void
    {
        try {
            // Dipanggil juga dari jalur yang tidak lewat BaseController (mis.
            // filter atau CLI), tempat helper ini belum tentu sudah dimuat.
            // Ikut masuk try bersama ip()/detect(): keduanya bersandar pada
            // service('request'), yang bentuknya berbeda di luar HTTP.
            helper('global_helper');

            $router = service('router');

            // `user_id` di bawah adalah pemilik akun — dan pada sesi Panel Casting
            // itu justru orang yang TIDAK melakukan apa-apa. Tanpa jejak berikut,
            // baris log ini terbaca seolah-olah dia sendiri yang mengerjakannya,
            // dan tidak ada apa pun di tabel ini yang bisa membantahnya.
            if (\App\Libraries\AuditUser::isImpersonating()) {
                $jejak   = '[' . \App\Libraries\AuditUser::describe() . ']';
                $message = ! empty($message) ? $message . ' ' . $jejak : $jejak;
            }

            $dataActivity = [
                // `user_id` = userpk (int). `userid_login` = string login-nya.
                // Namanya sengaja tidak `userid`: bersebelahan dengan `user_id`
                // di tabel yang sama, keduanya akan terbaca sama-sama "user" dan
                // query yang salah ambil tetap memberi hasil yang tampak wajar.
                'user_id'       => session()->get(SESSION_NAME.'userpk') ?: 0,
                // Pada LOGIN_FAILED sesi masih anonim, jadi satu-satunya petunjuk
                // akun yang disasar adalah userid yang dikirim pemanggil.
                'userid_login'  => self::potong(session()->get(SESSION_NAME.'userid') ?: ($context['userid'] ?? null), 50),
                'event'         => $event,
                'module'        => basename(FCPATH),
                'controller'    => $router->controllerName(),
                'action'        => $router->methodName(),
                'message'       => !empty($message) ? $message : null,
                'message_error' => !empty($messageError) ? $messageError : null,
                'context'       => $this->encodeContext($context),
                // Dipotong ke panjang IPv6 terpanjang. ip() membaca header
                // X-Forwarded-For yang dikendalikan klien: tanpa batas ini,
                // header raksasa membuat INSERT gagal — dan percobaan login
                // yang mengirimnya justru tidak meninggalkan jejak.
                'ip'            => self::potong(ip(), 45),
                'detect'        => detect(),
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $this->db->table($this->table)->insert($this->filterKolom($dataActivity));
        } catch (Throwable $e) {
            // Audit trail yang gagal ditulis tidak boleh ikut menggagalkan aksi
            // penggunanya — login yang sah harus tetap jadi, data yang sah harus
            // tetap tersimpan. Kegagalannya tetap terlihat di writable/logs
            // supaya tidak diam-diam hilang.
            log_message('error', sprintf(
                'Audit trail gagal ditulis (event=%s): %s',
                $event,
                $e->getMessage()
            ));
        }
    }

    /**
     * Bandingkan kondisi sebelum dan sesudah, sisakan yang benar-benar berubah.
     *
     * Menyimpan seluruh baris dua kali membuat kolom `context` didominasi nilai
     * yang tidak bergerak, dan justru menyembunyikan satu kolom yang diubah.
     *
     * @param array<string, mixed>|object|null $before
     * @param array<string, mixed>|object|null $after
     *
     * @return array<string, array{dari: mixed, ke: mixed}>
     */
    public static function changes($before, $after): array
    {
        $before = self::toArray($before);
        $after  = self::toArray($after);

        $perubahan = [];

        foreach ($after as $kolom => $nilaiBaru) {
            $nilaiLama = $before[$kolom] ?? null;

            // Longgar (!=) disengaja: nilai dari database sering bertipe string
            // sedangkan nilai dari POST bertipe int untuk angka yang sama.
            if ($nilaiLama != $nilaiBaru) {
                $perubahan[$kolom] = ['dari' => $nilaiLama, 'ke' => $nilaiBaru];
            }
        }

        return $perubahan;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): ?string
    {
        if ($context === []) {
            return null;
        }

        $json = json_encode(self::redaksi($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function redaksi(array $data): array
    {
        foreach ($data as $kunci => $nilai) {
            if (is_string($kunci) && in_array(strtolower($kunci), self::REDAKSI, true)) {
                // Keberadaan perubahan tetap terlihat, isinya tidak.
                $data[$kunci] = ($nilai === null || $nilai === '' || $nilai === []) ? null : '***';
                continue;
            }

            if (is_array($nilai)) {
                $data[$kunci] = self::redaksi($nilai);
                continue;
            }

            if (is_object($nilai)) {
                $data[$kunci] = self::redaksi(self::toArray($nilai));
            }
        }

        return $data;
    }

    /**
     * Buang kolom yang belum ada di tabel supaya insert tetap berhasil pada
     * database yang migrasi audit-nya belum dijalankan.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function filterKolom(array $data): array
    {
        if (self::$kolomTabel === null) {
            try {
                self::$kolomTabel = array_map('strtolower', $this->db->getFieldNames($this->table));
            } catch (Throwable $e) {
                self::$kolomTabel = [];
            }
        }

        // Daftar kosong berarti metadata tabel tidak terbaca; jangan menebak-nebak
        // dan jangan pula membuang apa pun.
        if (self::$kolomTabel === []) {
            return $data;
        }

        return array_filter(
            $data,
            static fn ($kolom) => in_array(strtolower((string) $kolom), self::$kolomTabel, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Potong nilai agar muat di kolomnya. Baris log yang sebagian terpotong
     * masih bisa dipakai; baris yang gagal ditulis sama sekali tidak.
     *
     * @param mixed $nilai
     */
    private static function potong($nilai, int $panjang): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return mb_substr((string) $nilai, 0, $panjang);
    }

    /**
     * @param array<string, mixed>|object|null $nilai
     *
     * @return array<string, mixed>
     */
    private static function toArray($nilai): array
    {
        if (is_array($nilai)) {
            return $nilai;
        }

        if (is_object($nilai)) {
            return get_object_vars($nilai);
        }

        return [];
    }
}
