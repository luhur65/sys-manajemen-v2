<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * M-07 — melengkapi `log_activity` supaya bisa dipakai sebagai audit trail.
 *
 * Tabel ini lahir di CI3 hanya untuk mencatat login berhasil: tidak ada penanda
 * jenis kejadian, tidak ada tempat menyimpan nilai lama/baru, dan tidak ada
 * kolom waktu yang ditulis aplikasi. Empat kolom di bawah menutup ketiganya.
 *
 * Ditulis sebagai SQL mentah, bukan lewat Forge, karena tabelnya sudah ada sejak
 * sebelum proyek ini memakai migrasi: bentuk pastinya berbeda antar server, dan
 * penjaga `COL_LENGTH(...) IS NULL` membuat migrasi ini aman dijalankan di
 * database yang sebagian kolomnya sudah terlanjur ada.
 */
class AuditTrailLogActivity extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        // Jenis peristiwa: LOGIN_SUCCESS, LOGIN_FAILED, DATA_UPDATE, dst.
        // Tanpa kolom ini log gagal-login tidak bisa dipisahkan dari log lain
        // kecuali dengan LIKE pada teks pesan.
        $this->query(
            "IF COL_LENGTH('log_activity', 'event') IS NULL
                 ALTER TABLE log_activity ADD [event] VARCHAR(40) NULL"
        );

        // Login gagal terjadi saat sesi masih anonim, jadi `user_id` pasti 0.
        // Userid yang dicoba adalah satu-satunya petunjuk akun mana yang disasar.
        //
        // Namanya `userid_login`, bukan `userid`, justru karena tabel ini sudah
        // punya `user_id`. Dua kolom yang bedanya satu garis bawah dan sama-sama
        // berarti "user" adalah jebakan diam: query yang mengambil kolom keliru
        // tetap memberi hasil, hanya isinya bukan yang dikira penulisnya.
        // `user_id` = userpk (int, 0 saat anonim); `userid_login` = string login.
        $this->query(
            "IF COL_LENGTH('log_activity', 'userid_login') IS NULL
                 ALTER TABLE log_activity ADD userid_login VARCHAR(50) NULL"
        );

        // Nilai lama/baru dan pengenal baris, disimpan sebagai JSON. NVARCHAR(MAX)
        // dipilih agar satu perubahan besar tidak terpotong diam-diam.
        $this->query(
            "IF COL_LENGTH('log_activity', 'context') IS NULL
                 ALTER TABLE log_activity ADD context NVARCHAR(MAX) NULL"
        );

        // Waktu kejadian. Tabel ini sebelumnya TIDAK punya kolom waktu sama
        // sekali — sembilan kolom aslinya (id, user_id, module, controller,
        // action, message, message_error, ip, detect) tidak satu pun menyimpan
        // kapan. Seluruh riwayat login yang sudah terkumpul karena itu hanya
        // bisa diurutkan lewat `id`, dan tidak bisa dijawab pertanyaan "kapan".
        // Baris lama akan tetap NULL: tidak ada sumber untuk mengisinya surut.
        $this->query(
            "IF COL_LENGTH('log_activity', 'created_at') IS NULL
                 ALTER TABLE log_activity ADD created_at DATETIME NULL"
        );

        // Penelusuran insiden selalu berbentuk "peristiwa X dalam rentang waktu Y"
        // (mis. mencari lonjakan LOGIN_FAILED). Tanpa indeks ini, tabel log yang
        // sekarang bertambah jauh lebih cepat harus dipindai seluruhnya.
        $this->query(
            "IF NOT EXISTS (
                 SELECT 1 FROM sys.indexes
                 WHERE name = 'IX_log_activity_event_created'
                   AND object_id = OBJECT_ID('log_activity')
             )
                 CREATE INDEX IX_log_activity_event_created
                     ON log_activity ([event], created_at)"
        );
    }

    public function down()
    {
        // Hanya indeks yang dilepas. Kolomnya sengaja dibiarkan: isinya adalah
        // jejak audit yang sudah terkumpul, dan rollback skema bukan alasan yang
        // sah untuk menghapus bukti. Kolom kosong tidak merugikan siapa pun;
        // baris log yang hilang tidak bisa dikembalikan.
        $this->query(
            "IF EXISTS (
                 SELECT 1 FROM sys.indexes
                 WHERE name = 'IX_log_activity_event_created'
                   AND object_id = OBJECT_ID('log_activity')
             )
                 DROP INDEX IX_log_activity_event_created ON log_activity"
        );
    }

    private function query(string $sql): void
    {
        $this->db->query($sql);
    }
}
