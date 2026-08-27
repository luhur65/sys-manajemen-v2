<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Sso as SsoConfig;
use Throwable;

/**
 * Diagnosa pemetaan identitas SSO -> baris `tbluser`.
 *
 * Menjawab satu pertanyaan yang dari browser mustahil dibedakan: saat login SSO
 * ditolak dengan "Akun Anda belum terdaftar di SYS", apakah barisnya memang
 * tidak ada, ada lebih dari satu, atau aplikasi ini sebenarnya sedang menatap
 * database/kolom yang berbeda dari yang Anda periksa lewat SSMS.
 *
 * Perintah ini memakai konfigurasi dan koneksi yang sama persis dengan
 * App\Controllers\SsoAuth::resolveUser().
 *
 *   php spark sso:match 6351
 *   php spark sso:match dharmataspusat@gmail.com
 *   php spark sso:match            (tanpa nilai: hanya tampilkan konfigurasi)
 */
class CheckSsoMatch extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'sso:match';
    protected $description = 'Memeriksa konfigurasi pencocokan SSO dan menguji satu nilai terhadap tbluser.';
    protected $usage       = 'sso:match [nilai]';

    public function run(array $params)
    {
        $sso = config(SsoConfig::class);

        CLI::write('Konfigurasi pencocokan SSO', 'yellow');
        CLI::write(str_repeat('-', 60));
        CLI::write('  sso.enabled     = ' . ($sso->enabled ? 'true' : CLI::color('false', 'red')));
        CLI::write('  sso.matchClaim  = ' . ($sso->matchClaim === '' ? CLI::color('(KOSONG)', 'red') : $sso->matchClaim));
        CLI::write('  sso.matchColumn = ' . ($sso->matchColumn === '' ? CLI::color('(KOSONG)', 'red') : $sso->matchColumn));

        try {
            $db = \Config\Database::connect();
        } catch (Throwable $e) {
            CLI::error('Tidak bisa menyambung ke database default: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::newLine();
        CLI::write('Database yang benar-benar dipakai aplikasi ini', 'yellow');
        CLI::write(str_repeat('-', 60));
        CLI::write('  hostname = ' . $db->hostname . (empty($db->port) ? '' : ':' . $db->port));
        CLI::write('  database = ' . $db->getDatabase());

        $column = $sso->matchColumn;

        if ($column === '') {
            CLI::error('sso.matchColumn kosong — resolveUser() akan selalu menolak.');

            return EXIT_ERROR;
        }

        if (! $db->fieldExists($column, 'tbluser')) {
            CLI::newLine();
            CLI::error('Kolom tbluser.' . $column . ' TIDAK ADA di database ini.');
            CLI::write('Setiap login SSO akan ditolak. Periksa sso.matchColumn, atau', 'yellow');
            CLI::write('apakah database.default menunjuk server yang Anda kira.', 'yellow');

            return EXIT_ERROR;
        }

        CLI::write('  tbluser.' . $column . ' ada : ' . CLI::color('ya', 'green'));

        // Gambaran umum: berapa baris yang sudah punya nilai pada kolom itu.
        $terisi = $db->table('tbluser')
            ->where($column . ' IS NOT NULL', null, false)
            ->where($column . " <> ''", null, false)
            ->where($column . " <> '0'", null, false)
            ->countAllResults();

        CLI::write('  baris tbluser dengan ' . $column . ' terisi : ' . $terisi
            . ' dari ' . $db->table('tbluser')->countAllResults());

        $nilai = trim((string) ($params[0] ?? ''));

        if ($nilai === '') {
            CLI::newLine();
            CLI::write('Beri satu nilai untuk mengujinya, contoh: php spark sso:match 6351', 'yellow');

            return EXIT_SUCCESS;
        }

        CLI::newLine();
        CLI::write('Uji nilai: ' . $nilai, 'yellow');
        CLI::write(str_repeat('-', 60));

        // Penjaga yang sama dengan SsoAuth::resolveUser().
        if (is_numeric($nilai) && (float) $nilai <= 0) {
            CLI::write('  ' . CLI::color('ditolak', 'red') . ' — nilai numerik <= 0 bukan identitas yang sah.');

            return EXIT_ERROR;
        }

        $rows = $db->table('tbluser')
            ->where($column, $nilai)
            ->limit(3)
            ->get()
            ->getResultArray();

        $jml = count($rows);

        foreach ($rows as $row) {
            CLI::write(sprintf(
                '   userpk=%s | userid=%s | %s=%s',
                $row['userpk'] ?? '-',
                $row['userid'] ?? '-',
                $column,
                $row[$column] ?? '-'
            ));
        }

        CLI::newLine();

        if ($jml === 1) {
            CLI::write('HASIL: tepat 1 baris — login SSO untuk identitas ini akan BERHASIL.', 'green');

            return EXIT_SUCCESS;
        }

        if ($jml === 0) {
            CLI::error('HASIL: 0 baris — akan ditolak "Akun Anda belum terdaftar di SYS".');
            CLI::write('Petakan nilai ini ke satu baris tbluser lewat halaman User.', 'yellow');
        } else {
            CLI::error('HASIL: ' . $jml . '+ baris — ditolak karena ambigu.');
            CLI::write('Dua user tidak boleh memakai identitas yang sama; sisakan satu.', 'yellow');
        }

        return EXIT_ERROR;
    }
}
