<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * M-09 — output view harus lolos escaping sesuai konteksnya.
 *
 * Dua hal yang dijaga di sini, dan keduanya pernah salah:
 *
 * 1. Konteks JavaScript bukan konteks HTML. Nilai yang masuk ke dalam literal
 *    string JS tidak cukup di-escape sebagai HTML — di dalam <script> entitas
 *    HTML tidak pernah didekode, jadi esc($v) di sana malah merusak nilainya
 *    sekaligus memberi rasa aman yang keliru. Yang dipakai json_encode() dengan
 *    bendera JSON_HEX_*, sehingga literalnya lengkap dengan kutipnya sendiri.
 *
 * 2. Titik keluar yang paling berbahaya adalah yang menerima input mentah:
 *    $userpk pada useracl/index.php datang langsung dari query string.
 *
 * Tes ini menguji keputusan escaping-nya, bukan me-render view: view butuh
 * session, router, dan basis data yang tidak ada di unit test. Kalau ada view
 * yang kembali menjepit nilai dengan kutip manual, penjaganya ada di
 * testTidakAdaLagiFlashdataAtauSesiDalamKutipManual().
 */
final class ViewEscapingTest extends CIUnitTestCase
{
    /** Bendera yang dipakai seluruh view saat menulis nilai ke konteks JS. */
    private const JS_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

    /**
     * Payload yang mewakili tiap cara keluar dari konteks: menutup literal,
     * menutup blok skrip, dan menyisipkan tag baru.
     *
     * @return array<string, array{0: string}>
     */
    public static function payloads(): array
    {
        return [
            'kutip tunggal'   => ["'); alert(1); ('"],
            'kutip ganda'     => ['"); alert(1); ("'],
            'penutup skrip'   => ['</script><script>alert(1)</script>'],
            'tag img onerror' => ['<img src=x onerror=alert(1)>'],
            'ampersand'       => ['a & b'],
        ];
    }

    /**
     * @dataProvider payloads
     */
    public function testKonteksJavaScriptTidakBisaDitembus(string $payload): void
    {
        $literal = json_encode($payload, self::JS_FLAGS);

        // Tidak ada satu pun karakter yang bisa mengakhiri literal lebih awal
        // atau menutup blok <script> yang sedang berjalan.
        $isi = substr($literal, 1, -1);

        $this->assertStringNotContainsString("'", $isi);
        $this->assertStringNotContainsString('"', $isi);
        $this->assertStringNotContainsString('<', $isi);
        $this->assertStringNotContainsString('>', $isi);
        $this->assertStringNotContainsString('&', $isi);

        // Dan nilainya tetap utuh saat dibaca kembali sebagai JS.
        $this->assertSame($payload, json_decode($literal, true));
    }

    /**
     * @dataProvider payloads
     */
    public function testKonteksHtmlDanAtributTidakBisaDitembus(string $payload): void
    {
        foreach (['html', 'attr'] as $konteks) {
            $keluaran = esc($payload, $konteks);

            $this->assertStringNotContainsString('<', $keluaran, "konteks {$konteks}");
            $this->assertStringNotContainsString('>', $keluaran, "konteks {$konteks}");
            $this->assertStringNotContainsString('"', $keluaran, "konteks {$konteks}");
            $this->assertStringNotContainsString("'", $keluaran, "konteks {$konteks}");
        }
    }

    /**
     * esc() dengan konteks 'html' TIDAK boleh dianggap cukup untuk literal JS:
     * hasilnya berupa entitas yang tidak pernah didekode di dalam <script>,
     * sehingga pesan yang tampil ke pengguna jadi kacau. Ini alasan view
     * memakai json_encode() dan bukan esc() di sana.
     */
    public function testEscHtmlMerusakNilaiDiKonteksJavaScript(): void
    {
        $this->assertSame('&#039;', esc("'", 'html'));
        $this->assertNotSame("'", esc("'", 'html'));
    }

    /**
     * Penjaga regresi atas berkas view yang sebenarnya: begitu ada yang kembali
     * menulis '<?= sesuatu ?>' di dalam kutip manual pada blok skrip, pola di
     * bawah ini akan menangkapnya.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function titikKeluarView(): array
    {
        return [
            'useracl userpk dari query string' => ['app/Views/useracl/index.php', 'var userpk'],
            'home userid sesi'                 => ['app/Views/home.php', 'let userId'],
            'footer userid lockscreen'         => ['app/Views/partials/footer.php', 'sysmodern_lockscreen_userid'],
            'grafik flashdata error'           => ['app/Views/grafik/grafikbiayakantorbandinglaba.php', 'showDialog('],
        ];
    }

    /**
     * @dataProvider titikKeluarView
     */
    public function testTidakAdaLagiFlashdataAtauSesiDalamKutipManual(string $berkas, string $penanda): void
    {
        $jalur = ROOTPATH . $berkas;
        $this->assertFileExists($jalur);

        $baris = array_values(array_filter(
            file($jalur, FILE_IGNORE_NEW_LINES),
            static fn (string $b): bool => str_contains($b, $penanda)
        ));

        $this->assertNotSame([], $baris, "penanda '{$penanda}' tidak ditemukan di {$berkas}");

        foreach ($baris as $b) {
            if (! str_contains($b, '<?=')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/<\?=\s*json_encode\(/',
                $b,
                "nilai di konteks JS pada {$berkas} harus lewat json_encode(): {$b}"
            );
            $this->assertDoesNotMatchRegularExpression(
                '/[\'"]<\?=/',
                $b,
                "nilai di konteks JS pada {$berkas} tidak boleh dijepit kutip manual: {$b}"
            );
        }
    }
}
