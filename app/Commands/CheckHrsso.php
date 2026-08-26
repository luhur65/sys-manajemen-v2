<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Diagnosa koneksi ke database HR (grup `hrsso`) — sumber lookup karyawan di
 * halaman User.
 *
 * Ada supaya kegagalan lookup bisa dibedakan penyebabnya. Dari sisi browser
 * setiap sebab terlihat sama: `{"records":0,"rows":[]}`. Perintah ini memakai
 * config yang sama persis dengan aplikasi, lalu menaikkan kembali kesalahan
 * yang di controller sengaja ditelan supaya halaman User tidak ikut mati.
 *
 * Jalankan di server yang bermasalah:
 *   php spark hrsso:check
 */
class CheckHrsso extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'hrsso:check';
    protected $description = 'Memeriksa koneksi & hak baca ke database HR (grup hrsso) untuk lookup karyawan.';

    public function run(array $params)
    {
        CLI::write('Diagnosa koneksi hrsso', 'yellow');
        CLI::write(str_repeat('-', 60));

        $settings = config(\Config\Database::class)->hrsso ?? null;

        if ($settings === null) {
            CLI::error('Grup `hrsso` tidak ada di app/Config/Database.php.');

            return EXIT_ERROR;
        }

        // 1. Apa yang benar-benar terbaca dari .env
        CLI::write('1. Nilai config yang terbaca aplikasi:', 'green');

        foreach (['hostname', 'port', 'database', 'username', 'DBDriver'] as $key) {
            $value = (string) ($settings[$key] ?? '');
            CLI::write(sprintf('   %-10s = %s', $key, $value === '' ? CLI::color('(KOSONG)', 'red') : $value));
        }

        $password = (string) ($settings['password'] ?? '');
        CLI::write(sprintf(
            '   %-10s = %s',
            'password',
            $password === '' ? CLI::color('(KOSONG)', 'red') : '(terisi, ' . strlen($password) . ' karakter)'
        ));

        if (trim((string) ($settings['username'] ?? '')) === '') {
            CLI::newLine();
            CLI::error('username kosong — lookup karyawan dimatikan sebelum mencoba koneksi.');
            CLI::write('Periksa baris `database.hrsso.username` di .env yang dipakai server ini.', 'yellow');

            return EXIT_ERROR;
        }

        // 2. Ekstensi driver
        CLI::newLine();
        CLI::write('2. Ekstensi PHP:', 'green');
        CLI::write('   sqlsrv terpasang : ' . (extension_loaded('sqlsrv')
            ? CLI::color('ya', 'green')
            : CLI::color('TIDAK', 'red')));

        // 3. Koneksi
        CLI::newLine();
        CLI::write('3. Koneksi:', 'green');

        try {
            $db = \Config\Database::connect('hrsso');
            $db->initialize();
            CLI::write('   ' . CLI::color('berhasil tersambung', 'green'));
        } catch (Throwable $e) {
            CLI::write('   ' . CLI::color('GAGAL', 'red') . ' — ' . $e->getMessage());
            CLI::newLine();
            CLI::write('Kemungkinan: host tidak terjangkau dari server ini, port 1433 tertutup,', 'yellow');
            CLI::write('login/password salah, atau login tidak punya akses ke database itu.', 'yellow');

            return EXIT_ERROR;
        }

        // 4. Hak baca per tabel — lookup butuh keempatnya
        CLI::newLine();
        CLI::write('4. Hak baca per tabel:', 'green');

        $gagal = false;

        foreach (['karyawan', 'cabang', 'jabatan', 'parameter'] as $tabel) {
            try {
                $jml = $db->table($tabel)->countAllResults();
                CLI::write(sprintf('   %-10s : %s (%d baris)', $tabel, CLI::color('bisa dibaca', 'green'), $jml));
            } catch (Throwable $e) {
                $gagal = true;
                CLI::write(sprintf('   %-10s : %s — %s', $tabel, CLI::color('GAGAL', 'red'), $e->getMessage()));
            }
        }

        if ($gagal) {
            CLI::newLine();
            CLI::error('Ada tabel yang tidak terbaca. Lookup butuh SELECT pada keempatnya.');

            return EXIT_ERROR;
        }

        // 5. Baris parameter penanda AKTIF — tanpa ini lookup selalu kosong
        CLI::newLine();
        CLI::write('5. Parameter STATUS AKTIF:', 'green');

        $row = $db->table('parameter')
            ->select('id')
            ->where('grp', 'STATUS AKTIF')
            ->where('text', 'AKTIF')
            ->get()
            ->getRow();

        if ($row === null) {
            CLI::write('   ' . CLI::color('TIDAK DITEMUKAN', 'red') . ' — grp="STATUS AKTIF" text="AKTIF"');
            CLI::error('Lookup akan selalu kosong tanpa baris ini.');

            return EXIT_ERROR;
        }

        CLI::write('   id AKTIF = ' . $row->id);

        // 6. Hasil akhir: persis query yang dipakai lookup
        CLI::newLine();
        CLI::write('6. Karyawan aktif (yang akan tampil di lookup):', 'green');

        $jml = $db->table('karyawan')->where('statusaktif', (int) $row->id)->countAllResults();
        CLI::write('   ' . $jml . ' baris');

        CLI::newLine();

        if ($jml === 0) {
            CLI::error('Terhubung dan berhak baca, tapi tidak ada karyawan berstatus AKTIF.');

            return EXIT_ERROR;
        }

        CLI::write('Semua pemeriksaan lolos — lookup karyawan seharusnya berisi.', 'green');

        return EXIT_SUCCESS;
    }
}
