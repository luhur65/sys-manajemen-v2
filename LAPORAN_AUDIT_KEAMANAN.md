# Laporan Audit Keamanan — sys-modern

**Standar acuan:** `SecurityAgent.md` (Coding Standard Internal)
**Target:** `D:\php-project\sys-modern` — CodeIgniter 4.7.4 / PHP 8.2 / SQL Server
**Tanggal:** 19 Agustus 2026
**Metode:** Static source review (tanpa perubahan source code)

---

## 1. Ringkasan Eksekutif

**Temuan saat audit (19 Agustus 2026):**

| Severity | Jumlah | Selesai | Sebagian | Masih terbuka |
|---|---|---|---|---|
| Critical | 5 | **5** | – | 0 |
| High | 8 | 4 | 1 | 3 |
| Medium | 9 | 2 | – | 7 |
| Low / Info | 6 | – | – | 6 |
| **Total** | **28** | **11** | **1** | **16** |

**Security Score saat audit: 32 / 100**

Aplikasi memiliki fondasi yang baik pada beberapa area (bcrypt, WebAuthn, auto-routing dimatikan, error handling produksi, dependency mutakhir). Namun saat audit dilakukan, tiga kontrol keamanan paling fundamental — **CSRF, Access Control, dan parameterisasi SQL** — tidak aktif atau tidak diterapkan sama sekali. Kombinasi ketiganya membuat aplikasi dapat dieksploitasi oleh siapa pun yang memiliki satu akun valid tingkat terendah.

**Perkembangan per 31 Agustus 2026:** ketiga kontrol fundamental itu sudah ditegakkan — CSRF aktif secara global (C-03), ACL dieksekusi di lapisan filter (C-02), dan seluruh jalur SQL dinamis memakai whitelist + parameter (C-01, H-03). **Seluruh temuan Critical tertutup.** Yang paling menonjol di sisa daftar: **H-08** (cookie tanpa `Secure`, HTTPS belum dipaksakan) dan **H-06** (kebijakan password lemah) — keduanya menyentuh perlindungan kredensial, jadi layak diprioritaskan berikutnya. **H-07** (hash MD5 legacy masih diterima) berpindah status menjadi 🟢 **risiko diterima** per 31 Agustus 2026: jalur itu sengaja dibiarkan terbuka sebagai jalur migrasi bertahap MD5→bcrypt yang berjalan sendiri saat user login, karena tidak ada satu pun jalur di codebase yang menulis hash MD5 baru. Rincian per temuan ada di tabel §6.

---

## 2. Daftar Item yang SUDAH SESUAI Standar

| # | Poin Checklist | Implementasi | Lokasi |
|---|---|---|---|
| 1 | Password hashing | `password_hash(PASSWORD_BCRYPT)` | `app/Models/MloginModel.php:44`, `MuserModel.php:147` |
| 2 | `password_verify()` | Digunakan, tidak ada perbandingan `==` | `app/Models/MloginModel.php:39` |
| 3 | Migrasi hash legacy | MD5 lama otomatis di-upgrade ke bcrypt saat login sukses | `app/Models/MloginModel.php:42-51` |
| 4 | MFA / WebAuthn | Terimplementasi penuh (register, login, checkDevice) | `app/Controllers/Webauthn.php` |
| 5 | Lock screen | Ada, dengan verifikasi password ke server | `app/Controllers/Login.php:96`, `lockscreen.js` |
| 6 | Token reset password | Disimpan sebagai SHA-256, dibanding dengan `hash_equals()`, expiry 1 jam, single-use (dihapus setelah dipakai) | `app/Controllers/Login.php:246-262, 288-300` |
| 7 | Auto-routing | **Dimatikan** — semua route didefinisikan eksplisit | `app/Config/Routes.php:13`, `Routing.php:97` |
| 8 | `permittedURIChars` | Dibatasi ke `a-z 0-9~%.:_\-` | `app/Config/App.php` |
| 9 | Debug Mode produksi | `display_errors=0`, `CI_DEBUG=false`, halaman error generik | `app/Config/Boot/production.php` |
| 10 | Logger threshold | Dibatasi level 4 di produksi | `app/Config/Logger.php:42` |
| 11 | Information disclosure (API) | Detail exception hanya keluar saat `ENVIRONMENT === 'development'` | `app/Controllers/Webauthn.php:28-31` |
| 12 | `.env` tidak ter-commit | Ter-cover `.gitignore:44`; file template `env` bersih dari secret asli | `.gitignore` |
| 13 | Dependency Security | CI 4.7.4, PHP ^8.2, lbuchs/webauthn 2.2.0, laminas-escaper 2.18.0 — semua mutakhir, tidak ada advisory aktif; tidak ada paket deprecated | `composer.json`, `composer.lock` |
| 14 | CORS | Default deny — `allowedOrigins` kosong, `supportsCredentials=false` | `app/Config/Cors.php` |
| 15 | HTTP Header: HSTS | `max-age=31536000; includeSubDomains` | `app/Filters/SecurityHeaders.php:44` |
| 16 | HTTP Header: X-Frame-Options | `SAMEORIGIN` | `app/Filters/SecurityHeaders.php:49` |
| 17 | HTTP Header: X-Content-Type-Options | `nosniff` | `app/Filters/SecurityHeaders.php:54` |
| 18 | HTTP Header: Referrer-Policy | `strict-origin-when-cross-origin` | `app/Filters/SecurityHeaders.php:59` |
| 19 | HTTP Header: Permissions-Policy | `camera=(), microphone=(), geolocation=()` | `app/Filters/SecurityHeaders.php:64` |
| 20 | Cookie HttpOnly | `true` | `app/Config/Cookie.php` |
| 21 | Cookie SameSite | `Lax` | `app/Config/Cookie.php` |
| 22 | Session destroy saat logout | `session()->destroy()` dipanggil | `app/Controllers/Login.php:90` |
| 23 | Session timeout | `expiration = 7200` (2 jam) | `app/Config/Session.php` |
| 24 | XSS — escaping per konteks | `esc()` dipakai 138x di views (dari 64x saat audit) + `json_encode()` untuk seluruh literal JavaScript; laminas-escaper terpasang. Diperbaiki 31 Agu 2026 lewat M-09 | `app/Views/**`, `app/Helpers/my_helper.php` |
| 25 | SQLi — whitelist nama tabel | `$cabang` dipetakan lewat array whitelist, bukan konkatenasi | `app/Models/MomsetModel.php:21-32`, `MtruckingtradoluarModel.php:17-38`, `MtruckingtradoluartasModel.php:17-38` |
| 26 | SQLi — Query Builder | Model generasi baru (`MshippernewModel`, `Mtruckingtradoluar*`, `MuserrolesModel`, `MloginModel`) memakai Query Builder terparameterisasi | `app/Models/*` |
| 27 | Validation | CI4 Validation dipakai pada login, reset password, dan validasi periode bulan (custom rule reusable) | `Login.php:44`, `BaseController.php:174-194` |
| 28 | Path traversal (grid preference) | Nama file di-hash `md5($userid)`, input user tidak masuk ke path | `app/Controllers/GridPreference.php:31` |
| 29 | File Upload | **N/A** — aplikasi tidak memiliki fitur upload file sama sekali (tidak ada `getFile()`/`$_FILES`) | — |
| 30 | Logging login berhasil | `saveLog()` mencatat user, controller, action, IP, user-agent | `app/Models/MlogModel.php:25-39` |

---

## 3. Daftar Item yang BELUM SESUAI Standar

### CRITICAL

---

#### C-01 — SQL Injection pada Filter Grid (seluruh modul laporan)

**Status:** ✅ SUDAH DIPERBAIKI (22 Agustus 2026) — dua catatan penutup di akhir item ini: sisi parser filter dan sisi model (parameter `cabang`). Lingkup berdekatan `ORDER BY` (**H-03**) juga sudah selesai 22 Agustus 2026.

**Severity:** Critical
**Lokasi:** `app/Controllers/BaseController.php:74-171` (`operationAll`), duplikat di `Menu.php:40`, `NewShipper.php:91`, `NewShipperCabang.php:92`, dan seluruh controller `Omset*`, `Truckingtradoluar*`, `Overtopemkl*`, `Piutangemkl`

**Potongan Kode:**
```php
// app/Controllers/BaseController.php:88-89
$fieldName = $rule->field;
$fieldData = $rule->data; // TODO: Implement proper escaping if not using Query Builder
...
case "eq":
    $fieldOperation = " = '" . $fieldData . "'";   // konkatenasi mentah
    break;
case "in":
    $fieldOperation = " IN (" . $fieldData . ")";  // tanpa kutip sama sekali
    break;
...
$whereArray[] = $fieldName . $fieldOperation;      // nama kolom juga dari user
```

Alur lengkap sampai eksekusi:
```php
// app/Controllers/Omset.php:46-53
$filters = $this->request->getPost('filters');       // JSON dari client
$where = " AND (" . $this->operationAll($filters) . ")";

// app/Models/MomsetModel.php:37-39
$whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
$sql = $this->dbtruck->query("SELECT FTgl FROM $table $whereClause");
```

**Penjelasan:** Parameter POST `filters` adalah JSON yang dikirim jqGrid dan sepenuhnya dikendalikan client. Baik **nama kolom** (`rule.field`) maupun **nilai** (`rule.data`) dikonkatenasi langsung ke string SQL tanpa escaping, binding, atau whitelist. Operator `in` bahkan tidak membungkus nilai dengan kutip, sehingga payload bisa disuntik tanpa perlu keluar dari string literal. Komentar `TODO` di baris 89 menunjukkan ini disadari namun tidak pernah diperbaiki.

**Risiko:** Pembacaan seluruh isi 4 database (`transpor_tasmanagement`, `dbTruckWeb`, `dbtas`), termasuk tabel `tbluser` (hash password), `tblacl`, `password_resets`. Karena koneksi memakai akun **`sa` (sysadmin SQL Server)**, penyerang dapat pula menulis data, menjalankan `xp_cmdshell`, dan mengambil alih server database — eskalasi dari SQLi menjadi RCE tingkat OS.

**Contoh eksploitasi:**
```
POST /omset/grid
_search=true&filters={"groupOp":"AND","rules":[{"field":"1","op":"in","data":"1) UNION SELECT userid FROM tbluser--"}]}
```

**Cara Memperbaiki:** Whitelist nama kolom, dan gunakan binding parameter untuk nilai. Jangan pernah mengembalikan string SQL dari fungsi filter — kembalikan array kondisi + array binding.

**Contoh Kode Perbaikan:**
```php
protected function operationAll($filters, array $allowedFields): array
{
    $out = ['sql' => ' ', 'binds' => []];
    $decoded = json_decode((string) $filters);
    if (!$decoded || empty($decoded->rules)) {
        return $out;
    }

    // Whitelist operator -> template SQL (tidak ada input user di sini)
    $ops = [
        'eq' => '= ?',   'ne' => '!= ?',  'lt' => '< ?',   'gt' => '> ?',
        'le' => '<= ?',  'ge' => '>= ?',  'bw' => 'LIKE ?','cn' => 'LIKE ?',
        'ew' => 'LIKE ?','nc' => 'NOT LIKE ?',
    ];

    $groupOp = ($decoded->groupOp ?? 'AND') === 'OR' ? 'OR' : 'AND';
    $parts = $binds = [];

    foreach ($decoded->rules as $rule) {
        // 1. Nama kolom WAJIB lolos whitelist
        if (!in_array($rule->field, $allowedFields, true)) {
            continue;
        }
        // 2. Operator WAJIB lolos whitelist
        if (!isset($ops[$rule->op])) {
            continue;
        }

        $value = (string) $rule->data;
        $value = match ($rule->op) {
            'bw'       => $value . '%',
            'ew'       => '%' . $value,
            'cn', 'nc' => '%' . $value . '%',
            default    => $value,
        };

        // 3. Nama kolom di-quote oleh driver, nilai jadi bind parameter
        $parts[] = $this->db->protectIdentifiers($rule->field) . ' ' . $ops[$rule->op];
        $binds[] = $value;
    }

    if ($parts !== []) {
        $out['sql']   = ' AND (' . implode(" {$groupOp} ", $parts) . ')';
        $out['binds'] = $binds;
    }

    return $out;
}
```
```php
// Pemakaian di Model — query() dengan binding array
public function count(string $where, array $binds, string $cabang)
{
    $table = $this->getTableName($cabang);   // sudah whitelist
    return $this->dbtruck->query(
        "SELECT COUNT(*) AS total FROM {$table} WHERE 1=1 {$where}",
        $binds
    );
}
```

---

**Perbaikan yang Diterapkan — Sisi Parser Filter (22 Agustus 2026)**

Seluruh parser filter jqGrid dipusatkan ke satu library. Perbaikan tidak mengikuti bentuk
`['sql' => ..., 'binds' => [...]]` seperti contoh di atas — alasannya dijelaskan di bagian
"Mengapa escape, bukan bind" di bawah — namun ketiga kontrol yang diminta (whitelist kolom,
whitelist operator, nilai tidak pernah jadi sintaks SQL) tetap diterapkan penuh.

| Berkas | Perubahan |
|---|---|
| `app/Libraries/GridFilter.php` (**BARU**) | Parser tunggal untuk seluruh aplikasi. Tiga lapis: (1) nama kolom wajib lolos whitelist milik controller; (2) operator wajib lolos peta `OPERATORS`; (3) nilai selalu di-escape lewat koneksi yang menjalankan query-nya. Rule yang ditolak dibuang dan dicatat `log_message('warning', ...)`; `groupOp` dipaksa hanya `AND`/`OR`. |
| `app/Controllers/BaseController.php` | `operationAll()` (dulu 98 baris `switch` konkatenasi) kini hanya mendelegasi ke `GridFilter`, dengan tanda tangan `operationAll($filters, ?array $fieldMap = null, ?string $dbGroup = null): string`. Ditambah properti `$filterFields` + `$filterDbGroup` dan helper `escapeFilterValue()`. |
| 27 controller grid | Masing-masing mendeklarasikan `$filterFields` (whitelist kolom grid-nya sendiri) dan `$filterDbGroup`. Whitelist diturunkan dari `colModel` tiap view, lalu diverifikasi silang dengan skrip pembanding — semua kolom yang dapat difilter user tercakup. |
| 10 salinan parser | `operation()` di `Menu`, `NewShipper`, `NewShipperCabang`, `Parameter`, `Roles`, `Supirpercabang`, `Tracing`, `User`, `UserAcl` dan override `operationAll()` di `Piutangemkl` **dihapus seluruhnya**. Tidak ada lagi parser filter kedua di codebase. |
| `MtruckingtradoluartasModel`, `MtruckingtradoluardetailexpModel` | Kelompok `Truckingtradoluartas*` dan `Truckingtradoluardetailexp` memfilter lewat Query Builder, jadi nilainya memang sudah aman — tetapi **nama kolom** diserahkan mentah ke `$builder->like($field, $data)`. Ditambahkan konstanta `FILTER_FIELDS` sebagai whitelist sebelum pemanggilan tersebut. |

Perlakuan khusus tiap grid yang dulu tersebar sebagai `if` di dalam parser sekarang menjadi
data di whitelist, bukan logika:

```php
// app/Controllers/Piutangemkl.php
protected array $filterFields = [
    'FNTrans', 'FNInvoice', 'FNShipper', /* ... */
    'FTgl'     => "UPPER(FORMAT(FTgl, 'dd-MMM-yyyy'))",              // format tampilan
    'FNTgl'    => "(ltrim(rtrim(str(FThnJob)))+'-'+ ... )",           // kolom kalkulasi
    'FNominal' => ['sql' => 'CAST(FNominal AS VARCHAR)', 'numeric' => true], // buang pemisah ribuan
];
protected ?string $filterDbGroup = 'dbtruck';
```

Kunci array adalah nama kolom dari client dan wajib berupa identifier polos
(`^[A-Za-z_][A-Za-z0-9_]*$`); ekspresi SQL hanya boleh muncul di sisi nilai, yang ditulis
developer dan tidak pernah berasal dari request.

**Nilai di luar grid ikut ditutup.** Masih di endpoint yang sama, beberapa filter tambahan
dikonkatenasi mentah atau lewat `addslashes()` — sia-sia memperbaiki parser bila jalur ini
dibiarkan terbuka:

| Berkas | Sebelum | Sesudah |
|---|---|---|
| 6 controller `Omsetrekapmarketing*` | `" AND FBulan = '" . addslashes($bln) . "'"` | `" AND FBulan = " . $this->escapeFilterValue($bln)` |
| `Overtopemklmarketing.php` | `addslashes($marketing)` | `escapeFilterValue($marketing)` |
| `Piutangemkl.php` | `" AND FJnsJob LIKE '$jnsjob%'"` | `escapeFilterValue($jnsjob . '%')` |
| `Tracing.php` | `" AND cabang = '" . $cabang . "'"` | `escapeFilterValue($cabang)` |

`addslashes()` **tidak aman untuk SQL Server**: backslash bukan karakter escape di sana, jadi
`a'b` menjadi `a\'b` yang tetap menutup string literal. Yang benar adalah penggandaan kutip,
dan itulah yang dilakukan `escape()` milik driver SQLSRV.

**Mengapa escape, bukan bind:** `CodeIgniter\Database\Query::compileBinds()` →
`matchSimpleBinds()` tidak memakai prepared statement, melainkan menyubstitusi `?` dengan hasil
`escape()` dari koneksi yang sama. Jadi untuk driver ini escaping terpusat setara dengan
binding, sementara mengubah bentuk kembalian menjadi `['sql', 'binds']` akan memaksa perubahan
tanda tangan pada ~20 model (`count`, `get`, `getGrandTotal`, `excel`) beserta seluruh
pemanggilnya — risiko regresi jauh lebih besar untuk keuntungan keamanan nol.

**Mengapa koneksi harus disebut eksplisit:** aplikasi memakai lebih dari satu grup database, dan
`app/Config/Database.php` masih mendeklarasikan grup `default` sebagai **MySQLi** (baru
di-override ke SQLSRV lewat `.env`). Meng-escape nilai dengan aturan MySQL lalu mengirimnya ke
SQL Server justru **membuka kembali celah injeksi**: MySQL mengubah `a'b` menjadi `a\'b`, dan
karena SQL Server membaca backslash sebagai karakter biasa, kutip tunggalnya tetap menutup
string literal — persis kelemahan `addslashes()` di atas. Karena itu tiap controller menyatakan
`$filterDbGroup`-nya (`dbtruck` untuk modul laporan, `default` untuk modul master).

**Ikut beres sebagai efek samping:** `Menu::grid()` dan `Parameter::grid()` dulu menerapkan
filter yang sama dua kali (dua blok `if` berurutan); dan pola `" AND (" . operationAll() . ")"`
tanpa penjagaan menghasilkan SQL `AND ( )` yang error saat `_search=true` datang tanpa rule
valid. Keduanya hilang karena semua pemanggil kini memakai bentuk seragam:

```php
$operation = $search == "true" ? $this->operationAll($filters) : '';
if ($operation !== '') {
    $where .= " AND (" . $operation . ")";
}
```

**Verifikasi.** `php -l` bersih pada seluruh berkas yang disentuh. `GridFilter` diuji offline
terhadap 16 kasus (escape SQLSRV tidak memerlukan koneksi aktif), semuanya lolos:

| Masukan | Keluaran |
|---|---|
| Payload eksploitasi di laporan ini (`field:"1"`, `op:"in"`) | ditolak — kosong |
| `data: "x' OR 1=1--"` | `FNShipper = 'x'' OR 1=1--'` — menjadi literal |
| `field: "FNShipper WHERE 1=1 UNION SELECT 1--"` | ditolak — kosong |
| `op: "; DROP TABLE x--"` | ditolak — kosong |
| `groupOp: "AND) OR (1=1"` | dipaksa menjadi `AND` |
| Sebagian rule valid, sebagian kolom asing | hanya rule valid yang dipakai |
| Filter wajar (`cn`, `bw`, `in`, `nu`/`nn`, tanggal, numerik) | tetap berperilaku seperti sebelumnya |

**Masih terbuka di lingkup yang sama:** klausa `ORDER BY` masih dibangun dari `sidx`/`sord`
tanpa whitelist — lihat **H-03**.

> **Status penerapan:** perubahan berada di working tree `dev` bersama perbaikan sisi model di
> bawah, namun **belum di-commit**. `main` belum tersentuh.

---

**Perbaikan yang Diterapkan — Sisi Model: parameter `cabang` (22 Agustus 2026)**

Refactor parser filter dikerjakan terpisah (`app/Libraries/GridFilter.php` +
`BaseController::escapeFilterValue()`). Bagian di bawah menutup sisa konkatenasi mentah di
**sisi model**, yaitu parameter POST `cabang` — nilai yang tidak melewati parser filter sama
sekali sehingga tetap terbuka meski `operationAll()` sudah aman.

Dua sink yang berbeda, dua penanganan yang berbeda:

| Berkas | Perubahan |
|---|---|
| `app/Models/MtruckingtradoluarModel.php` | `$tableName = 'TradoLuar' . ucfirst(strtolower($cabang))` diganti `getTableName()` dengan whitelist `CABANG_SUFFIX` (`MDN/JKT/SBY/MKS`), mengikuti pola `MomsetModel`. Menutup 7 titik: `count`, `get`, `getGrandTotal`, `get_tglupdate`, `countDetail`, `getDetail`, `getGrandTotalDetail` (dua prefix: `TradoLuar` dan `TradoLuarDetail`). |
| `app/Models/MtruckingtradoluartasModel.php` | Whitelist yang sama untuk prefix `LaporanTradoLuarTas`, dipakai di `getGridData`, `getComboBulan`, `getComboJenisTrado`, `getLastUpdate`. |
| `app/Models/MpiutangemklModel.php` | `WHERE FKCABANG='" . $cabang . "'` diganti `WHERE FKCABANG = ?` + `query($sql, [$cabang])` pada `count`, `get`, `getGrandTotal`, `get_tglupdate`. |
| `app/Models/MovertopModel.php` | Idem pada `count`, `get`, `getGrandTotal`, `get_tglupdate`, `get_where1`, `get_where2`. |
| `app/Models/MovertopmarketingModel.php` | Idem, ditambah `get_marketing()`. |

**Mengapa nama tabel tidak dibinding:** nama objek bukan literal, jadi tidak bisa jadi bind
parameter. Satu-satunya pengaman yang benar adalah whitelist — keluaran `getTableName()` hanya
bisa bernilai salah satu dari empat nama tabel tetap. Kode di luar daftar dicatat
`log_message('warning', ...)` lalu jatuh ke `Mdn`. Input non-string (mis. `cabang[]`) ditangani
lewat `is_string()`.

**Mengapa binding aman meski `$where` ikut disambung:** `Query::matchSimpleBinds()` milik CI4
me-mask `?` yang berada di dalam string ber-kutip sebelum menghitung penanda, sehingga nilai
filter yang mengandung `?` (mis. `LIKE '%wh?t%'`) tidak menyebabkan bind-count mismatch.
Diverifikasi terhadap `vendor` yang terpasang: payload `MDN' OR '1'='1` keluar sebagai
`'MDN'' OR ''1''=''1'` pada `$where` kosong, `$where` berisi filter, maupun `$where` berisi `?`.

**Lingkup yang sama, sudah menyusul ditutup:** klausa `ORDER BY $surut $sord` di seluruh model
ini sekarang disterilkan lewat `App\Libraries\GridSort` — lihat **H-03**, ✅ selesai 22 Agustus
2026. Cabang `BTG` dan `SMG` sengaja tidak masuk whitelist `TradoLuar*` karena tabelnya memang
belum ada.

> **Status penerapan:** perubahan sudah berada di working tree `dev`, berdampingan dengan
> perbaikan parser filter, namun **belum di-commit**. `main` belum tersentuh. Pada
> `MtruckingtradoluartasModel.php` kedua whitelist hidup berdampingan: `FILTER_FIELDS`
> (nama kolom filter) dan `CABANG_SUFFIX` (sufiks nama tabel).

---

#### C-02 — Broken Access Control: ACL Tidak Pernah Dieksekusi

**Status:** ✅ SUDAH DIPERBAIKI (20 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Critical
**Lokasi:** `app/Libraries/MyAuth.php:28-34`, `app/Filters/AuthFilter.php`, `app/Config/Filters.php:78`

**Potongan Kode:**
```php
// app/Libraries/MyAuth.php — method penegak hak akses
public function auth($class=null,$method=null){
    if(!$this->_validatePermission($class,$method)){
        log_message('error', "[MyAuth] Access Denied: $class/$method ...");
        exit("You don't have access to $class/$method");
    }
}
```
```php
// app/Filters/AuthFilter.php — satu-satunya filter global yang aktif
if (!session()->get(SESSION_NAME . 'logged_in')) {
    return redirect()->to(base_url('login'));
}
// ...tidak ada pengecekan role/ACL sama sekali
```

**Penjelasan:** Sistem ACL lengkap tersedia di database (`tblacos`, `tblacl`, `tbluseracl`, `tbluserroles`) dan logikanya sudah ditulis di `MyAuth::_validatePermission()`. Namun `MyAuth::auth()` **tidak pernah dipanggil dari mana pun** dalam codebase. Satu-satunya pemakaian ACL adalah `hasPermission()` yang dipakai untuk **merender menu sidebar** (`MmenutopModel.php:33`) dan tombol di dashboard (`Home.php:39-111`) — murni kosmetik di sisi tampilan.

Diperparah oleh whitelist di `MyAuth::$exceptAuth['method']` yang memuat `grid`, `excel`, `crud`, `operation` — artinya bahkan seandainya ACL diaktifkan, seluruh endpoint data dan CRUD tetap lolos tanpa pemeriksaan.

**Risiko:** Setiap user yang berhasil login — termasuk akun dengan nol hak akses di ACL — dapat mengakses **seluruh** controller dengan mengetik URL langsung: `/user/grid` (daftar user + hash password), `/roles/crud`, `/useracl/crud` (mengubah hak akses dirinya sendiri menjadi admin), `/omset/excel` (export data keuangan seluruh cabang). Ini adalah **privilege escalation vertikal penuh** — OWASP A01.

**Cara Memperbaiki:** Tegakkan ACL di lapisan filter, bukan di view.

**Contoh Kode Perbaikan:**
```php
// app/Filters/AclFilter.php  (BARU)
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AclFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $router = service('router');
        $class  = strtolower(basename(str_replace('\\', '/', $router->controllerName())));
        $method = strtolower($router->methodName());

        if (! hasPermission($class, $method)) {
            log_message('warning', sprintf(
                'ACL DENY user=%s %s/%s ip=%s',
                session()->get(SESSION_NAME . 'userid'), $class, $method, $request->getIPAddress()
            ));

            if ($request->isAJAX()) {
                return service('response')->setStatusCode(403)
                    ->setJSON(['error' => 'Forbidden']);
            }
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
```
```php
// app/Config/Filters.php
public array $aliases = [
    // ...
    'acl' => \App\Filters\AclFilter::class,
];

public array $globals = [
    'before' => [
        'csrf',
        'auth' => ['except' => ['login', 'login/*', 'logout', '/', '', 'webauthn/getLoginArgs',
                                'webauthn/processLogin', 'forgot-password', 'reset-password']],
        'acl'  => ['except' => ['login', 'login/*', 'logout', '/', '', 'home',
                                'webauthn/*', 'forgot-password', 'reset-password']],
    ],
    'after' => ['secureheaders'],
];
```
Dan hapus `grid`, `excel`, `crud`, `operation` dari `MyAuth::$exceptAuth['method']` — endpoint data justru yang paling perlu dilindungi.

---

**Perbaikan yang Diterapkan (20 Agustus 2026)**

| Berkas | Perubahan |
|---|---|
| `app/Filters/AclFilter.php` (baru) | Filter global yang mengambil pasangan class/method dari **router**, lalu memanggil `MyAuth::hasPermission()`. Ditolak → `403 JSON` untuk request AJAX, halaman `403` untuk request biasa. Setiap penolakan dicatat: user, userpk, class/method, URI, IP. |
| `app/Config/Filters.php` | Alias `'acl'` didaftarkan dan dipasang di `globals['before']` tepat setelah `'auth'`, dengan `except` yang sama (alur login/webauthn/reset password). |
| `app/Libraries/MyAuth.php` | `'useracl'` **dikeluarkan** dari `exceptAuth['class']` — inilah jalur eskalasi hak yang sesungguhnya (`POST /useracl/userroles/<userpk-sendiri>` menulis ke `tbluseracl`). Sebagai gantinya ditambahkan peta `classAliases = ['useracl' => ['user','roles']]` sehingga izinnya mengikuti layar induknya — admin User (modal Manage User Roles) dan admin Roles (grid katalog aco di `app/Views/roles/index.php`). Ditambahkan pula `'errors'`, `'webauthn'`, `'gridpreference'`, `'harilibur'` sebagai endpoint infrastruktur. |
| `app/Libraries/MyAuth.php` | `exceptAuth['method']` dilengkapi method pendamping yang benar-benar routable (`getbyid`, `getroles`, `getacos`, `lookupaco`, `reseq`, `griddetail`, `getgriddata`, `getlastupdate`, dll). Daftar ini **tidak** melonggarkan class: ia baru berlaku setelah user terbukti punya minimal satu aco pada class tersebut. |
| `app/Libraries/MyAuth.php` | Cache keputusan & hasil query per request (render sidebar memanggil `hasPermission()` puluhan kali), koneksi DB dibuat lazy, dan `auth()` melempar exception alih-alih `exit()`. |
| `app/Views/errors/html/error_403.php` (baru) | Halaman 403. Sekaligus memperbaiki `Errors::show403()` yang selama ini merujuk view yang tidak ada. |
| `tests/unit/AclTest.php`, `tests/unit/AclFilterTest.php` (baru) | Regresi tabel keputusan ACL + smoke test filter (403 HTML & 403 JSON). |

**Catatan koreksi atas temuan asli:** `exceptAuth['method']` sebenarnya **tidak** membuka endpoint lintas class — daftar itu baru dievaluasi setelah query `tblacos ⋈ tbluseracl/tblacl` untuk class terkait mengembalikan baris. Jadi dampaknya adalah kelonggaran granularitas *di dalam* satu class (pemegang hak baca otomatis dapat `crud`/`operation`), bukan bypass penuh. Pemisahan hak baca vs tulis per method masih perlu dikerjakan dan didaftarkan sebagai baris `tblacos` tersendiri.

**Saklar transisi:** `security.aclEnforce` di `.env`.

```
security.aclEnforce = true    # default: tolak dengan 403
security.aclEnforce = false   # hanya catat "ACL WOULD-DENY" di writable/logs
```

Jalankan sebentar dengan `false` di produksi untuk memanen baris `tblacos` yang masih kurang dari log, baru kembalikan ke `true`. Verifikasi cakupan: seluruh **114 endpoint yang benar-benar routable** tetap dapat diakses selama user memiliki aco `<Class>::index` — pola yang memang dipakai `tblacos` (dikunci oleh `AclTest::testEveryRoutableEndpointStaysReachableWithTheClassIndexAco`).

---

#### C-03 — CSRF Protection Dinonaktifkan Secara Global

**Status:** ✅ SUDAH DIPERBAIKI (22 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Critical
**Lokasi:** `app/Config/Filters.php:75`

**Potongan Kode:**
```php
public array $globals = [
    'before' => [
        // 'honeypot',
        // 'csrf',            // <-- DINONAKTIFKAN
        // 'invalidchars',
        'auth' => ['except' => [...]],
    ],
```

**Penjelasan:** Filter `csrf` dikomentari di `$globals`, dan tidak didaftarkan ulang di `$methods` maupun `$filters`. Akibatnya **tidak ada satu pun request POST yang divalidasi token CSRF-nya**. Ironisnya konfigurasi CSRF di `app/Config/Security.php` sudah benar (`csrfProtection='cookie'`, `regenerate=true`) dan view sudah memancarkan `csrf_field()` (`login.php:393`, `reset_password.php:246`) — token dikirim tapi tidak pernah diperiksa server. Ini memberi rasa aman yang keliru saat code review.

Diperparah oleh `Routes.php` yang mendaftarkan hampir semua endpoint dengan `$routes->match(['GET','POST'], ...)`, termasuk operasi destruktif seperti `Cabang/crud` dan `gridpreference/delete` — sehingga serangan cukup lewat `<img src>`.

**Risiko:** Penyerang dapat memaksa admin yang sedang login untuk mengeksekusi aksi apa pun: mengubah password user lain via `/user/crud`, memberi dirinya role admin via `/useracl/crud`, menghapus data master. Digabung dengan C-02, satu klik pada link berbahaya = kompromi penuh aplikasi.

**Cara Memperbaiki:**
```php
// app/Config/Filters.php
public array $globals = [
    'before' => [
        'csrf' => ['except' => [
            // hanya endpoint yang benar-benar stateless & aman
        ]],
        'invalidchars',
        'auth' => ['except' => [...]],
    ],
    'after' => ['secureheaders'],
];
```
```php
// app/Config/Security.php — aktifkan randomisasi token (mitigasi BREACH)
public bool $tokenRandomize = true;
```
```javascript
// Kirim token pada seluruh AJAX secara global
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
// Perbarui token dari response CI4 setiap kali
$(document).ajaxSuccess(function (e, xhr, opts, data) {
    if (data && data.csrfHash) {
        $('meta[name="csrf-token"]').attr('content', data.csrfHash);
    }
});
```
Pisahkan pula route: `GET` hanya untuk baca, `POST` untuk aksi yang mengubah state.

---

**Perbaikan yang Diterapkan (22 Agustus 2026)**

| Berkas | Perubahan |
|---|---|
| `app/Config/Filters.php` | Filter `'csrf'` diaktifkan di `globals['before']`, **sebelum** `auth`, tanpa daftar `except`. |
| `app/Config/Security.php` | `tokenRandomize = true` (mitigasi BREACH, sesuai saran temuan). |
| `app/Config/Security.php` | `regenerate = false` — lihat penjelasan di bawah. |
| `app/Config/Security.php` | `expires = 0` — lihat penjelasan di bawah. |
| `app/Views/partials/header.php` | `<meta name="csrf-token">` + `$.ajaxSetup({headers:{'X-CSRF-TOKEN': ...}})` tepat setelah jQuery dimuat, sehingga **seluruh** AJAX jQuery (termasuk jqGrid) membawa token tanpa perlu menyentuh satu pun call site. Plus handler `ajaxError` sebagai jaring pengaman token basi. |
| `app/Views/login.php` | Hal yang sama — halaman login berdiri sendiri, tidak memakai partial header. Menutup POST `webauthn/processLogin`. |
| `public/libraries/tas-lib/js/catch-error.js` | Satu-satunya pengirim POST non-jQuery; header dipasang manual karena `fetch()` tidak lewat `$.ajaxSetup`. |
| `tests/unit/CsrfTest.php`, `CsrfClientCoverageTest.php`, `CsrfRenderTest.php` (baru) | Regresi. |

**Dua setelan yang sengaja berbeda dari saran temuan asli.** Keduanya bukan soal selera — dengan nilai defaultnya, mengaktifkan filter CSRF akan mematikan aplikasi:

1. **`regenerate = false`.** Cookie CSRF bersifat `httpOnly`, jadi JavaScript tidak bisa membaca token yang baru. Token dikirim sekali lewat `<meta>` saat halaman dirender. Kalau token diputar setiap POST sukses, nilai di `<meta>` langsung basi dan **seluruh AJAX berikutnya kena 403**. Ini diverifikasi: dengan `regenerate = true`, `CsrfTest::testSameTokenWorksForManySequentialPosts` gagal pada POST kedua. Token per-sesi tetap perlindungan CSRF yang sah (pola default Django/Rails/Laravel).

2. **`expires = 0`** (semula 7200). Cookie CSRF **tidak** diperpanjang setiap request — `Security::__construct` hanya membuat hash baru bila cookienya sudah hilang — sedangkan sesi aplikasi ikut diperpanjang selama user aktif. Dengan 7200, user yang bekerja terus-menerus lebih dari 2 jam tetap login tetapi tokennya kedaluwarsa, dan semua AJAX POST mendadak 403 sampai halaman di-refresh manual. Dengan `0`, token hidup selama tab/browser masih hidup — selalu seumur dengan halaman yang membawanya.

**Yang tidak perlu diubah:** GET tidak pernah diperiksa CI4 (hanya POST/PUT/DELETE/PATCH), jadi seluruh grid jqGrid yang memakai GET tidak terpengaruh sama sekali. CI4 juga sudah membedakan AJAX: request AJAX menerima 403 exception, bukan redirect HTML.

**Pengetesan AJAX.** Selain 14 test PHPUnit, pola pengirimannya dibuktikan di browser sungguhan memakai berkas jQuery 3.6.0 dan jqGrid 5.9.0 yang persis dipakai aplikasi, terhadap endpoint yang memantulkan header yang diterima:

| Pola | Dipakai di | Token terkirim |
|---|---|---|
| `$.ajax` POST polos | `mains.js`, `lookup*.js`, `modalInput.js` | ✅ |
| `$.ajax` POST + `headers` sendiri (`Authorization`) | `lazyLoadingGrid.js:373` | ✅ (token **dan** `Authorization` sama-sama terkirim) |
| `$.post()` | submit form `#fm` di `user`/`menu`/`parameter` | ✅ |
| `$.ajax` dengan body JSON | endpoint crud | ✅ |
| **jqGrid `mtype: 'POST'`** | 34 grid di `app/Views` | ✅ |
| `$.ajax` GET | seluruh grid | ✅ (dan memang tidak diperiksa server) |

Kasus kedua adalah yang paling rawan — kekhawatirannya `headers` per-call menimpa `$.ajaxSetup`. Ternyata tidak: jQuery mendaftarkan `headers` di luar `flatOptions`, sehingga digabung secara *deep merge*. Terbukti langsung di browser, bukan hanya dari membaca dokumentasi.

**Cakupan regresi.** `CsrfClientCoverageTest` memindai **sendiri** seluruh view dan JS aplikasi: setiap halaman standalone yang ber-AJAX POST wajib memasang `<meta>` + `$.ajaxSetup`, dan setiap pengirim POST non-jQuery (`fetch`/XHR) wajib memasang header sendiri. Berkas baru otomatis ikut terperiksa. Ketiga test regresi sudah diverifikasi benar-benar gagal ketika perbaikannya dilepas satu per satu.

---

#### C-04 — Bypass Autentikasi via Pola Regex pada AuthFilter

**Status:** ✅ SUDAH DIPERBAIKI (21 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Critical
**Lokasi:** `app/Filters/AuthFilter.php:13-18`

**Potongan Kode:**
```php
public function before(RequestInterface $request, $arguments = null)
{
    // Allow custom reset password link
    $uri = $request->getUri()->getPath();
    if (preg_match('#.*-\d{2}-\d{2}-\d{4}-\d{2}-\d{2}-\d{2}-[a-f0-9]+$#i', urldecode($uri))) {
        return;                       // <-- LOLOS TANPA CEK LOGIN SAMA SEKALI
    }

    if (!session()->get(SESSION_NAME . 'logged_in')) {
        return redirect()->to(base_url('login'));
    }
}
```

**Penjelasan:** Filter melakukan `return` kosong — artinya request diteruskan sebagai terautentikasi — untuk **setiap URI** yang cocok pola `<apapun>-dd-dd-dddd-dd-dd-dd-<hex>`. Pola ini dievaluasi terhadap **path apa pun**, bukan hanya route reset password. Karena `.*` di awal bersifat sangat permisif dan `urldecode()` dipanggil lebih dulu (memungkinkan penyelundupan `%2F` sebagai pemisah segmen), kondisi pengecualian jauh lebih luas daripada yang dimaksud.

Pendekatannya juga salah secara arsitektur: kontrol autentikasi diputuskan berdasarkan **bentuk string URL**, bukan berdasarkan route yang benar-benar cocok. Verifikasi token sebenarnya sudah dilakukan dengan benar di `Login::resetPasswordCustom()` — jadi pengecualian ini seharusnya cukup didaftarkan sebagai route eksplisit di `except`.

**Risiko:** Bypass kontrol autentikasi. Setiap URI yang dapat dibentuk agar cocok dengan pola akan melewati pemeriksaan sesi sepenuhnya.

**Cara Memperbaiki:** Hapus pengecualian berbasis regex; daftarkan route reset password sebagai route bernama dan masukkan ke daftar `except` filter.

**Contoh Kode Perbaikan:**
```php
// app/Config/Routes.php — route eksplisit dengan pola ketat
$routes->get('reset/(:segment)', 'Login::resetPasswordCustom/$1', ['as' => 'reset.custom']);
```
```php
// app/Config/Filters.php
'auth' => ['except' => [
    'login', 'login/*', 'logout', '/', '',
    'webauthn/getLoginArgs', 'webauthn/processLogin',
    'forgot-password', 'reset-password',
    'reset/*',                       // <-- pengecualian yang tepat & terbatas
]],
```
```php
// app/Filters/AuthFilter.php — bersih, tanpa regex
public function before(RequestInterface $request, $arguments = null)
{
    if (! session()->get(SESSION_NAME . 'logged_in')) {
        if ($request->isAJAX()) {
            return service('response')->setStatusCode(401)
                ->setJSON(['error' => 'Session expired']);
        }
        return redirect()->to(base_url('login'));
    }
}
```

---

**Perbaikan yang Diterapkan (21 Agustus 2026)**

| Berkas | Perubahan |
|---|---|
| `app/Filters/AuthFilter.php` | Blok `preg_match` dihapus seluruhnya. Filter kini murni memeriksa `SESSION_NAME . 'logged_in'`; request AJAX menerima `401 JSON` (`['error' => 'Session expired']`) alih-alih redirect HTML yang selama ini dicerna mentah oleh grid. |
| `app/Config/Routes.php` | Route fallback `sys-modern/(.*)` dan `(.*)` — yang membuat **setiap** URL GET tak dikenal jatuh ke `Login::resetPasswordCustom` — dihapus. Diganti satu route eksplisit `reset/(:segment)` bernama `reset.custom`, diletakkan bersama route reset password lainnya. |
| `app/Config/Filters.php` | `'reset/*'` ditambahkan ke daftar `except` untuk `auth` **dan** `acl`. Inilah satu-satunya tempat pengecualian didaftarkan sekarang. |
| `app/Controllers/Login.php` | `$resetLink` diberi prefix segmen: `base_url('reset/' . urlencode($username) . "-{$datetime}-{$rawToken}")`, agar cocok dengan route baru. Verifikasi token di `resetPasswordCustom()` tidak diubah — memang sudah benar (`hash_equals` + cek kedaluwarsa). |

**Verifikasi** (`php spark filter:check`, CI 4.7.4):

| URI | Before filters | Hasil |
|---|---|---|
| `/reset/dharma-21-08-2026-14-30-00-a1b2c3…` | *(tanpa auth/acl)* | publik, token tetap diverifikasi di controller |
| `/cabang` | `AuthFilter → AclFilter` | tetap terlindungi |
| `/dharma-21-08-2026-14-30-00-a1b2c3…` (bentuk bypass lama) | — | **Can't find a route** → 404, tidak ada controller yang dapat dicapai |

`php spark routes` menunjukkan `reset/([^/]+)` terdaftar tanpa filter, sementara seluruh route lain tetap membawa `auth acl`.

**Catatan dampak:** format URL link reset berubah, sehingga link yang sudah terkirim sebelum perbaikan (masa berlaku 1 jam) tidak lagi valid dan harus di-request ulang. Selain itu URL GET tak dikenal kini menghasilkan 404 dari router, bukan lewat `PageNotFoundException` di dalam `resetPasswordCustom` — hasil akhirnya sama bagi pengguna. Sisi klien belum menangani `401` tersebut: handler `ajaxError` di `public/libraries/tas-lib/js/mains.js:498` masih dikomentari, jadi redirect ke halaman login saat sesi habis masih perlu dikerjakan terpisah.

---

#### C-05 — Skrip Debug Dapat Diakses Publik Tanpa Autentikasi

**Status:** ✅ SELESAI (24 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Critical
**Lokasi:** `public/test_db.php`, `public/test_acos.php`, `public/test_acos_db.php`, `public/test_save.php`, `public/test_diff.php`, `public/test_getargs.php`, `public/test_qs.php`, `public/test_webauthn.php`, `public/test_param.php`

**Potongan Kode:**
```php
// public/test_acos_db.php
define('ENVIRONMENT', 'development');       // paksa mode development
require __DIR__ . '/../app/Config/Paths.php';
require $paths->systemDirectory . '/bootstrap.php';

$db = \Config\Database::connect();
$builder = $db->table('tbluser as u');
// ...
var_dump($result[0]['acos']);               // dump data ke browser
```
```php
// public/test_save.php  — MENULIS ke database, tanpa autentikasi
$model = new \App\Models\UserAclModel();
$payload = ['userpk' => 73, 'acos' => [1,2,3,...,62,40]];
print_r($model->saveBatchData($payload));   // mengubah hak akses user!
```

**Penjelasan:** Sembilan skrip debug berada di dalam document root (`public/`) sehingga dapat dipanggil langsung lewat browser tanpa melewati routing, filter `auth`, maupun filter apa pun. Skrip-skrip ini mem-bootstrap framework, membuka koneksi database, dan mencetak hasilnya. Beberapa di antaranya **memaksa `ENVIRONMENT = 'development'`**, yang mengaktifkan stack trace penuh berisi path filesystem, versi framework, dan potongan source code saat terjadi error. `public/test_param.php` bahkan sudah ter-commit ke Git.

Paling berbahaya: `test_save.php` melakukan operasi **tulis** ke tabel ACL — siapa pun yang membukanya akan memberikan 48 hak akses ke user `userpk=73`.

**Risiko:** Kebocoran data langsung, information disclosure lewat stack trace, dan modifikasi hak akses tanpa autentikasi. Skrip semacam ini juga menjadi titik pijakan favorit untuk enumerasi struktur database.

**Cara Memperbaiki:**
1. Hapus seluruh `public/test_*.php` dari server produksi dan dari repository.
2. Tambahkan pola ke `.gitignore` agar tidak terulang:
```gitignore
# Debug & scratch artifacts
public/test_*.php
scratch/
scratch_*
*.scratch.php
```
3. Sebagai pertahanan berlapis, blokir di `public/.htaccess`:
```apache
<FilesMatch "^(test_|scratch_|debug_).*\.php$">
    Require all denied
</FilesMatch>
```
4. Untuk kebutuhan debugging berulang, gunakan Spark command (`app/Commands/`) yang hanya bisa dijalankan dari CLI — bukan file di `public/`.

**Penutupan Temuan (24 Agustus 2026)**

Ditutup atas keputusan pemilik aplikasi: skrip-skrip ini adalah artefak debugging lokal dan
**tidak ikut dalam rilis** ke server.

Keadaan yang terverifikasi saat penutupan:

| Hal | Keadaan |
|---|---|
| 9 berkas `public/test_*.php` | Masih ada di working tree mesin developer, tidak dipakai aplikasi. |
| `.gitignore` | Sudah memuat `/public/test_*.php` dan pola `scratch_*`, jadi berkas baru sejenis tidak akan ikut ter-commit. |
| `public/test_param.php` | Sudah **tidak tracked** di Git (verifikasi ulang 31 Agustus 2026). |
| `public/.htaccess` | Belum memblokir pola `test_*`. |

**Sisa yang tercatat saat penutupan sudah bersih.** Ketika ditutup pada 24 Agustus 2026,
`public/test_param.php` masih tracked sehingga deploy berbasis checkout/clone dari Git berpotensi
membawanya ke server. Verifikasi ulang pada 31 Agustus 2026 menunjukkan berkas itu sudah tidak lagi
ada di dalam repository (`git ls-files public/test_param.php` tidak menemukan apa pun), jadi tidak
ada lagi skrip debug yang ikut terbawa lewat Git.

Satu langkah opsional yang belum dikerjakan: `public/.htaccess` belum memblokir pola `test_*` sebagai
pertahanan berlapis. Ini bukan syarat penutupan, hanya jaring pengaman jika suatu saat berkas sejenis
tersalin ke server.
Jika sewaktu-waktu skrip debug diperlukan lagi, pakai Spark command di `app/Commands/` yang
hanya bisa dijalankan dari CLI, bukan berkas di dalam `public/`.

---

### HIGH

---

#### H-01 — Session Fixation: Tidak Ada `regenerateId()` Saat Login

**Status:** ✅ SUDAH DIPERBAIKI (22 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** High
**Lokasi:** `app/Controllers/Login.php:68-83` dan `:120-136`

**Potongan Kode:**
```php
if ($cek != "" && $cek->getNumRows() > 0) {
    $row = $cek->getRow();
    $sessionData = [ ... SESSION_NAME . 'logged_in' => 1, ... ];
    session()->set($sessionData);         // <-- session ID lama dipertahankan
    $this->mlogModel->saveLog($this);
    return redirect()->to(base_url("home"));
}
```

**Penjelasan:** Tidak ada pemanggilan `session()->regenerate()` pada saat perubahan level privilese (login). Pencarian di seluruh codebase hanya menemukan `regenerate` sebagai nama properti konfigurasi, bukan sebagai pemanggilan method. `Config\Session::$regenerateDestroy` juga `false`, sehingga sesi lama tidak dihancurkan.

**Risiko:** Session fixation — penyerang menanam session ID (misalnya lewat link atau XSS), korban login memakai ID tersebut, dan penyerang otomatis memegang sesi terautentikasi korban.

**Contoh Kode Perbaikan:**
```php
if ($cek != "" && $cek->getNumRows() > 0) {
    $row = $cek->getRow();

    session()->regenerate(true);   // ID baru + hancurkan data sesi lama

    session()->set([
        SESSION_NAME . 'userpk'    => $row->userpk,
        SESSION_NAME . 'userid'    => $row->userid,
        SESSION_NAME . 'username'  => $row->username,
        SESSION_NAME . 'userlevel' => $row->userlevel,
        SESSION_NAME . 'logged_in' => 1,
        SESSION_NAME . 'cabangid'  => $row->authorityid,
        SESSION_NAME . 'last_activity' => time(),
    ]);
    // ...
}
```
```php
// app/Config/Session.php
public bool $regenerateDestroy = true;
```

---

**Perbaikan yang Diterapkan (22 Agustus 2026)**

`session()->regenerate(true)` dipanggil tepat sebelum penanda login ditulis, pada **tiga** jalur — bukan dua. Laporan asli hanya menyebut `Login.php`; `Webauthn::processLogin()` membangun sesi dengan pola yang sama dan sama rentannya.

| Berkas | Jalur | Keterangan |
|---|---|---|
| `app/Controllers/Login.php:74` | `proses()` | Login dengan password. |
| `app/Controllers/Login.php:125` | `unlock()` | Auto-relogin saat sesi server sudah kedaluwarsa (cabang `!session()->has('logged_in')`) — transisi anonim → terautentikasi, jadi diperlakukan sama seperti login baru. Cabang "sesi masih hidup, sekadar terkunci" tidak merotasi ID karena level privilesenya tidak berubah. |
| `app/Controllers/Webauthn.php:319` | `processLogin()` | **Tidak tercatat di temuan asli.** Login biometrik dari halaman login. |
| `tests/unit/SessionFixationTest.php` (baru) | — | Regresi. |

**Semantik yang diverifikasi** pada PHP 8.2.28 + `FileHandler` yang dipakai aplikasi ini: `session_regenerate_id(true)` menghasilkan ID baru, **isi `$_SESSION` ikut pindah** (jadi `webauthn_challenge` yang dibaca setelah regenerate tetap utuh), dan record sesi lama benar-benar dihancurkan.

**Yang TIDAK diubah — `Config\Session::$regenerateDestroy` tetap `false`.** Ini menyimpang dari saran di temuan asli, dengan alasan: setelan tersebut **tidak berpengaruh pada saat login** — ia hanya mengatur rotasi berkala setiap `$timeToUpdate = 300` detik. Perlindungan fixation sepenuhnya sudah ditegakkan oleh pemanggilan eksplisit di atas. Bila dijadikan `true`, request AJAX yang masih in-flight dari halaman sebelumnya (grid besar, export excel) bisa kehilangan sesinya ketika sebuah page load merotasi ID, lalu user terlempar ke halaman login tanpa sebab yang jelas. CI4 memang melewati rotasi berkala untuk request AJAX, tetapi tidak melindungi AJAX yang sudah berjalan saat page load lain merotasi. Record sesi lama tetap kedaluwarsa lewat `$expiration = 7200` + garbage collector. Alasannya ditulis juga sebagai komentar di `app/Config/Session.php` supaya tidak "diperbaiki" ulang tanpa konteks. Kalau tetap diinginkan, cukup satu baris.

**Catatan cakupan regresi:** jalur login butuh database, yang tidak tersedia di lingkungan test, sehingga `SessionFixationTest` memeriksa di tingkat sumber — ia **mencari sendiri** semua titik yang menulis `'logged_in' => 1` di `app/Controllers/`, lalu memastikan setiap titik didahului `session()->regenerate(true)` dalam method yang sama. Jadi jalur login keempat yang ditambahkan nanti otomatis ikut terperiksa. Test sudah diverifikasi benar-benar gagal ketika salah satu pemanggilan `regenerate` dihapus.

---

#### H-02 — Kredensial Database Hardcoded (Akun `sa`) di File Ter-commit

**Status:** 🟡 SEBAGIAN DITUTUP (29 Agustus 2026) — file scratch & `cekmenu.php` sudah keluar dari pelacakan Git. **Dua sisa belum tertutup:** `app/Helpers/my_helper.php:18` dan riwayat Git. Lihat catatan penutup di akhir item ini.
**Severity:** High
**Lokasi:** `cekmenu.php:4` (**ter-commit di Git**), `scratch_role.php:8`, `scratch_roles.php:2`, `scratch_rosita.php:8`, `scratch/*.php` (belasan file)

**Potongan Kode:**
```php
// cekmenu.php:4  -- TRACKED di repository
$password = "Aa1*****"; // password akun 'sa' tertulis literal
```
```php
// scratch_roles.php:2
$conn = new PDO("sqlsrv:Server=192.168.3.39;Database=transpor_tasmanagement", "sa", "Aa1*****");
```

**Penjelasan:** Password akun **`sa` (sysadmin SQL Server)** tertulis literal di banyak file. `cekmenu.php` sudah ter-commit sehingga password tersimpan permanen dalam riwayat Git. Password itu sendiri sangat lemah (8 karakter, pola kamus + angka berurutan) dan akan jebol dalam hitungan detik oleh brute force. Alamat IP server database internal (`192.168.4.65`, `192.168.3.39`) juga tersebar.

**Risiko:** Siapa pun dengan akses baca repository memperoleh kredensial sysadmin ke seluruh database produksi. Karena `sa` adalah sysadmin, ini setara dengan RCE pada host database (`xp_cmdshell`).

**Cara Memperbaiki:**
1. **Rotasi password `sa` segera** — anggap sudah bocor.
2. Buat user database khusus aplikasi dengan hak minimum (`db_datareader` + `db_datawriter` pada database yang diperlukan saja). Aplikasi tidak boleh berjalan sebagai `sa`.
3. Hapus file scratch dan berhenti melacaknya:
```bash
git rm --cached cekmenu.php public/test_param.php
```
4. Selalu baca kredensial dari environment:
```php
$conn = new PDO(
    sprintf('sqlsrv:Server=%s;Database=%s', getenv('database.default.hostname'), getenv('database.default.database')),
    getenv('database.default.username'),
    getenv('database.default.password')
);
```

---

**Perbaikan yang Diterapkan (29 Agustus 2026)**

Jalur kebocoran lewat berkas ter-commit sudah ditutup, tetapi **temuan ini belum tuntas** — lihat "Sisa" di bawah.

| Berkas | Tindakan | Keterangan |
|---|---|---|
| `cekmenu.php` | `git rm --cached` + masuk `.gitignore:139` | Berkas inti temuan ini. Sebelumnya masih tracked di HEAD dengan `sa` + password literal. Berkas tetap ada di disk pengembang, hanya berhenti dilacak Git. |
| `scratch_*.php`, `scratch/*`, `test_*.php`, `public/test_*.php` | Tercakup `.gitignore` | Diverifikasi: tidak ada satu pun yang tracked (`git ls-files`). |
| `app/Config/Database.php` | — | Sudah bersih; seluruh `password` kosong dan diisi dari `.env`. |

**Catatan penting soal `.gitignore`.** Menambahkan berkas ke `.gitignore` **tidak berpengaruh apa pun** pada berkas yang sudah tracked — inilah sebabnya `cekmenu.php` masih ikut ter-commit meski pola scratch sudah lama terpasang. Yang menutupnya adalah `git rm --cached`, bukan `.gitignore`. Entri `.gitignore` hanya mencegah ia ter-`add` kembali di kemudian hari.

**Sisa yang BELUM ditutup:**

1. **`app/Helpers/my_helper.php:18` — password `sa` masih literal di kode aplikasi yang tracked.**
```php
$connectionInfo = array( "Database"=>$db, "UID"=>'sa', "PWD"=>'Aa1*****'); // Updated to use Password from .env
```
   Komentar "Updated to use Password from .env" **tidak sesuai kenyataan** — nilainya string literal, bukan `getenv()`. Berkas ini kode aplikasi, jadi tidak bisa diselesaikan dengan `git rm --cached`. Daftar host internal per cabang (`tasmedan.dynu.com,1477`, `192.168.3.39,1433`, dll) juga ikut terekspos di baris 7–13.

   Peringan: fungsi pemuatnya, `Generate_ProcedureAll()` dan pembungkusnya `Generate_Procedure()`, **tidak punya satu pun pemanggil** di seluruh repo — sisa migrasi dari CI3. Jadi kredensial ini tidak pernah benar-benar dipakai saat runtime, tetapi tetap terbaca oleh siapa pun yang punya akses repo. Diputuskan untuk dibiarkan pada iterasi ini dan dicatat sebagai utang; penutupannya cukup dengan menghapus kedua fungsi mati tersebut (nol risiko regresi karena nol pemanggil).

2. **Riwayat Git tetap memuat password.** `cekmenu.php` masuk sejak commit `99a205a` ("initial project for sys"). Untracking **tidak** menghapusnya dari riwayat — password masih dapat dipulihkan lewat `git log -p`/`git show`. Membersihkannya butuh penulisan ulang riwayat (`git filter-repo`) yang berdampak ke semua klon.

**Konsekuensinya, rotasi password `sa` tetap menjadi remediasi yang sebenarnya dan masih berstatus terbuka.** Selama password lama belum dirotasi, temuan ini belum benar-benar selesai; langkah di atas hanya menghentikan penyebaran lebih lanjut. Rekomendasi lain dari temuan asli — mengganti `sa` dengan user aplikasi berhak minimum (`db_datareader` + `db_datawriter`) — juga masih terbuka.

---

#### H-03 — SQL Injection pada Klausa `ORDER BY`

**Status:** ✅ SUDAH DIPERBAIKI (22 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** High
**Lokasi:** `app/Models/MuserModel.php:156-179`, `MomsetModel.php:43-58`, dan seluruh model grid lain

**Potongan Kode:**
```php
// app/Controllers/Omset.php:38-39
$sidx = $this->request->getPost('sidx') ?: 'FTgl';   // dari client
$sord = $this->request->getPost('sord') ?: 'desc';   // dari client

// app/Models/MomsetModel.php:47-54
$surut = !empty($sidx) ? $sidx : "FTgl";
$sql = $this->dbtruck->query("SELECT * FROM (
    SELECT ..., ROW_NUMBER() OVER (ORDER BY $surut $sord) AS RowNum
    FROM $table $whereClause
) AS GD WHERE RowNum BETWEEN $start AND $sampai ORDER BY RowNum");
```
```php
// app/Models/MuserModel.php:163-178 — $limit dan $start juga mentah
$sort = " $sidx $sord ";
$sql = "... ORDER BY $sort OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";
```

**Penjelasan:** Nama kolom pengurutan (`sidx`), arah pengurutan (`sord`), dan pada `MuserModel` juga `$limit`/`$start` masuk ke SQL tanpa validasi. Nama kolom tidak dapat dijadikan bind parameter, sehingga **wajib** divalidasi lewat whitelist.

**Risiko:** Injeksi melalui `ORDER BY` — pada SQL Server memungkinkan eksfiltrasi data via subquery dan error-based injection, meski tanpa `UNION`.

**Contoh Kode Perbaikan:**
```php
public function get(string $where, array $binds, string $sidx, string $sord, int $limit, int $start, string $cabang)
{
    $table = $this->getTableName($cabang);

    // Whitelist kolom yang boleh diurutkan
    $sortable = ['FBulan','FTgl','FJumlahMuatan','FJumlahBongkaran','FJumlahExim',
                 'FOmset','FBiayaLapangan','FNomPph23','FProfit','FMargin','FTglUpdate'];
    $sidx = in_array($sidx, $sortable, true) ? $sidx : 'FTgl';
    $sord = strtolower($sord) === 'asc' ? 'ASC' : 'DESC';

    // Paksa tipe integer untuk paging
    $limit = max(1, min(1000, (int) $limit));
    $start = max(0, (int) $start);

    return $this->dbtruck->query(
        "SELECT * FROM (
            SELECT FBulan, FTgl, FOmset, FProfit, FMargin, FTglUpdate,
                   ROW_NUMBER() OVER (ORDER BY {$sidx} {$sord}) AS RowNum
            FROM {$table} WHERE 1=1 {$where}
         ) AS GD
         WHERE RowNum BETWEEN ? AND ? ORDER BY RowNum",
        array_merge($binds, [$start + 1, $start + $limit])
    );
}
```

---

**Perbaikan yang Diterapkan (22 Agustus 2026)**

| Berkas | Perubahan |
|---|---|
| `app/Libraries/GridSort.php` (baru) | `column()` hanya meloloskan identifier polos (`Kolom` / `tabel.Kolom`); apa pun yang mengandung spasi, kutip, koma, atau kurung gugur ke default tulisan developer. `direction()` hanya pernah mengembalikan `ASC`/`DESC`. `limit()`/`offset()` memaksa integer. Whitelist kolom opsional sebagai lapis kedua. |
| **26 model** | Memakai `GridSort` sebelum `sidx`/`sord`/`limit`/`start` masuk ke SQL. SQL di sekitarnya sengaja dibiarkan sama persis supaya perubahannya mekanis dan mudah ditinjau. |
| `tests/unit/GridSortTest.php`, `GridSortCoverageTest.php`, `GridSortBehaviourTest.php` (baru) | Regresi. |

**Cakupannya lebih luas dari yang tercatat di temuan asli.** Temuan menyebut `MuserModel`, `MomsetModel`, "dan seluruh model grid lain". Angka pastinya: **28 method di 26 model** menerima kolom pengurutan dari client. Dua di antaranya tidak akan ketemu dengan pencarian `ORDER BY $`:

1. `MtracingModel::get()` — polanya `ORDER BY 3 desc, $sidx $sord`, jadi `sidx` adalah kunci pengurutan kedua.
2. `MshippernewModel::getList()` — lihat di bawah.

**Temuan tambahan: Query Builder CI4 tidak mengamankan `ORDER BY`.** `MshippernewModel` memakai `$builder->orderBy($sortName, $sortOrder)`, yang sekilas tampak aman. Ternyata tidak — `protectIdentifiers` meloloskan subquery dan ekspresi apa adanya. Dibuktikan langsung dengan `getCompiledSelect()` memakai driver SQLSRV aplikasi ini:

```
IN : (SELECT TOP 1 password FROM tbluser)
OUT: SELECT "a" FROM "dbTruckWeb"."dbo"."x" ORDER BY (SELECT TOP 1 password FROM tbluser) DESC
```

Arah pengurutan memang di-whitelist CI4 ke `ASC`/`DESC`, tetapi nama kolomnya tidak. Jadi jalur Query Builder tetap wajib memakai `GridSort`. Fakta ini dikunci sebagai test (`GridSortCoverageTest::testCodeIgniterQueryBuilderDoesNotEscapeOrderByExpressions`) supaya alasannya tidak hilang.

**Whitelist kolom, bukan sekadar validasi bentuk.** Validasi identifier saja sudah cukup menutup injeksi, tetapi belum menutup inferensi buta: `MuserModel` memakai `SELECT tbluser.*` sehingga `ORDER BY password` bisa dipakai menebak isi hash dari urutan baris. Karena itu model dengan kolom sensitif diberi whitelist eksplisit yang diambil dari `colModel` view-nya masing-masing: `MuserModel`, `RolesModel`, `MparameterModel`, `UserAclModel`, `MmenuModel` (dua grid), `MtracingModel`, `MshippernewModel`. Model laporan yang memakai `SELECT *` memakai validasi identifier saja.

**Paging.** `$limit`/`$start` juga masuk mentah ke `OFFSET`/`FETCH NEXT`/`BETWEEN` di 24 model. Sekarang dipaksa integer. Sengaja **tidak** dibatasi rentangnya: sebagian controller memakai `limit = 0` sebagai penanda "tidak ada data", dan pembatasan ukuran halaman adalah lingkup **M-06**. Pada tiga model (`Movertop`, `Movertopmarketing`, `Mpiutangemkl`) sterilisasi harus ditaruh **sebelum** `$start = $start + 1`, kalau tidak `$sampai` terlanjur dihitung dari nilai mentah — versi pertama perbaikan ini sempat salah di situ.

**Verifikasi tidak ada perubahan perilaku.** `GridSortBehaviourTest` mereplikasi percabangan tiap model dan membandingkan klausa ORDER BY yang terbentuk untuk input yang sah: default majemuk `substring(FBulan,4,4) DESC, FBulan DESC, FNMarketing ASC` tetap utuh, pemetaan `rolename` → `tbluser.userid` di grid User tetap jalan, `class` → `class, method` di grid ACO tetap jalan, dan aritmetika paging menghasilkan angka yang sama. Yang berubah hanya perilaku untuk input berbahaya.

`GridSortCoverageTest` memindai sendiri seluruh berkas model dan menolak method mana pun yang menerima parameter pengurutan tanpa melewatkannya ke `GridSort` — jadi model baru otomatis ikut terperiksa. Sudah diverifikasi benar-benar gagal ketika penjagaan di salah satu model dilepas.

---

#### H-04 — Tidak Ada Proteksi Brute Force / Rate Limiting

**Status:** ✅ SUDAH DIPERBAIKI (24 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** High
**Lokasi:** `app/Controllers/Login.php:39` (`proses`), `:96` (`unlock`), `:157` (`forgotPassword`)

**Penjelasan:** Pencarian menyeluruh tidak menemukan mekanisme throttle, penghitung percobaan gagal sisi server, penguncian akun, CAPTCHA, maupun delay progresif. Endpoint `Login::proses` menerima percobaan tak terbatas. Filter `honeypot` juga dinonaktifkan di `Filters.php:74`. Penghitung yang ada (`sysmodern_idle-failed-attempts` di `lockscreen.js:168`) tersimpan di **localStorage** — sepenuhnya di sisi klien dan trivial untuk dilewati.

`Login::forgotPassword` tanpa rate limit juga menjadi vektor abuse: pengiriman email massal lewat akun Brevo perusahaan.

**Risiko:** Brute force dan credential stuffing. Dengan kebijakan password minimal 5 karakter (H-06), akun dapat ditembus dalam waktu singkat.

**Contoh Kode Perbaikan:**
```php
// app/Controllers/Login.php
public function proses()
{
    $throttler = service('throttler');
    $ip        = $this->request->getIPAddress();
    $userid    = (string) $this->request->getPost('userid');

    // Maks 5 percobaan/menit per IP, dan 5 per akun (cegah password spraying)
    if ($throttler->check("login-ip-{$ip}", 5, MINUTE) === false
        || $throttler->check('login-user-' . md5($userid), 5, MINUTE) === false) {

        log_message('warning', "Login throttled ip={$ip} userid={$userid}");

        return redirect()->to(base_url('login'))->with(
            SESSION_NAME . 'message',
            'Terlalu banyak percobaan login. Coba lagi dalam beberapa saat.'
        );
    }

    // ... lanjutkan proses login
}
```
Terapkan pola yang sama pada `unlock()` dan `forgotPassword()`.

---

**Perbaikan yang Diterapkan (24 Agustus 2026)**

| Berkas | Perubahan |
|---|---|
| `app/Libraries/LoginThrottle.php` (baru) | Pembungkus `service('throttler')` bawaan CI4 (token bucket di cache; handler aplikasi ini `file`, jadi tanpa infrastruktur tambahan). |
| `app/Controllers/Login.php` | `proses()`, `unlock()`, dan `forgotPassword()` memakai limiter tersebut. |
| `public/libraries/tas-lib/js/lockscreen.js` | Menampilkan pesan 429 dari server, bukan "Terjadi kesalahan koneksi". |
| `tests/unit/LoginThrottleTest.php`, `LoginThrottleEndpointTest.php` (baru) | Regresi. |

**Dua keputusan yang membedakannya dari contoh kode di temuan asli:**

1. **Jatah hanya berkurang saat GAGAL.** Contoh di temuan memanggil `check()` di awal
   `proses()`, yang berarti setiap login — termasuk yang berhasil — mengambil satu token.
   Dengan batas 5/menit, lima orang yang login normal dari satu kantor akan mengunci orang
   keenam. Di sini pemeriksaan memakai `cost = 0` (murni mengintip) dan token baru berkurang
   lewat `hit()` setelah kredensial terbukti salah. User yang selalu berhasil login tidak
   pernah menyentuh batas sama sekali. Ini penting karena satu kantor lazimnya keluar lewat
   satu IP publik (NAT).

2. **Batas per-IP dilonggarkan, per-akun diketatkan.** Temuan menyarankan 5/menit untuk
   keduanya. Ember per-akun adalah pertahanan brute force yang sebenarnya, jadi itu yang
   diketatkan; ember per-IP hanya perlu menangkap *password spraying* lintas akun tanpa
   mengunci satu kantor.

| Aksi | Per akun | Per IP | Pemulihan |
|---|---|---|---|
| `login` (dipakai bersama `proses()` dan `unlock()`) | 5 kegagalan / 15 menit | 20 kegagalan / 15 menit | 1 percobaan tiap 3 menit (akun), tiap 45 detik (IP) |
| `forgot-password` | 3 / jam | 10 / jam | 1 tiap 20 menit (akun), tiap 6 menit (IP) |

**Catatan penerapan:**

- `unlock()` memverifikasi password yang sama dengan halaman login, jadi sengaja memakai ember
  yang sama. Percobaan lewat lock screen dan lewat halaman login dihitung bersama-sama —
  kalau dipisah, penyerang cukup berganti endpoint untuk mendapat jatah dua kali lipat.
- Login berhasil menghapus hukuman **pada akun itu saja**. Ember per-IP sengaja dibiarkan:
  satu tebakan yang kebetulan benar tidak boleh menghapus jejak ribuan percobaan lain dari
  IP yang sama.
- `forgotPassword()` memotong jatah **sebelum** email dikirim, bukan sesudah, supaya kegagalan
  SMTP tidak bisa dipakai memukul server email berulang kali. Mode `check=true` tidak memotong
  jatah (satu aksi user memang menghasilkan dua request), tetapi ikut ditolak begitu batasnya
  tercapai.
- Kunci cache selalu di-hash. Selain menyamarkan userid, ini menghindari
  `Config\Cache::$reservedCharacters` yang memuat `:` — alamat IPv6 mentah akan melempar
  exception kalau dipakai langsung sebagai kunci.
- Penolakan dicatat ke `writable/logs` (`Rate limit login: account=… ip=… tunggu=…d`).

**Perubahan sisi klien yang diperlukan.** `lockscreen.js` sebelumnya menelan setiap respons
non-200 menjadi "Terjadi kesalahan koneksi", jadi pesan rate limit tidak akan pernah terlihat.
Handler `error` sekarang membaca `responseJSON.message`. Respons 429 juga sengaja **tidak**
dilewatkan ke `handleFailedUnlock()`: fungsi itu menambah penghitung di localStorage dan memaksa
logout setelah sekian kali, sehingga user yang sedang kena rate limit akan ikut ter-logout
dengan pesan "sisa percobaan" yang menyesatkan. Perbaikan ini sekaligus memperbaiki respons 401
("Sesi telah berakhir permanen") yang selama ini juga tertelan.

**Yang belum disentuh:** `Webauthn::processLogin()` tidak diberi rate limit. Verifikasi tanda
tangan WebAuthn bersifat kriptografis sehingga tidak bisa ditebak, jadi bukan vektor brute force
kredensial. Kalau nanti diinginkan pembatasan untuk mencegah penyalahgunaan sumber daya, ember
per-IP yang longgar sudah cukup.

**Pengujian.** 15 test unit mengunci perilaku ember (waktu dimajukan lewat `setTestTime()`,
bukan dengan menunggu): login sukses berturut-turut tidak mengurangi jatah, 5 kegagalan mengunci
akun, pulih setelah 3 menit, hukuman satu akun tidak bocor ke akun lain, berpindah IP tidak
mereset hukuman akun, spraying lintas akun tertahan ember IP, huruf besar-kecil userid tidak
bisa dipakai mengelak, dan IPv6 tidak memecahkan kunci cache. Ditambah 6 test tingkat endpoint
yang benar-benar men-dispatch request ke `login/proses`, `login/unlock`, dan `forgot-password`,
memastikan bentuk responsnya (redirect+flash, JSON 429, JSON 429 lengkap dengan `csrfToken`)
serta memastikan request yang belum kena batas tetap berlanjut sampai ke database.

---

#### H-05 — Hash Password Disimpan di Dalam Session

**Status:** ✅ SUDAH DIPERBAIKI (29 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** High
**Lokasi:** `app/Controllers/Login.php:75` dan `:128`, dibaca di `app/Models/MloginModel.php:60-70`

**Potongan Kode:**
```php
$sessionData = [
    ...
    SESSION_NAME . 'password' => $row->password,   // hash bcrypt masuk ke session
    ...
];
```

**Penjelasan:** Hash bcrypt user disimpan dalam session file di `writable/session`. Session driver adalah `FileHandler`, sehingga hash tertulis ke disk dalam bentuk serialized dan bertahan sampai garbage collection. `MloginModel::cek()` kemudian memakainya sebagai kriteria `WHERE password = ...` — pola yang tidak diperlukan sama sekali.

**Risiko:** Memperluas dampak dari kerentanan lain: LFI, path traversal, atau akses baca ke `writable/` langsung membocorkan hash password untuk cracking offline. Melanggar prinsip minimalisasi data sensitif.

**Contoh Kode Perbaikan:**
```php
$sessionData = [
    SESSION_NAME . 'userpk'    => $row->userpk,
    SESSION_NAME . 'userid'    => $row->userid,
    SESSION_NAME . 'username'  => $row->username,
    SESSION_NAME . 'userlevel' => $row->userlevel,
    SESSION_NAME . 'logged_in' => 1,
    SESSION_NAME . 'cabangid'  => $row->authorityid,
    // hash password TIDAK disimpan
];
```
```php
// MloginModel::cek() — validasi cukup dengan userpk, bukan hash
public function cek()
{
    $userpk = session()->get(SESSION_NAME . 'userpk');
    if (! $userpk) {
        return false;
    }
    return $this->db->table($this->table)->where('userpk', $userpk)->limit(1)->get();
}
```

---

**Perbaikan yang Diterapkan (29 Agustus 2026)**

Hash password tidak lagi pernah masuk ke session. Laporan asli menyebut dua titik penulis (`Login.php:75` dan `:128`); penelusuran menemukan **empat**, plus satu pembaca nyata yang tidak tercatat sama sekali.

| Berkas | Jalur | Tindakan |
|---|---|---|
| `app/Controllers/Login.php:249` | `proses()` | Kunci `password` dihapus dari `$sessionData`. |
| `app/Controllers/Login.php:372` | `unlock()` | Idem. |
| `app/Controllers/SsoAuth.php:193` | `callback()` | **Tidak tercatat di temuan asli.** Login via SSO membangun sesi dengan pola yang sama. |
| `app/Controllers/Webauthn.php:352` | `processLogin()` | **Tidak tercatat di temuan asli.** Login biometrik. |
| `app/Controllers/Profil.php:68` | `editpassword()` | **Satu-satunya pembaca nyata.** Hash sekarang diambil dari database lewat `userpk`. |
| `app/Models/MloginModel.php:67` | `cek()` | `WHERE password = <hash sesi>` diganti `WHERE userpk = ...`. |
| `tests/unit/PasswordHashNotInSessionTest.php` (baru) | — | Regresi. |

**Pembaca yang luput dari temuan asli.** Laporan menunjuk `MloginModel::cek()` sebagai konsumen hash sesi, padahal method itu **tidak punya satu pun pemanggil** — dan lebih jauh, ia membaca `$_SESSION['password']` tanpa prefiks `SESSION_NAME`, sehingga nilainya **selalu `null`** dan `WHERE password = NULL` tidak pernah cocok. Method itu praktis mati dua kali. Konsumen yang sebenarnya adalah `Profil::editpassword()`, yang memakai hash sesi untuk memverifikasi password lama sebelum penggantian. Itulah alasan sesungguhnya kunci ini ada, dan itu yang harus diubah lebih dulu — kalau hanya penulisnya yang dihapus, fitur ganti password langsung rusak.

**Efek samping yang menguntungkan.** Membaca hash dari database saat dibutuhkan sekaligus menutup celah kebasian pada pola lama: bila password seorang user diubah lewat jalur lain (reset oleh admin, misalnya) sementara sesinya masih hidup, salinan di sesi menjadi usang dan verifikasi password lama tetap menerima password yang sudah tidak berlaku. Sekarang verifikasi selalu memakai hash mutakhir.

**`MloginModel::cek()` dipertahankan, tidak dihapus.** Meski dead code, method ini ditulis ulang sesuai saran temuan (validasi via `userpk`) alih-alih dibuang, supaya perubahan tetap terbatas pada lingkup H-05. Perbaikan prefiks `SESSION_NAME` ikut sekalian. Penghapusannya sebagai dead code layak dipertimbangkan terpisah.

**Catatan operasional — sesi yang sudah berjalan.** Perbaikan ini menghentikan penulisan baru, tetapi sesi yang sudah aktif saat deploy **masih memuat hash lama** di berkasnya. `session()->regenerate(true)` memindahkan isi `$_SESSION` ke ID baru, jadi login ulang pun tidak membersihkannya; yang menghapus hanya `session()->destroy()` saat logout (`Login.php:301`) atau kedaluwarsa `$expiration = 7200`. Bila ingin bersih seketika saat rilis, kosongkan `writable/session/` sekali (konsekuensinya semua user harus login ulang).

**Cakupan regresi.** Sama seperti H-01, jalur login butuh database yang tidak tersedia di lingkungan test, jadi pemeriksaan dilakukan di tingkat sumber. `PasswordHashNotInSessionTest` **memindai sendiri seluruh `app/`** untuk dua bentuk terlarang — `SESSION_NAME . 'password'` dan `$_SESSION['password']` — lalu memeriksa terpisah bahwa setiap payload yang menulis `'logged_in' => 1` tidak memuat kunci `password`. Jalur login baru yang ditambahkan nanti otomatis ikut terperiksa. Test sudah diverifikasi benar-benar gagal ketika baris `SESSION_NAME . 'password'` dikembalikan ke `Login::proses()`. Suite penuh: 244 test, 1036 assertion, hijau.

---

#### H-06 — Kebijakan Password Lemah & Tidak Ada `password_needs_rehash()`

**Severity:** High
**Lokasi:** `app/Controllers/Login.php:277`, `app/Views/auth/reset_password.php:255`

**Potongan Kode:**
```php
if (!$this->validate(['password' => 'required|min_length[5]'])) {
```

**Penjelasan:** Password minimal **5 karakter**, tanpa syarat kompleksitas, tanpa pengecekan terhadap daftar password bocor. Standar juga meminta `password_needs_rehash()` — fungsi ini tidak ada di seluruh codebase, sehingga hash yang dibuat dengan cost lama tidak pernah di-upgrade meski konfigurasi bcrypt berubah.

**Contoh Kode Perbaikan:**
```php
// app/Config/Validation.php — rule reusable
public array $passwordRules = [
    'password' => [
        'label'  => 'Password',
        'rules'  => 'required|min_length[12]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
        'errors' => [
            'min_length'  => 'Password minimal 12 karakter.',
            'regex_match' => 'Password harus memuat huruf besar, huruf kecil, dan angka.',
        ],
    ],
];
```
```php
// Upgrade hash otomatis saat login sukses — app/Models/MloginModel.php
if (password_verify($password, $hashTrim)) {
    if (password_needs_rehash($hashTrim, PASSWORD_BCRYPT)) {
        $this->db->table($this->table)
                 ->where('userid', $userid)
                 ->update([$fieldPassword => password_hash($password, PASSWORD_BCRYPT)]);
    }
    return $sql;
}
```

---

#### H-07 — Hash MD5 Legacy Masih Diterima Saat Login

**Status:** 🟢 **RISIKO DITERIMA — MIGRASI BERTAHAP** (keputusan 31 Agustus 2026) — jalur MD5 sengaja dibiarkan terbuka sebagai *jalur migrasi*, bukan karena terlewat. Lihat catatan penutup di akhir item ini.
**Severity:** High (nilai asli temuan) — **efektif Medium** setelah kontrol pengimbang diperhitungkan
**Lokasi:** `app/Models/MloginModel.php:41-51`, `app/Controllers/Profil.php:96`

**Potongan Kode:**
```php
} else {
    // Belum dimigrasi, anggap hash adalah MD5
    if (strcasecmp(md5($password), $hashTrim) === 0) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        // ... update ke bcrypt
    }
}
```

**Penjelasan:** Mekanisme migrasi otomatis MD5 ke bcrypt adalah pendekatan yang baik untuk transisi, namun jalur MD5 tetap terbuka tanpa batas waktu. Selama masih ada baris `tbluser` berisi hash MD5, penyerang yang memperoleh dump database dapat memecahkan password tersebut secara instan (MD5 tanpa salt, rainbow table).

**Cara Memperbaiki:** Jalankan migrasi paksa dan tutup jalur MD5.
```php
// app/Database/Migrations/xxxx_ForceResetLegacyPasswords.php
public function up()
{
    // Tandai semua akun yang masih memakai MD5 agar wajib reset password
    $this->db->query("
        UPDATE tbluser
        SET must_reset_password = 1
        WHERE password NOT LIKE '\$2y\$%'
          AND password NOT LIKE '\$2a\$%'
          AND password NOT LIKE '\$2b\$%'
          AND password NOT LIKE '\$argon2%'
    ");
}
```
```php
// MloginModel::login() — setelah deadline migrasi, tolak hash non-bcrypt
if (! str_starts_with($hashTrim, '$2') && ! str_starts_with($hashTrim, '$argon2')) {
    log_message('warning', "Legacy MD5 hash login attempt: {$userid}");
    return false;   // paksa lewat alur reset password
}
```

---

**Catatan penutup — keputusan: risiko diterima, migrasi dijalankan bertahap (31 Agustus 2026)**

**Keputusan.** Jalur verifikasi MD5 **tetap dibuka untuk sementara**. Migrasi ke bcrypt dijalankan bertahap dan otomatis mengikuti pemakaian normal: setiap user yang login ke sys akan ter-upgrade hash-nya sendiri — tanpa pengumuman, tanpa reset massal, dan tanpa mengunci siapa pun di luar sistem. Rekomendasi "migrasi paksa" di atas **tidak dibatalkan, hanya dijadwalkan belakangan** — dipakai nanti untuk membersihkan sisa akun yang tidak pernah login.

**Dasar 1 — tidak ada satu pun jalur yang menulis MD5 baru.** Seluruh titik penulisan password di codebase menghasilkan bcrypt:

| Jalur | Lokasi | Hash yang ditulis |
|---|---|---|
| Login (upgrade otomatis) | `app/Models/MloginModel.php:43` | `password_hash(..., PASSWORD_BCRYPT)` |
| Ganti password sendiri | `app/Controllers/Profil.php:106` | `password_hash(..., PASSWORD_BCRYPT)` |
| Reset password lewat email | `app/Controllers/Login.php:611` | `password_hash(..., PASSWORD_BCRYPT)` |
| Buat/ubah user oleh admin | `app/Models/MuserModel.php:154-157` (`_hashPassword`) | `password_hash(..., PASSWORD_BCRYPT)` |

Artinya populasi hash MD5 di `tbluser` bersifat **menurun satu arah** — tidak mungkin bertambah. Inilah yang membuat "menunggu" jadi strategi yang sah: masalahnya menyusut sendiri seiring pemakaian, bukan mengendap.

**Dasar 2 — migrasi berjalan tanpa aksi user dan tanpa downtime.** Empat jalur di atas semuanya menutup satu akun MD5 begitu dipakai: login biasa, ganti password di profil, reset lewat email, atau reset oleh admin. Untuk aplikasi internal yang dipakai harian, mayoritas akun aktif akan termigrasi dalam hitungan hari kerja pertama. Reset massal sebaliknya memaksa seluruh karyawan membuat password baru serentak — beban dukungan yang nyata, dengan efek samping klasik: password yang lebih lemah dan lebih sering dicatat di kertas.

**Dasar 3 — jalur MD5 tidak memperlebar permukaan serangan online.** Verifikasi MD5 tetap menuntut password yang benar; ia tidak melewati pemeriksaan apa pun. Kecepatan MD5 baru berbahaya pada serangan *offline* (penyerang sudah memegang hash-nya). Pada serangan *online*, biaya menebak ditentukan rate limit, bukan kecepatan hash — dan `LoginThrottle` (H-04, ✅ 24 Agustus 2026) membatasi 5 kegagalan/15 menit per akun serta 20 kegagalan/15 menit per IP, berlaku sama untuk akun MD5 maupun bcrypt. Termasuk pada `Login::unlock()`, yang secara efektif adalah jalur login kedua.

**Risiko sisa yang tetap harus dicatat.** Menerima risiko bukan berarti risikonya lenyap. Tiga hal tetap berlaku:

1. **Akun dorman tidak akan pernah termigrasi sendiri.** Akun yang tidak pernah login — user resign yang belum dinonaktifkan, akun cadangan, akun service — akan menyimpan hash MD5-nya selamanya. Migrasi bertahap **tidak akan pernah mencapai 0%** tanpa satu tindakan penutup di akhir.
2. **Bahaya sebenarnya ada pada hash yang tersimpan, bukan pada jalur login.** Bila dump database bocor, MD5 tanpa salt pecah seketika lewat rainbow table. Perlu diluruskan: menutup jalur login di `MloginModel` **tidak menghapus satu pun hash MD5** dari tabel — yang menghapusnya hanya penggantian password. Jadi keputusan menunda ini nyaris tidak menambah eksposur terhadap dump; yang menentukan justru **seberapa cepat populasi MD5 mengecil**. Karena itu pemantauan di bawah bukan formalitas.
3. **Dampak ikutannya melampaui sys.** Password yang pecah dari MD5 lazim dipakai ulang di layanan lain (email, aplikasi HR). Untuk akun ber-hak tinggi, kerugiannya tidak berhenti di aplikasi ini.

**Syarat agar keputusan ini tetap sah.** Empat hal berikut adalah bagian dari keputusan, bukan tambahan opsional:

1. **Pantau populasinya.** Jalankan berkala (T-SQL, driver default aplikasi `SQLSRV`):
   ```sql
   SELECT
       SUM(CASE WHEN password LIKE '$2%' OR password LIKE '$argon2%' THEN 1 ELSE 0 END)     AS sudah_bcrypt,
       SUM(CASE WHEN password NOT LIKE '$2%' AND password NOT LIKE '$argon2%' THEN 1 ELSE 0 END) AS masih_md5
   FROM tbluser
   WHERE aktif = 1;
   ```
   Angka `masih_md5` harus turun setiap minggu. Bila stagnan, asumsi dasar keputusan ini gugur dan migrasi paksa harus dimajukan.
2. **Tetapkan batas waktu, jangan biarkan menggantung.** Beri jendela migrasi alami (usulan: sampai **30 November 2026**, ± satu kuartal). Setelah itu jalankan migrasi paksa untuk sisa akun dan tutup jalur MD5 memakai potongan kode "Cara Memperbaiki" di atas. Jumlah akun yang tersisa saat itu akan sedikit, sehingga bebannya kecil — justru itu keuntungan menunda secara terkendali.
3. **Akun ber-hak tinggi jangan ikut mengantre.** Akun admin/`userlevel` tinggi yang masih ber-hash MD5 sebaiknya direset manual sekarang. Untuk kelompok inilah dampak dump paling besar, dan jumlahnya sedikit.
4. **Satukan dengan H-06.** Saat kebijakan password diperketat, momen login MD5 adalah satu-satunya titik di mana sistem memegang password polos milik user legacy — di situ password bisa sekaligus diuji terhadap kebijakan baru sebelum di-`password_hash()`. Bila tidak, akun legacy akan lolos ke bcrypt sambil tetap membawa password 5 karakter.

**Catatan implementasi — jalur kedua sengaja dipertahankan.** `Profil::editpassword()` (`app/Controllers/Profil.php:96`) juga menerima MD5 saat memverifikasi password lama. Ini **bagian dari migrasi, bukan temuan terpisah**: tanpa itu, user legacy tidak bisa mengganti password sendiri dan justru terdorong ke jalur reset. Jalur ini ditutup bersamaan dengan `MloginModel::login()`, bukan lebih dulu.

**Peninjauan ulang.** Keputusan ini batal dan migrasi paksa dijalankan segera bila muncul salah satu dari: indikasi kebocoran/dump database, temuan akses baca tak sah ke `tbluser`, atau `masih_md5` tidak turun selama dua siklus pemantauan berturut-turut.

---

#### H-08 — Cookie Tidak `Secure` dan HTTPS Tidak Dipaksakan

**Severity:** High
**Lokasi:** `app/Config/Cookie.php` (`$secure = false`), `app/Config/App.php` (`$forceGlobalSecureRequests = false`)

**Potongan Kode:**
```php
// app/Config/Cookie.php
public bool $secure = false;      // cookie dikirim lewat HTTP polos
```
```php
// app/Config/App.php
public bool $forceGlobalSecureRequests = false;   // filter forcehttps jadi tidak aktif
```

**Penjelasan:** Cookie sesi tidak bertanda `Secure`, sehingga browser mengirimkannya melalui koneksi HTTP tidak terenkripsi. Filter `forcehttps` memang terdaftar di `$required['before']`, tetapi filter tersebut hanya bekerja jika `forceGlobalSecureRequests = true` — jadi efektif menganggur. `app.baseURL` di `.env` pun masih `http://localhost/sys-modern/`. Header HSTS yang dikirim `SecurityHeaders.php:44` diabaikan browser bila diterima lewat HTTP.

**Risiko:** Session hijacking lewat sniffing jaringan atau serangan MITM di jaringan internal/WiFi kantor.

**Contoh Kode Perbaikan:**
```php
// app/Config/Cookie.php
public bool $secure   = true;
public bool $httponly = true;
public string $samesite = 'Strict';   // 'Lax' jika ada kebutuhan navigasi lintas situs
```
```php
// app/Config/App.php
public bool $forceGlobalSecureRequests = true;
```
```dotenv
# .env
app.baseURL = 'https://sys.karaya.site/sys-modern/'
```

*Catatan (31 Agustus 2026):* `$allowedHostnames` tidak perlu lagi ditulis tangan
di sini — sejak M-05 ditutup, `Config\App::__construct()` mengisinya dari
`app.allowedHostnames` di `.env`. Yang tersisa untuk H-08 tinggal
`forceGlobalSecureRequests` dan `Cookie::$secure`, dan karena `Config\App` tidak
memanggil `parent::__construct()`, `forceGlobalSecureRequests` harus diubah di
berkas config-nya, bukan lewat `.env`.

---
### MEDIUM

---

#### M-01 — Header Keamanan Belum Lengkap; CSP Dilumpuhkan `unsafe-inline`/`unsafe-eval`

**Status:** ⚠️ SEBAGIAN SELESAI (31 Agustus 2026) — seluruh header yang kurang sudah dipasang; pelepasan `unsafe-inline`/`unsafe-eval` tetap tertunda dan syaratnya dijelaskan di catatan penutup.
**Severity:** Medium
**Lokasi:** `app/Filters/SecurityHeaders.php:42-66`, `app/Config/ContentSecurityPolicy.php:33-40`

**Status per header (checklist poin 1):**

| Header | Status | Keterangan |
|---|---|---|
| Strict-Transport-Security | Ada | tapi tidak efektif tanpa HTTPS (H-08) |
| X-Frame-Options | Ada | `SAMEORIGIN` |
| X-Content-Type-Options | Ada | `nosniff` |
| Referrer-Policy | Ada | `strict-origin-when-cross-origin` |
| Permissions-Policy | Ada | 3 fitur dibatasi |
| Content-Security-Policy | Aktif tapi lemah | `unsafe-inline` + `unsafe-eval` |
| Cross-Origin-Opener-Policy | **Tidak ada** | |
| Cross-Origin-Embedder-Policy | **Tidak ada** | |
| Cross-Origin-Resource-Policy | **Tidak ada** | |

**Potongan Kode:**
```php
// app/Config/ContentSecurityPolicy.php:34
public $scriptSrc = ['self', 'unsafe-inline', 'unsafe-eval',
                     'https://cdnjs.cloudflare.com', 'https://code.highcharts.com', ...];
public bool $autoNonce = false;
```

**Penjelasan:** `$CSPEnabled = true` di `App.php` — bagus, CSP benar-benar dikirim. Namun `unsafe-inline` pada `script-src` membuat CSP kehilangan hampir seluruh nilai proteksi XSS-nya, dan `unsafe-eval` mengizinkan `eval()`. Tiga header Cross-Origin (COOP/COEP/CORP) yang diminta standar belum ada sama sekali.

**Contoh Kode Perbaikan:**
```php
// app/Filters/SecurityHeaders.php — tambahkan 3 header yang kurang
$response->setHeader('Cross-Origin-Opener-Policy', 'same-origin');
$response->setHeader('Cross-Origin-Embedder-Policy', 'require-corp');
$response->setHeader('Cross-Origin-Resource-Policy', 'same-origin');

// Sekalian lengkapi Permissions-Policy
$response->setHeader(
    'Permissions-Policy',
    'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
);
```
Untuk pengetatan CSP, lihat bagian **CSP Readiness** di bawah.

---

**Catatan penutup (31 Agustus 2026)**

**Status per header sesudah perbaikan.**

| Header | Sebelum | Sesudah |
|---|---|---|
| Strict-Transport-Security | Ada | tidak berubah |
| X-Frame-Options | Ada | tidak berubah |
| X-Content-Type-Options | Ada | tidak berubah |
| Referrer-Policy | Ada | tidak berubah |
| Permissions-Policy | 3 fitur | **11 fitur** |
| Cross-Origin-Opener-Policy | Tidak ada | **`same-origin-allow-popups`** |
| Cross-Origin-Resource-Policy | Tidak ada | **`same-origin`** |
| Cross-Origin-Embedder-Policy | Tidak ada | **sengaja tetap tidak ada** — alasannya di bawah |
| CSP `base-uri` | tidak dikirim | **`'self'`** |
| CSP `frame-ancestors` | tidak dikirim | **`'self'`** |
| CSP `object-src` | `'self'` | **`'none'`** |
| CSP `script-src` | `unsafe-inline` + `unsafe-eval` | belum berubah — lihat "Yang belum dikerjakan" |

**COEP `require-corp` sengaja TIDAK dipasang — menyimpang dari rekomendasi di atas.**
Nilai itu menuntut setiap sub-resource lintas-origin membawa header CORP sendiri, atau diambil
lewat CORS eksplisit. Aplikasi ini memuat skrip dari `cdnjs.cloudflare.com`,
`code.highcharts.com`, dan `static.cloudflareinsights.com`; style dari `fonts.googleapis.com`;
font dari `fonts.gstatic.com`; serta gambar dari bucket S3 — tidak satu pun dijamin
mengirimkannya. Memasangnya berarti halaman kehilangan skrip, font, dan gambarnya sekaligus:
gejalanya persis sama dengan keluhan "semua gambar rusak". Prasyaratnya bukan mengubah satu
baris header, melainkan menghosting aset CDN secara lokal lebih dulu (langkah 4 pada rencana
CSP Readiness). Sesudah itu barulah `require-corp` bisa dipasang tanpa mematikan halaman.

**COOP dipilih `same-origin-allow-popups`, bukan `same-origin`.** Aplikasi ini benar-benar
memakai pola opener: `previewPDFs()` di `mains.js:3705` membuka `window.open('', '_blank')` lalu
mengisinya lewat `winTab.document.write()`. Nilai yang dipilih menjaga referensi ke popup yang
dibuka sendiri, sambil tetap mengisolasi halaman dari opener lintas-origin. Kekeliruan di sini
tidak akan terlihat sampai ada yang mencoba mencetak laporan — karena itu dikunci test.

**`publickey-credentials-get` sengaja TIDAK dicantumkan di Permissions-Policy.** Defaultnya sudah
`self`; menuliskannya sebagai `()` akan mematikan login biometrik WebAuthn. Bentuknya persis
seperti pengetatan biasa, jadi mudah masuk tanpa disadari, dan gagalnya baru ketahuan saat ada
yang menempelkan jarinya ke sensor. `interest-cohort` (FLoC) yang disebut rekomendasi juga tidak
ikut: fiturnya sudah ditarik dari browser.

**Dampak ke SSO: tidak ada.** Diperiksa satu per satu:

| Jalur | Kena CSP/COOP? |
|---|---|
| Tombol "Login SSO" (`login.php:475`) | `<a href>` ke `sso/login` — same-origin, tidak tersentuh |
| `SsoAuth::start()` → dashboard | redirect 302 tingkat atas; tidak ada direktif CSP yang membatasi navigasi ini (`form-action` hanya berlaku untuk submit form) |
| Dashboard → `auth/sso-callback?ticket=…` | navigasi masuk, tidak tersentuh |
| `SsoSlo` → `auth-sso-api` | HTTP dari sisi server, di luar jangkauan CSP browser |
| `Login::logout()` → dashboard | redirect 302, sama seperti di atas |

Satu-satunya cara COOP bisa mengganggu SSO adalah bila dashboard membuka SYS lewat popup dan
menyimpan `window.opener`-nya. Alurnya berbasis redirect penuh, bukan popup, jadi tidak berlaku
— tetapi kalau suatu saat dashboard beralih ke popup, header inilah yang pertama harus ditinjau.

**Verifikasi di browser (bukan hanya di test).** Halaman login dimuat di
`http://localhost/sys-modern/login`, lalu header respons dan console diperiksa langsung:

- Header terkirim persis seperti tabel di atas; `cross-origin-embedder-policy` bernilai `null`.
- Halaman render normal, **logo dan gambar tampil** — tidak ada regresi dari keluhan sebelumnya.
- Console tidak memuat satu pun pelanggaran untuk `base-uri`, `object-src`, maupun
  `frame-ancestors`. Ketiga direktif baru itu tidak menyentuh perilaku yang ada.
- `document.featurePolicy.allowsFeature('publickey-credentials-get')` → `true`, dan
  `…('publickey-credentials-create')` → `true`. Login dan pendaftaran biometrik tetap diizinkan.
  `camera` dan `geolocation` → `false`, sesuai maksud.

**Kenapa CSP report-only di development, dan hubungannya dengan "semua gambar rusak".**
Perilaku ini sudah ada sejak 22 Agustus 2026 dan sengaja dipertahankan. Sebabnya ada di spesifikasi
CSP: begitu sebuah **nonce** masuk ke `script-src`/`style-src`, browser **mengabaikan
`unsafe-inline`** pada direktif itu. Debug Toolbar CI4 menyuntikkan nonce, sehingga saat CSP
menegakkan di development, seluruh 47 blok `<script>` dan `<style>` inline di view ikut terblokir
— halaman kehilangan styling dan skrip yang menggambar isinya, yang di layar terlihat seperti aset
rusak. Console pada verifikasi di atas masih menunjukkan persis pesan itu ("`unsafe-inline` is
ignored if either a hash or nonce value is present"), hanya berstatus report-only sehingga tidak
memblokir. **Production (`CI_DEBUG = false`) tetap menegakkan.** Ini kelonggaran development, bukan
kelonggaran produksi — dan ada test yang menjaga agar tidak tertukar.

**Temuan sampingan: header tidak terpasang pada respons yang di-short-circuit filter.**
CodeIgniter menghentikan pipeline begitu sebuah filter `before` mengembalikan Response
(`system/CodeIgniter.php:492`), sehingga filter `after` — termasuk `secureheaders` — tidak pernah
jalan. Artinya setiap redirect yang dilempar `AuthFilter` (dan jawaban 401 AJAX-nya) keluar tanpa
header keamanan. Dampaknya kecil: bodinya kosong dan tidak ada dokumen yang dirender, sehingga CSP
maupun COOP tidak punya apa pun untuk dilindungi di sana. Dicatat karena konsekuensinya nyata untuk
pengujian — `home` bukan halaman yang sah untuk memverifikasi header — dan karena HSTS idealnya
ikut terkirim pada redirect. Perbaikannya menuntut pemindahan `secureheaders` ke filter `required`,
di luar lingkup M-01.

**Yang belum dikerjakan: `unsafe-inline` dan `unsafe-eval` masih ada.** Ini bukan kelalaian
melainkan urutan kerja. Melepasnya menuntut pembersihan yang volumenya sudah diukur:

| Hambatan | Volume terukur |
|---|---|
| Blok `<script>` inline di view | **47 blok pada 44 berkas** |
| `onclick=` di view | 4 |
| `onclick=` yang dibangun formatter jqGrid di JS | 7 |
| `eval()` | di dalam bundel pihak ketiga (jQuery, jqGrid, ajaxfileupload) |

Selama satu saja blok inline tersisa tanpa nonce, melepas `unsafe-inline` akan mematikan halaman
yang memuatnya — dan kegagalannya tidak seragam: sebagian halaman tetap tampil normal, sebagian
lumpuh. Rencana bertahapnya ada di bagian **CSP Readiness**. `eval()` yang tersisa berada di dalam
pustaka pihak ketiga, sehingga `unsafe-eval` baru bisa dilepas setelah pustaka itu diganti atau
di-patch — bukan dengan mengubah kode aplikasi.

**Pengujian.** 13 test di `tests/unit/SecurityHeadersTest.php`: keenam header wajib beserta
nilainya, COOP tidak memutus popup yang dibuka sendiri, COEP tidak dipasang selama aset masih dari
CDN, Permissions-Policy tidak mematikan WebAuthn, lima fitur tak terpakai benar-benar ditutup,
ketiga direktif CSP baru, `unsafe-inline`/`unsafe-eval` masih ada secara sadar (test ini wajib
diperbarui oleh siapa pun yang melepasnya, supaya penghapusannya tidak ditemukan lewat halaman
kosong di production), dan `reportOnly` mengikuti `CI_DEBUG`.

---

#### M-02 — Username Enumeration pada Fitur Lupa Password

**Status:** ✅ SELESAI (31 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Medium
**Lokasi:** `app/Controllers/Login.php:161-167`, `:174-179`

**Potongan Kode:**
```php
if (!$userRow) {
    return $this->response->setStatusCode(400)->setJSON([
        'errors' => ['user' => 'Username tidak ditemukan'],     // membocorkan eksistensi akun
        'csrfToken' => csrf_hash()
    ]);
}
// ...
if (empty($email) && empty($nowhatsapp)) {
    return $this->response->setStatusCode(400)->setJSON([
        'errors' => ['user' => 'Akun ini tidak memiliki email atau nomor WhatsApp terdaftar.'],
    ]);
}
```

**Penjelasan:** Respons berbeda untuk username yang ada dan tidak ada. Menariknya, kode sudah memuat pesan netral yang benar di cabang `$check` (`'Jika username ada, link reset akan dikirim...'`) — namun cabang error tetap membocorkan informasi. Pesan kedua bahkan mengonfirmasi akun ada sekaligus memberi tahu bahwa akun tersebut tidak punya jalur pemulihan.

**Risiko:** Penyerang dapat menyusun daftar username valid untuk dipakai pada credential stuffing. (Sejak H-04 ✅ diperbaiki 24 Agustus 2026, laju enumerasinya sudah tertahan rate limit, tetapi respons yang berbeda-beda tetap membocorkan keberadaan akun.)

**Contoh Kode Perbaikan:**
```php
public function forgotPassword()
{
    $username   = (string) $this->request->getPost('user');
    $muserModel = new \App\Models\MuserModel();
    $userRow    = $muserModel->asArray()->where('userid', $username)->first();

    // Respons seragam untuk SEMUA kasus
    $genericResponse = $this->response->setJSON([
        'status'    => 200,
        'message'   => 'Jika username terdaftar, link reset akan dikirim ke email/WhatsApp Anda.',
        'csrfToken' => csrf_hash(),
    ]);

    if (! $userRow) {
        log_message('info', "Password reset untuk username tidak dikenal: {$username}");
        return $genericResponse;
    }

    $userRow = array_change_key_case($userRow, CASE_LOWER);
    $email   = $userRow['email'] ?? '';

    if (empty($email) && empty($userRow['nowhatsapp'] ?? '')) {
        log_message('warning', "Akun tanpa jalur pemulihan: {$username}");
        return $genericResponse;
    }

    // ... kirim email

    return $genericResponse;
}
```

**Perbaikan (31 Agustus 2026)**

Seluruh jalan keluar `Login::forgotPassword()` sesudah gerbang rate limit kini
melewati satu pintu yang sama, `forgotPasswordResponse()` — 200 dengan kalimat
*"Jika username terdaftar, link reset akan dikirim ke email atau WhatsApp yang
tercatat pada akun tersebut."*

| Keadaan | Sebelum | Sesudah |
|---|---|---|
| Username tidak ada | 400 `Username tidak ditemukan` | 200 pesan seragam; username dicatat ke log |
| Akun tanpa email & WhatsApp | 400 `Akun ini tidak memiliki email...` | 200 pesan seragam; dicatat ke log |
| Email terkirim | 200 `Link reset sudah dikirim...` | 200 pesan seragam |
| SMTP gagal | 500 `belum bisa dikirim saat ini` | 200 pesan seragam; kegagalan tetap masuk log, token yang terlanjur dibuat dihapus |
| Reset dimatikan (SSO-only) | 403 | 403 — tidak bergantung akun, dibiarkan |
| Kena rate limit | 429 | 429 — lihat catatan jatah di bawah |

**Jatah rate limit ikut diseragamkan.** Sebelumnya `hit()` hanya dipanggil saat
email benar-benar dikirim, jadi username tak terdaftar tidak pernah memotong
jatah dan tidak pernah kena 429. Pola itu sendiri sudah cukup untuk memisahkan
akun yang ada dari yang tidak, tanpa perlu membaca isi respons sama sekali.
Sekarang setiap permintaan kirim memotong jatah, terdaftar atau tidak.

Sisi klien (`app/Views/login.php`) dulu menembak endpoint ini dua kali: sekali
`check: true` untuk memastikan username ada, baru sekali lagi untuk mengirim.
Tahap pertama itu ada justru karena server mau menjawab pertanyaan tersebut,
jadi ikut dihapus — tombol reset sekarang satu request. Server tetap menerima
parameter `check` dan menjawabnya seragam supaya halaman lama yang masih
ter-cache di browser tidak mengirim email dua kali.

**Yang belum seragam: waktu respons.** Permintaan untuk akun yang ada selesai
setelah SMTP dipanggil, sedangkan username tak dikenal berhenti lebih awal —
selisihnya masih terukur. Menutupnya menuntut antrean pengiriman terpisah;
untuk sementara laju pengukurannya ditahan rate limit `forgot` (3 percobaan per
jam per akun, 10 per IP).

**Konsekuensi operasional.** Kegagalan SMTP tidak lagi terlihat oleh pengguna:
ia tetap membaca "link akan dikirim". Satu-satunya jejaknya ada di
`writable/logs`, jadi keluhan "link reset tidak sampai" harus dicek di sana.

Regresi dijaga `tests/unit/ForgotPasswordEnumerationTest.php`: memastikan tidak
ada lagi pesan yang memastikan keberadaan akun, setiap `return` setelah
pemotongan jatah memakai `forgotPasswordResponse()`, `hit()` dipanggil sebelum
akun dicari, dan halaman login tidak lagi memakai tahap cek username.

---

#### M-03 — Token Reset Password Berentropi Rendah

**Severity:** Medium
**Lokasi:** `app/Controllers/Login.php:191`

**Potongan Kode:**
```php
$rawToken = substr(bin2hex(random_bytes(32)), 0, 11);   // 32 byte dipotong jadi 11 hex
$hashedToken = hash('sha256', $rawToken);
```

**Penjelasan:** `random_bytes(32)` menghasilkan 256 bit entropi, namun `substr(..., 0, 11)` memangkasnya menjadi 11 karakter heksadesimal = **44 bit**. Penyimpanan sebagai SHA-256 dan perbandingan `hash_equals()` sudah benar; masalahnya murni pada panjang token. Dengan masa berlaku 1 jam, ruang tebak 2^44 tidak memberi margin keamanan yang memadai. (Endpoint resetnya sendiri sudah dibatasi sejak H-04 ✅ 24 Agustus 2026, tetapi entropi token tetap perlu dinaikkan.)

**Contoh Kode Perbaikan:**
```php
// Gunakan token penuh (64 karakter hex = 256 bit)
$rawToken    = bin2hex(random_bytes(32));
$hashedToken = hash('sha256', $rawToken);

$resetModel->insert([
    'username'   => $username,
    'token'      => $hashedToken,
    'created_at' => date('Y-m-d H:i:s'),
    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
]);

// Kirim token sebagai query parameter, bukan bagian dari path
$resetLink = base_url('reset-password') . '?' . http_build_query([
    'user'  => $username,
    'token' => $rawToken,
]);
```
Perubahan ini sekaligus menghapus kebutuhan akan format URL `-dd-dd-dddd-...` yang memicu bypass C-04.

---

#### M-04 — Endpoint Pengubah State Menerima Method GET

**Severity:** Medium
**Lokasi:** `app/Config/Routes.php` (pola berulang), `app/Controllers/GridPreference.php`

**Potongan Kode:**
```php
$routes->match(['GET', 'POST'], 'Cabang/crud', 'Cabang::crud');
$routes->match(['GET', 'POST'], 'cabang/crud', 'Cabang::crud');
$routes->get('gridpreference/delete', 'GridPreference::delete');    // DELETE lewat GET
```

**Penjelasan:** Operasi `crud`, `operation`, dan `delete` dapat dipanggil dengan GET. Ini melanggar prinsip HTTP method safety, membuat aksi destruktif dapat dipicu lewat `<img src="...">` atau prefetch browser, dan membuat parameter aksi tercatat di access log server. Digabung dengan CSRF yang mati (C-03), eksploitasinya sepele.

**Contoh Kode Perbaikan:**
```php
// app/Config/Routes.php — pisahkan method sesuai semantiknya
$routes->group('cabang', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/',    'Cabang::index');
    $routes->get('grid', 'Cabang::grid');
    $routes->get('form/(:segment)/(:segment)', 'Cabang::form/$1/$2');
    $routes->post('crud', 'Cabang::crud');          // POST saja
    $routes->post('operation/(:segment)', 'Cabang::operation/$1');
});

$routes->get('gridpreference/load',  'GridPreference::load');
$routes->post('gridpreference/save', 'GridPreference::save');
$routes->delete('gridpreference/(:segment)', 'GridPreference::delete/$1');
```

---

#### M-05 — Host Header Injection pada Pembentukan `baseURL`

**Status:** ✅ SELESAI (31 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Medium
**Lokasi:** `app/Config/App.php` (constructor), `$allowedHostnames = []`

**Potongan Kode:**
```php
public function __construct()
{
    if (isset($_SERVER['HTTP_HOST'])) {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $scheme = 'https';
        } else { ... }

        $host = $_SERVER['HTTP_HOST'];                       // <-- input penyerang
        $this->baseURL = $scheme . '://' . $host . '/' . trim($folder, '/') . '/';
    }
}
```

**Penjelasan:** `baseURL` dibangun dari header `Host` yang dikirim klien, sementara `$allowedHostnames` dibiarkan kosong sehingga CI4 tidak memvalidasi host. Header `X-Forwarded-Proto` juga dipercaya tanpa memeriksa apakah request benar-benar datang dari reverse proxy tepercaya (`$proxyIPs` kosong).

**Risiko:** Karena `Login::forgotPassword` membangun link reset dengan `base_url()` (`Login.php:196`), penyerang dapat memicu pengiriman email reset yang **link-nya menunjuk ke domain penyerang** — token reset korban terkirim ke server penyerang begitu link diklik. Juga membuka cache poisoning.

**Contoh Kode Perbaikan:**
```php
// app/Config/App.php
public string $baseURL = 'https://sys.karaya.site/sys-modern/';

// Whitelist host — CI4 akan menolak Host header di luar daftar ini
public array $allowedHostnames = ['sys.karaya.site'];

// Daftarkan IP reverse proxy agar X-Forwarded-* hanya dipercaya dari sana
public array $proxyIPs = ['192.168.4.1' => 'X-Forwarded-For'];
```
Hapus constructor dinamis. Bila perlu multi-environment, atur lewat `.env`:
```dotenv
app.baseURL = 'https://sys.karaya.site/sys-modern/'
```

**Perbaikan (31 Agustus 2026)**

Constructor dinamisnya **dipertahankan**, tetapi host request sekarang harus
lolos daftar putih sebelum boleh dipakai. Menghapus constructor sepenuhnya —
seperti saran di atas — berarti setiap server harus lebih dulu punya
`app.baseURL` yang benar di `.env`; sampai itu terjadi, penghapusan justru
memutus staging dan production yang sekarang mengandalkan `app.folder`
(lihat `dokumentasi_sso.md` dan `tests/unit/BaseUrlFolderTest.php`).

Alur `Config\App::__construct()` sesudah perbaikan:

| Langkah | Perilaku |
|---|---|
| `app.baseURL` dari `.env` | Dipakai sebagai nilai statis. Dulu tidak dibaca sama sekali |
| Daftar putih | `app.allowedHostnames` di `.env`, atau bawaan `sys.transporindo.com`, `staging.transporindo.com`, `localhost`, `127.0.0.1`. Host milik `app.baseURL` selalu ikut |
| Host request lolos daftar | baseURL disusun seperti sebelumnya: skema + host + `app.folder` |
| Host request **tidak** lolos | `$baseURL` tidak disentuh — aplikasi memakai nilai statis dari `.env` |

Pemisahan host dan port diserahkan ke `parse_url()`, jadi bentuk yang
menyelipkan sesuatu (`sys.transporindo.com/evil.example`,
`evil.example@sys.transporindo.com`) gugur sebagai host yang tidak wajar, bukan
ikut terangkai ke dalam url. Huruf besar dan titik penutup dinormalkan.

Properti `$allowedHostnames` yang tadinya kosong kini terisi, sehingga
`SiteURIFactory::getValidHost()` — jalur framework untuk `current_url()` —
memakai daftar yang sama.

**Dampaknya pada M-02/M-03:** link reset password dirakit dengan `base_url()`,
jadi inilah yang menutup jalur "kirim email reset yang link-nya menunjuk ke
server penyerang".

**Sisa yang diketahui:**

1. `X-Forwarded-Proto` masih dipercaya tanpa memeriksa `$proxyIPs`, karena
   alamat cloudflared di tiap server belum dipastikan. Yang bisa dipengaruhinya
   sekarang tinggal pemilihan `http`/`https` untuk host yang **sudah** lolos
   daftar putih — bukan lagi ke mana url menunjuk. Selebihnya menyusul H-08.
2. `Config\App` tidak memanggil `parent::__construct()`, jadi override `.env`
   bawaan CI4 tidak berlaku untuk config ini — termasuk `app.forceGlobalSecureRequests`
   dan `app.CSPEnabled` yang tercantum sebagai contoh di berkas `env`. Karena
   itu daftar putih dan `app.baseURL` dibaca eksplisit dengan `getenv()`,
   mengikuti cara `app.folder` sudah dibaca sejak awal.
3. Bawaan daftar putih mengikuti tiga environment yang terdokumentasi. Server
   yang memakai domain lain (mis. `sys.karaya.site` pada contoh di atas) harus
   mengisi `app.allowedHostnames` atau `app.baseURL` di `.env`-nya.

Regresi dijaga `tests/unit/HostHeaderInjectionTest.php` (host asing, host yang
menumpang host sah, port lokal, normalisasi penulisan, daftar dari `.env`, host
`app.baseURL`, dan keterbacaan daftar oleh framework). `BaseUrlFolderTest`
tetap hijau, jadi bentuk url ketiga environment tidak berubah.

---

#### M-06 — Query Tanpa Batas untuk Menghitung Total Baris (Risiko DoS)

**Severity:** Medium
**Lokasi:** `app/Models/MomsetModel.php:35-40` dan pola yang sama di seluruh model grid; `app/Controllers/Omset.php:62-63`

**Potongan Kode:**
```php
// Model: mengambil SELURUH baris
public function count($where, $cabang)
{
    $table = $this->getTableName($cabang);
    $whereClause = trim($where) !== "" ? "WHERE 1=1 " . $where : "";
    $sql = $this->dbtruck->query("SELECT FTgl FROM $table $whereClause");   // tanpa COUNT()
    return $sql;
}
```
```php
// Controller: lalu menghitungnya di PHP
$sql   = $this->momsetModel->count($where, $cabang);
$count = $sql->getNumRows();
```

**Penjelasan:** Untuk sekadar mendapatkan jumlah baris, aplikasi menarik **seluruh result set** dari SQL Server ke memori PHP, lalu memanggil `getNumRows()`. Pada tabel omset multi-tahun ini berarti transfer ratusan ribu baris per request grid. Pola ini berulang di lebih dari 20 model. Selain itu `get_whereMDN()` s.d. `get_whereSMG()` (`MomsetModel.php:86-90`) melakukan `SELECT *` tanpa `TOP`/`OFFSET` sama sekali.

**Risiko:** Kehabisan memori PHP dan koneksi database jenuh; beberapa request paralel cukup untuk membuat aplikasi tidak responsif (DoS). Juga penyebab utama lambatnya grid.

**Contoh Kode Perbaikan:**
```php
public function count(string $where, array $binds, string $cabang): int
{
    $table = $this->getTableName($cabang);

    $row = $this->dbtruck->query(
        "SELECT COUNT(*) AS total FROM {$table} WHERE 1=1 {$where}",
        $binds
    )->getRow();

    return (int) ($row->total ?? 0);
}
```
```php
// Controller
$count       = $this->momsetModel->count($where, $binds, $cabang);
$total_pages = $count > 0 ? (int) ceil($count / $limit) : 0;

// Batasi ukuran halaman agar client tidak dapat meminta seluruh tabel
$limit = min(max((int) $this->request->getPost('rows') ?: 50, 1), 500);
```

---

#### M-07 — Audit Trail Tidak Memadai

**Status:** ✅ SUDAH DIPERBAIKI (29 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Medium
**Lokasi:** `app/Models/MlogModel.php:25-39`; pemanggilan hanya di `Login.php:82`, `Login.php:130`, `Webauthn.php:330`

**Penjelasan:** Perbandingan terhadap poin 11 standar:

| Yang diminta standar | Status |
|---|---|
| Login berhasil | **Tercatat** (`saveLog`) |
| Login gagal | **Tidak dicatat sama sekali** |
| Perubahan data | **Tidak ada**; `saveLog()` tidak dipanggil dari satu pun controller CRUD |
| Delete data | **Tidak ada** |
| Audit trail | Tabel `log_activity` ada, tapi tidak menyimpan nilai lama/baru |

`saveLog()` juga mencatat `controller`/`action` dari router, bukan deskripsi bisnis, dan parameter `$data` yang diterima hanya dipakai untuk `basename(FCPATH)` — nilainya tidak pernah benar-benar dicatat.

**Risiko:** OWASP A09. Serangan brute force tidak terdeteksi karena kegagalan login tidak meninggalkan jejak. Bila terjadi manipulasi data, tidak ada cara mengetahui siapa yang mengubah apa dan kapan — forensik pasca-insiden mustahil.

**Contoh Kode Perbaikan:**
```php
// app/Models/MlogModel.php
public function saveLog(string $event, ?string $message = null, array $context = []): void
{
    $router = service('router');

    $this->db->table('log_activity')->insert([
        'user_id'    => session()->get(SESSION_NAME . 'userpk') ?: 0,
        'userid'     => session()->get(SESSION_NAME . 'userid') ?: '-',
        'event'      => $event,   // LOGIN_SUCCESS | LOGIN_FAILED | DATA_UPDATE | DATA_DELETE
        'controller' => $router->controllerName(),
        'action'     => $router->methodName(),
        'message'    => $message,
        'context'    => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
        'ip'         => service('request')->getIPAddress(),
        'user_agent' => (string) service('request')->getUserAgent(),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
```
```php
// Login gagal — app/Controllers/Login.php
$this->mlogModel->saveLog('LOGIN_FAILED', 'Kombinasi userid/password salah', ['userid' => $userid]);

return redirect()->to(base_url('login'))
    ->with(SESSION_NAME . 'message', 'Kombinasi userid Atau Password Salah');
```
```php
// Perubahan data — pada setiap controller CRUD
$before = $this->model->find($id);
$this->model->update($id, $data);
$this->mlogModel->saveLog('DATA_UPDATE', 'Ubah data cabang', [
    'id'     => $id,
    'before' => $before,
    'after'  => $data,
]);
```

**Catatan penutup (29 Agustus 2026).**

`MlogModel::saveLog()` dirombak dari `saveLog($controller, $message, $messageError)` menjadi
`saveLog(string $event, ?string $message, array $context, ?string $messageError)`. Argumen pertama
yang lama hanya dipakai untuk `basename(FCPATH)` — nilainya tidak pernah benar-benar dicatat —
sehingga menggantinya tidak menghilangkan informasi apa pun. Empat pemanggil lama ikut disesuaikan.

**Kosakata peristiwa** (konstanta di `MlogModel`, bukan string bebas, supaya log bisa disaring
dengan `WHERE event = …` alih-alih `LIKE` pada teks pesan): `LOGIN_SUCCESS`, `LOGIN_FAILED`,
`LOGIN_BLOCKED`, `LOGOUT`, `UNLOCK_SUCCESS`, `UNLOCK_FAILED`, `DATA_CREATE`, `DATA_UPDATE`,
`DATA_DELETE`.

**Skema.** Migrasi `2026-08-29-090000_AuditTrailLogActivity` menambahkan empat kolom nullable ke
`log_activity` — `event`, `userid_login`, `context` (JSON, `NVARCHAR(MAX)`), `created_at` — plus indeks
`IX_log_activity_event_created`. Ditulis sebagai SQL mentah dengan penjaga `COL_LENGTH(...) IS NULL`
karena tabelnya lahir sebelum proyek ini memakai migrasi dan bentuknya bisa berbeda antar server.
`down()` sengaja hanya melepas indeks: rollback skema bukan alasan yang sah untuk membuang jejak
audit yang sudah terkumpul.

**Tabel ini sebelumnya tidak punya kolom waktu sama sekali.** Sembilan kolom aslinya — `id`,
`user_id`, `module`, `controller`, `action`, `message`, `message_error`, `ip`, `detect` — tidak satu
pun menyimpan kapan kejadiannya. Ini kekurangan yang lebih mendasar daripada yang tercatat pada
temuan awal: log tanpa waktu tidak bisa menjawab separuh dari pertanyaan yang membuatnya ada, dan
seluruh riwayat login yang sudah terkumpul hanya bisa diurutkan lewat `id`. `created_at` menutupnya
untuk baris baru; **baris lama akan tetap `NULL` selamanya**, karena tidak ada sumber untuk
mengisinya surut.

**Catatan penamaan.** Kolom string login diberi nama `userid_login`, bukan `userid`, karena tabel ini
sudah punya `user_id`. Dua kolom yang bedanya satu garis bawah dan sama-sama berarti "user" adalah
jebakan diam — query yang mengambil kolom keliru tetap memberi hasil, hanya isinya bukan yang dikira
penulisnya. Pembagiannya: `user_id` = `userpk` (int, `0` saat sesi anonim); `userid_login` = string
login, dan pada `LOGIN_FAILED` berisi userid yang **dicoba**.

**Yang sekarang tercatat.**

| Peristiwa | Di mana |
|---|---|
| Login berhasil — password, SSO, biometrik | `Login::proses()`, `SsoAuth::callback()`, `Webauthn::processLogin()` |
| **Login gagal** | `Login::proses()` (userid yang dicoba masuk `context`) |
| **Tiket SSO ditolak** | `SsoAuth::fail()` — kode penolakan masuk `context`, alasan lengkap ke `message_error` |
| **Ditolak rate limit** | `Login::logThrottled()` — mencakup login, unlock, dan forgot-password; sekali per jendela hukuman, lihat catatan pembanjiran log di bawah |
| **Logout** | `Login::logout()`, ditulis **sebelum** `session()->destroy()` |
| **Single Logout (sesi dicabut dari dashboard SSO)** | `AuthFilter::enforceSingleLogout()`, juga sebelum `destroy()` |
| Buka/gagal buka lock screen | `Login::unlock()` |
| Biometrik gagal / kredensial milik user lain | `Webauthn::processLogin()` |
| **Tambah, ubah, hapus data** | `User`, `Roles`, `Menu`, `Parameter`, `UserAcl`, `Profil` |

**Jalur SSO.** Ketiga peristiwanya ikut tertutup, dan dua di antaranya sebelumnya tidak berjejak sama
sekali di production:

- `SsoAuth::fail()` menolak tiket dengan tujuh kode berbeda. Dua di antaranya bukan sekadar salah
  konfigurasi: `invalid` berarti ada tiket dengan tanda tangan yang tidak sah, dan `replay` berarti
  tiket yang sama dipakai dua kali. Keduanya kini tercatat sebagai `LOGIN_FAILED` sehingga bisa
  dihitung dan disandingkan dengan percobaan login jalur lain.
- `AuthFilter::enforceSingleLogout()` mengakhiri sesi ketika sesi SSO-nya dicabut dari dashboard.
  Jejaknya dulu hanya `log_message('info', …)` — dan `Config\Logger` memakai ambang 4 di production,
  jadi baris itu **tidak pernah sampai ke berkas** justru di environment tempat ia paling dibutuhkan.
- Panel Casting (login-as) sudah tercatat sejak sebelumnya lewat `AuditUser::describe()` dan
  dipertahankan apa adanya — lihat paragraf di bawah.

**Nilai lama/baru.** `MlogModel::changes($sebelum, $sesudah)` menyisakan hanya kolom yang benar-benar
berbeda, sebagai `{"kolom":{"dari":…,"ke":…}}`. Menyimpan seluruh baris dua kali membuat `context`
didominasi nilai yang tidak bergerak dan justru menyembunyikan satu kolom yang diubah. Perbandingannya
longgar (`!=`) dengan sengaja: nilai dari SQL Server datang sebagai string sedangkan nilai dari POST
sering integer, dan perbandingan ketat akan menandai setiap angka sebagai "berubah" di setiap
penyimpanan. Setiap controller membaca kondisi lama **sebelum** menyimpan — sesudahnya nilai itu
sudah hilang.

**Redaksi.** Hash password, token, dan kunci rahasia diganti `***` sebelum di-`json_encode`, termasuk
yang bersarang di dalam hasil `changes()`. Tabel log dibaca lebih banyak orang daripada tabel asalnya;
tanpa ini, `context` menjadi tempat penampungan kedua untuk hash password.

**Kegagalan menulis log tidak boleh menggagalkan aksi penggunanya.** `saveLog()` menelan exception-nya
sendiri dan melaporkannya ke `writable/logs`. Login yang sah harus tetap jadi meski tabel log
bermasalah. Untuk alasan yang sama, payload disaring lebih dulu terhadap `getFieldNames('log_activity')`:
sebelum migrasi dijalankan, kolom baru dibuang diam-diam dan aplikasi tetap mencatat seadanya alih-alih
melempar "Invalid column name". `userid_login` dan `ip` juga dipotong ke panjang kolomnya: `ip()` membaca
header `X-Forwarded-For` yang dikendalikan klien, dan tanpa batas itu sebuah header raksasa cukup untuk
membuat `INSERT` gagal — sehingga percobaan login yang mengirimnya justru menjadi satu-satunya yang
tidak meninggalkan jejak.

**Panel Casting.** Jejak impersonation yang sudah ada (`AuditUser::describe()` disisipkan ke `message`)
tetap dipertahankan, sehingga baris log dari sesi login-as tidak terbaca seolah-olah dikerjakan oleh
pemilik akun.

**Yang belum disentuh.** `GridPreference::save()`/`delete()` tidak dicatat — isinya preferensi tampilan
kolom per-user, bukan data bisnis, dan mencatatnya hanya akan membanjiri tabel log setiap kali seseorang
menggeser lebar kolom. Belum ada halaman untuk **membaca** audit trail; saat ini pembacaan dilakukan
lewat query langsung ke `log_activity`.

**Pembanjiran log.** Tiga peristiwa baru ditulis dari endpoint yang bisa dipanggil **tanpa
autentikasi**: `LOGIN_FAILED` di `Login::proses()`, `LOGIN_BLOCKED` di `Login::logThrottled()`, dan
`LOGIN_FAILED` di `SsoAuth::fail()`. Yang paling rawan di antaranya adalah `LOGIN_BLOCKED`: ia justru
ditulis **setelah** `LoginThrottle` menolak percobaan, sehingga penolakan yang seharusnya murah malah
menghasilkan satu baris database per request — penyerang yang menghantam `/login/proses` dapat
menumbuhkan `log_activity` sesukanya, tabel yang justru dipakai untuk melacak dirinya.

`LoginThrottle::announceOnce()` menutup itu: `LOGIN_BLOCKED` kini ditulis **sekali per jendela
hukuman**, bukan sekali per request. Penandanya disimpan di cache dengan TTL selebar sisa waktu tunggu
(berlantai 60 detik, karena sisa waktu menyusut tiap detik dan bisa tinggal 1 di ujung jendela).

Kuncinya sengaja diturunkan dari **ember yang menahan**, bukan dari pasangan ip+akun. Bedanya baru
terasa saat password spraying: seribu akun berbeda dari satu IP sama-sama ditahan oleh ember IP yang
sama, jadi menghasilkan satu baris — bukan seribu. Mengunci pada ip+akun akan membuat gerbang ini
tidak berguna persis pada serangan yang paling perlu ditahan. Sinyalnya tidak hilang: yang dicatat
adalah *episode* penolakan, sedangkan frekuensi mentah percobaan tetap terbaca dari baris
`LOGIN_FAILED`. Berkas `writable/logs` sengaja tetap menerima satu baris per request — berkas itu
dirotasi, tabel log tidak.

Gerbangnya tidak atomik: dua request bersamaan bisa lolos berdua dan menulis dua baris. Untuk dedup
catatan audit itu tidak merugikan, dan cache handler `file` yang dipakai aplikasi ini memang tidak
menyediakan add-if-absent.

**Retensi — masih perlu keputusan.** `LOGIN_FAILED` (lokal maupun SSO) tetap satu baris per percobaan,
dan memang harus begitu. Tabel ini akan tumbuh jauh lebih cepat daripada sebelumnya, sementara
kebijakan retensi/arsip untuknya belum ada.

**Pengujian.** 18 test unit di `tests/unit/AuditTrailTest.php`: perilaku `changes()` (hanya kolom
berubah, beda tipe pada angka yang sama tidak dihitung berubah, kolom baru berangkat dari `null`,
menerima objek maupun array), redaksi rahasia termasuk yang bersarang, `context` kosong tidak menulis
JSON kosong, tidak ada lagi pemanggil `saveLog($this)` di seluruh `app/`, setiap controller CRUD
memanggil `auditLog()` dengan peristiwa yang sesuai, penolakan tiket SSO tercatat, `LOGIN_BLOCKED`
melewati gerbang `announceOnce()` lebih dulu, serta log `LOGOUT` — baik dari `Login::logout()` maupun
dari Single Logout — benar-benar ditulis sebelum `session()->destroy()`.

Ditambah 5 test di `tests/unit/LoginThrottleTest.php` untuk `announceOnce()` sendiri: diam saat tidak
ada yang ditahan, 100 penolakan beruntun menghasilkan satu catatan, spraying 100 akun dari satu IP juga
satu catatan, dua akun yang terkunci karena embernya masing-masing tetap dua catatan, dan ember
`login` vs `forgot` punya catatan sendiri-sendiri.

---

#### M-08 — Lock Screen Ditegakkan Hanya di Sisi Klien

**Severity:** Medium
**Lokasi:** `public/libraries/tas-lib/js/lockscreen.js:1, 42, 117-149`, `app/Controllers/Login.php:96-142`

**Potongan Kode:**
```javascript
const IDLE_TIMEOUT = 15 * 60 * 1000;
if (localStorage.getItem(LOCKED_KEY) === 'true') { ... }
localStorage.setItem(LAST_ACTIVITY_KEY, String(now));
```

**Penjelasan:** Status terkunci dan waktu aktivitas terakhir seluruhnya disimpan di `localStorage`. Server tidak menyimpan penanda "sesi terkunci" apa pun — sesi PHP tetap sepenuhnya valid selama 7200 detik. Menghapus key localStorage lewat DevTools, atau memanggil endpoint API secara langsung, sepenuhnya melewati lock screen.

Terkait ini, `Login::unlock()` menerima `userid` dari POST ketika sesi server sudah kedaluwarsa dan membangun ulang sesi login penuh — secara efektif menjadi endpoint login alternatif. Sejak H-04 ✅ (24 Agustus 2026) endpoint itu memakai ember rate limit yang sama dengan halaman login, sehingga tidak lagi bisa dipakai mengelak dari batas percobaan.

**Contoh Kode Perbaikan:**
```php
// app/Filters/AuthFilter.php — tegakkan idle timeout di server
public function before(RequestInterface $request, $arguments = null)
{
    $session = session();

    if (! $session->get(SESSION_NAME . 'logged_in')) {
        return redirect()->to(base_url('login'));
    }

    $idleLimit = 15 * 60;
    $last      = (int) $session->get(SESSION_NAME . 'last_activity');

    if ($last > 0 && (time() - $last) > $idleLimit) {
        $session->set(SESSION_NAME . 'locked', true);     // status terkunci di SERVER
    }

    // Saat terkunci, hanya endpoint unlock yang boleh diakses
    if ($session->get(SESSION_NAME . 'locked')
        && ! str_contains(strtolower($request->getUri()->getPath()), 'login/unlock')) {

        return service('response')->setStatusCode(423)   // 423 Locked
            ->setJSON(['locked' => true, 'message' => 'Sesi terkunci. Masukkan password.']);
    }

    $session->set(SESSION_NAME . 'last_activity', time());
}
```
```php
// Login::unlock() — hapus jalur relogin dari POST; hanya buka kunci sesi yang ada
public function unlock()
{
    $userid = session()->get(SESSION_NAME . 'userid');
    if (! $userid) {
        return $this->response->setStatusCode(401)
            ->setJSON(['success' => false, 'message' => 'Sesi berakhir. Silakan login ulang.']);
    }

    if (service('throttler')->check('unlock-' . md5($userid), 5, MINUTE) === false) {
        return $this->response->setStatusCode(429)
            ->setJSON(['success' => false, 'message' => 'Terlalu banyak percobaan.']);
    }

    $cek = $this->mloginModel->login($userid, (string) $this->request->getPost('password'));
    if ($cek && $cek->getNumRows() > 0) {
        session()->remove(SESSION_NAME . 'locked');
        session()->set(SESSION_NAME . 'last_activity', time());
        return $this->response->setJSON(['success' => true]);
    }

    $this->mlogModel->saveLog('UNLOCK_FAILED', 'Gagal membuka lock screen', ['userid' => $userid]);
    return $this->response->setJSON(['success' => false, 'message' => 'Password salah']);
}
```

---

#### M-09 — XSS: Flashdata dan Pesan Validasi Masuk ke Konteks JS/HTML Tanpa Escaping

**Status:** ✅ SELESAI (31 Agustus 2026) — lihat catatan penutup di akhir item ini.
**Severity:** Medium — namun temuan saat perbaikan menaikkan dampak nyatanya (lihat "Temuan Tambahan")
**Lokasi:** `app/Views/grafik/grafikbiayakantorbandinglaba.php:92`, `app/Views/auth/reset_password.php:261`, `app/Views/home.php:8-13`

**Potongan Kode:**
```php
// grafikbiayakantorbandinglaba.php:92 — masuk ke dalam string literal JavaScript
showDialog('<?= session()->getFlashdata('error_grafik') ?>');
```
```php
// reset_password.php:261 — konteks HTML
<div class="text-danger"><?= $validationErrors['password'] ?></div>
```
```php
// home.php:8-13
<div class="small-box <?= $btn['color'] ?>">
<p ...><?= $btn['title'] ?></p>
<i class="<?= $btn['icon'] ?>"></i>
```

**Penjelasan:** Nilai dikeluarkan tanpa `esc()`. Pada kasus `showDialog(...)` konteksnya adalah **string JavaScript**, sehingga karakter kutip tunggal saja sudah cukup untuk keluar dari literal — `esc($v, 'html')` pun tidak memadai di sini, diperlukan `esc($v, 'js')` atau `json_encode()`.

Catatan asli audit menyebut nilai-nilai ini bersumber dari array literal di controller dan pesan validasi framework, sehingga belum langsung dapat dieksploitasi. **Penelusuran saat perbaikan membantah asumsi itu** — lihat bagian berikutnya.

**Contoh Kode Perbaikan:**
```php
<!-- Konteks JavaScript: gunakan json_encode, bukan kutip manual -->
<?php if (session()->getFlashdata('error_grafik')) : ?>
    showDialog(<?= json_encode(session()->getFlashdata('error_grafik'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
<?php endif; ?>
```
```php
<!-- Konteks HTML -->
<div class="text-danger"><?= esc($validationErrors['password']) ?></div>

<!-- Konteks atribut -->
<div class="small-box <?= esc($btn['color'], 'attr') ?>">
    <p><?= esc($btn['title']) ?></p>
    <i class="<?= esc($btn['icon'], 'attr') ?>"></i>
</div>
```

---

**Temuan Tambahan Saat Perbaikan (31 Agustus 2026)**

Penyisiran seluruh `app/Views/**` — bukan hanya tiga lokasi yang tercatat — menemukan
dua titik keluar yang **jauh lebih parah** daripada perkiraan audit awal. Keduanya tidak
bergantung pada "kalau nanti ada input user yang mengalir ke sana": jalurnya sudah
terbuka sejak awal.

**1. Reflected XSS langsung — `app/Views/useracl/index.php:13`**

```php
var userpk = "<?= $userpk ?>";     // di dalam <script>
```

`$userpk` diambil mentah dari query string:

```php
// app/Controllers/UserAcl.php:42
$userpk = $this->request->getGet('userpk');
```

Tidak ada validasi bentuk, tidak ada whitelist, dan nilainya langsung dijepit kutip
ganda di dalam blok skrip. Satu kutip ganda pada URL sudah cukup untuk keluar dari
literal dan mengeksekusi skrip:

```
/useracl?userpk="; alert(document.cookie); //
```

Halaman ini berada di belakang autentikasi, jadi bentuk eksploitasinya adalah
*authenticated reflected XSS* — penyerang mengirimkan tautan kepada admin yang sedang
login. Karena CSP masih memakai `unsafe-inline` (M-01), tidak ada lapisan kedua yang
menahannya, dan halaman yang menjadi korban justru halaman **pengaturan hak akses**.

**2. Stored XSS lewat menu — `app/Helpers/my_helper.php:95-148`**

`print_sidebar_menu()` merangkai HTML sidebar dari tabel menu tanpa escaping sama
sekali — `menuname` masuk ke atribut `id` sekaligus menjadi teks, `menuicon` ke atribut
`class`, dan `link` ke atribut `href`:

```php
$str .= '<a id="link-' . strtolower($list['menuname']) . '" href="' . $menuexe . '" class="nav-link">';
$str .= '<i class="nav-icon ' . $iconClass . '"></i>';
$str .= '<p>' . strtoupper($list['menuname']);
```

Ketiga kolom itu dapat disunting lewat CRUD Menu, dan hasilnya dirender pada
**setiap halaman untuk setiap pengguna**. Satu baris menu yang berisi markup cukup
untuk menanam skrip di seluruh aplikasi, dan bertahan sampai baris itu diperbaiki.

**3. Data grafik ditempel sebagai kode JavaScript, bukan sebagai data**

```php
xAxis: { categories: [<?= implode(',', $FTglJKT) ?>] },
```

Controller sengaja menyisipkan kutip tunggalnya sendiri (`$bulan[] = "'" . $label . "'"`)
supaya `implode()` menghasilkan potongan kode JS yang valid. Artinya isi tabel
ditempelkan sebagai **kode**, bukan sebagai data — dan sisi klien bahkan sudah punya
tambalannya sendiri (`// Bersihkan single quote dari PHP pada kategori`). Polanya
diganti: controller berhenti menjepit kutip, view memakai `json_encode()`.

---

**Perbaikan yang Diterapkan (31 Agustus 2026)**

Prinsip yang dipakai: **escape di titik keluar, sesuai konteksnya** — `json_encode()`
untuk literal JavaScript, `esc($v)` untuk teks HTML, `esc($v, 'attr')` untuk isi
atribut. Untuk HTML yang memang dirakit di PHP (sidebar, `<option>`), escaping dipasang
di titik perakitan supaya view tetap boleh mencetaknya mentah.

| Konteks | Berkas | Perubahan |
|---|---|---|
| **JS (query string)** | `app/Views/useracl/index.php` | `var userpk` lewat `json_encode()` — tidak lagi dijepit kutip manual |
| **JS (flashdata)** | `app/Views/grafik/grafikbiayakantorbandinglaba.php` | `showDialog()` menerima literal dari `json_encode()` |
| **JS (sesi)** | `app/Views/home.php`, `app/Views/partials/footer.php` | `userid` sesi lewat `json_encode()` |
| **JS (filter)** | `app/Views/grafik/grafikbiayakantorbandinglaba.php` | `esc($v)` → `esc($v, 'js')` pada `lastFetchedData` |
| **JS (data grafik)** | `app/Controllers/Grafikbiayakantorbandinglaba.php`, `Grafiktradoluar.php`, `Grafikemklluar.php` + 3 view | Label berhenti membawa kutip sendiri; `implode()` → `json_encode()`; cabang kosong mengembalikan `[]`, bukan string `'[]'` |
| **HTML (pesan validasi)** | `app/Views/login.php`, `app/Views/auth/reset_password.php` | `esc()` pada `$validationErrors[...]` dan `$error` |
| **HTML (sink DOM)** | `app/Views/login.php` | `showDialog()`/`showSuccessDialog()` menyaring pesan lewat `escapeHtml()` sebelum `.html()` |
| **HTML (menu)** | `app/Helpers/my_helper.php` | `print_sidebar_menu()` meng-escape `menuname`, `menuicon`, dan `href` sesuai konteks |
| **HTML (`<option>`)** | `app/Controllers/Truckingtradoluartas{,jkt,mks,sby}.php` | `FBulan`/`FJenisTrado` di-escape saat HTML dirakit |
| **HTML (umum)** | `app/Views/partials/{header,navbar,sidebar,footer}.php` | Judul halaman, `cabangid`, `username`, `REMOTE_ADDR`, `userid` |
| **Atribut** | `app/Views/home.php`, `login.php`, `supirpercabang/view.php`, `auth/email_reset_password.php` | `color`/`icon`/`link` tombol, `$info`, meta tag, `src` dokumen supir, `$resetLink` |
| **HTML (teks)** | 17 view grid (`omset*`, `overtop*`, `piutangemkl`, `trucking/*`) | `Last Update : <?= esc($last_update) ?>` |

**Verifikasi**

`tests/unit/ViewEscapingTest.php` (15 tes, 88 asersi) menjaga tiga hal:

1. `json_encode()` dengan bendera `JSON_HEX_*` tidak menyisakan `'`, `"`, `<`, `>`, atau
   `&` di dalam literal — tidak ada payload yang bisa menutup literal maupun blok skrip —
   sementara nilainya tetap utuh saat dibaca kembali sebagai JS.
2. `esc()` konteks `html` dan `attr` menetralkan payload yang sama.
3. Penjaga regresi atas berkas view yang sebenarnya: begitu ada yang kembali menjepit
   nilai dengan kutip manual di titik-titik JS yang rawan, tesnya gagal.

Tes ini sekaligus menegaskan mengapa `esc($v, 'html')` bukan pengganti yang sah di
konteks JS: hasilnya berupa entitas yang tidak pernah didekode di dalam blok skrip,
sehingga pesan yang sampai ke pengguna justru rusak.

Suite penuh: **259 tes / 1124 asersi, seluruhnya lulus.**

**Sisa yang Belum Ditutup**

- `showDialog()` pada `public/libraries/tas-lib/js/mains.js:2535` merangkai respons
  server ke dalam string HTML lalu memasangnya lewat `.html()`. Ini **DOM XSS sink**,
  bukan masalah template PHP, dan fungsinya dipakai lintas modul sehingga perbaikannya
  perlu ditangani sebagai item tersendiri. Versi lokal fungsi yang sama di `login.php`
  sudah diamankan.
- Perbaikan ini menutup jalur eksekusi, **bukan** kebutuhan CSP. Selama M-01 masih
  memakai `unsafe-inline`, tidak ada lapisan pertahanan kedua bila kelak ada titik
  keluar baru yang terlewat.

---

### LOW / INFO

---

#### L-01 — `tokenRandomize` Nonaktif (Risiko BREACH)
**Lokasi:** `app/Config/Security.php` — `public bool $tokenRandomize = false;`

Token CSRF bernilai tetap sepanjang sesi sehingga rentan terhadap serangan kompresi BREACH bila respons di-gzip.
```php
public bool $tokenRandomize = true;
```

---

#### L-02 — Pemanggilan Method Dinamis dari Input GET
**Lokasi:** `app/Controllers/Grafikbiayakantorbandinglaba.php:73`
```php
$cabang = $this->request->getGet('cabang') ?? 'JKT';
$method = 'get_where' . $cabang;
if (method_exists($this->mgrafik, $method)) {
    $dataSemuaRaw = $this->mgrafik->$method('')->getResultArray();
}
```
Dampak saat ini terbatas karena prefiks `get_where` tetap dan dijaga `method_exists()`, tetapi pola ini membuat permukaan serangan bergantung pada penamaan method di model. Ganti dengan pemetaan eksplisit:
```php
$map = ['JKT' => 'get_whereJKT', 'MDN' => 'get_whereMDN', 'SBY' => 'get_whereSBY',
        'MKS' => 'get_whereMKS', 'BTG' => 'get_whereBTG', 'SMG' => 'get_whereSMG'];
$method = $map[$cabang] ?? $map['JKT'];
```

---

#### L-03 — Pembuatan Direktori dengan Permission 0777
**Lokasi:** `app/Controllers/GridPreference.php:29`
```php
mkdir($dir, 0777, true);
```
World-writable. Gunakan `0755` dan tangani kegagalan:
```php
if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
    throw new \RuntimeException("Gagal membuat direktori: {$dir}");
}
```

---

#### L-04 — Autentikasi Ditangani Manual di Dalam Controller
**Lokasi:** `app/Controllers/GridPreference.php:13-21`
```php
header('HTTP/1.1 401 Unauthorized');
echo json_encode(['error' => 'Unauthorized']);
exit;
```
Memanggil `header()`/`echo`/`exit` secara langsung melewati objek Response CI4 — akibatnya filter `after` (termasuk `secureheaders`) tidak pernah berjalan pada respons ini. Gunakan mekanisme framework:
```php
return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
```
Hal serupa terjadi pada `MyAuth::auth()` yang memakai `exit("You don't have access to $class/$method")` — selain melewati Response, pesannya juga membocorkan nama controller/method internal.

---

#### L-05 — `.gitignore` Belum Menutup File Scratch dan Debug

**Status:** ✅ SELESAI (verifikasi 31 Agustus 2026) — `.gitignore` kini memuat pola `/public/test_*.php`, `test_*.php`, `/scratch/*`, `/scratch_*.{py,ps1,sql,php}`, `cekmenu.php`, dan `simulate.php`. Dua berkas yang dulu ter-commit (`cekmenu.php`, `public/test_param.php`) sudah tidak lagi tracked di Git; keduanya masih ada di working tree mesin developer.
**Lokasi:** `.gitignore`

Hanya `.env` yang dilindungi. Puluhan file `scratch_*.py`, `scratch_*.php`, `public/test_*.php`, dan direktori `scratch/` berada di working tree; dua di antaranya (`cekmenu.php`, `public/test_param.php`) sudah ter-commit.
```gitignore
# Debug, scratch & temporary artifacts
scratch/
scratch_*
public/test_*.php
temp_legacy.js
refactor_footers.js
simulate.php
cekmenu.php
update_omset.php
```

---

#### L-06 — Kredensial SMTP di `.env` Perlu Dirotasi
**Lokasi:** `.env` — `email.SMTPPass`

`.env` sudah benar ter-cover `.gitignore` (poin ini **sesuai standar**). Namun karena file `.env` berada di working tree bersama file scratch yang berisi kredensial `sa`, dan mengingat temuan C-05 (skrip debug publik), kunci SMTP Brevo sebaiknya dirotasi sebagai tindakan pencegahan. Batasi pula kunci tersebut hanya untuk pengiriman transaksional.

---

## 4. Audit OWASP Top 10 (Poin 18)

| Kode | Kategori | Status | Temuan Terkait |
|---|---|---|---|
| **A01** | Broken Access Control | **Sebagian** | ~~C-02 (ACL tidak dieksekusi)~~ ✅ 20 Agu 2026 — `AclFilter` global, ~~C-04 (bypass regex)~~ ✅ 21 Agu 2026. *Sisa:* M-04 (endpoint pengubah state menerima GET) |
| **A02** | Cryptographic Failures | **Sebagian** | H-02 (kredensial hardcoded — 🟡 sebagian ditutup 29 Agu 2026; sisa `my_helper.php` + rotasi), ~~H-05 (hash di session)~~ ✅ 29 Agu 2026, H-07 (MD5 — 🟢 risiko diterima 31 Agu 2026, migrasi bertahap saat login), H-08 (tanpa TLS), M-03. *Positif:* bcrypt, SHA-256 + `hash_equals` untuk token |
| **A03** | Injection | **Sesuai** | *Positif:* M-09 (XSS) ✅ diperbaiki 31 Agu 2026 — escaping per konteks di seluruh view, termasuk reflected XSS `useracl?userpk=` dan stored XSS menu sidebar. C-01 (SQLi filter grid) ✅ dan H-03 (SQLi ORDER BY) ✅ diperbaiki 22 Agu 2026 — parser & sanitasi pengurutan terpusat dengan whitelist kolom |
| **A04** | Insecure Design | **Sebagian** | ~~H-04 (tanpa anti-automation)~~ ✅ 24 Agu 2026, ~~M-02 (enumerasi username)~~ ✅ 31 Agu 2026. *Sisa:* M-08 (lock screen sisi klien) |
| **A05** | Security Misconfiguration | **Sebagian** | ~~C-03 (CSRF mati)~~ ✅ 22 Agu 2026, ~~C-05 (skrip debug publik)~~ ✅ ditutup 24 Agu 2026, ~~M-05 (host header injection)~~ ✅ 31 Agu 2026. *Sisa:* H-08 (tanpa TLS), M-01 sebagian — header lengkap 31 Agu 2026, `unsafe-inline`/`unsafe-eval` masih ada. *Positif:* auto-routing mati, error handling produksi benar |
| **A06** | Vulnerable & Outdated Components | **Lulus** | CI 4.7.4, PHP ^8.2, webauthn 2.2.0, laminas-escaper 2.18.0 — mutakhir, tanpa advisory aktif |
| **A07** | Identification & Authentication Failures | **Sebagian** | ~~H-01 (session fixation)~~ ✅ 22 Agu 2026, ~~H-04 (brute force)~~ ✅ 24 Agu 2026, ~~H-05 (hash di session)~~ ✅ 29 Agu 2026. ~~M-02 (enumerasi username)~~ ✅ 31 Agu 2026. *Sisa:* H-06 (password lemah); H-07 (MD5 legacy) 🟢 risiko diterima 31 Agu 2026 — migrasi bertahap, tidak ada jalur yang menulis MD5 baru. *Positif:* WebAuthn/MFA tersedia |
| **A08** | Software & Data Integrity Failures | **Perhatian** | Aset dimuat dari CDN eksternal (cdnjs, highcharts) tanpa atribut Subresource Integrity |
| **A09** | Security Logging & Monitoring Failures | **Sebagian** | ~~M-07 (login gagal & perubahan data tidak dicatat)~~ ✅ diperbaiki 29 Agu 2026 — peristiwa login gagal/diblokir, logout, dan CRUD (beserta nilai lama/baru) kini tercatat. *Sisa:* belum ada halaman pembacaan audit trail, alerting, maupun kebijakan retensi |
| **A10** | SSRF | **Lulus** | Tidak ditemukan HTTP request keluar yang URL-nya dikendalikan user; `CURLRequest` tidak dipakai |

---

## 5. CSP Readiness (Poin 17)

**Kondisi saat ini:** CSP aktif (`App::$CSPEnabled = true`) namun dilumpuhkan oleh `unsafe-inline` + `unsafe-eval`, dan `autoNonce = false`.

**Hambatan yang teridentifikasi:**

| Hambatan | Volume | Solusi |
|---|---|---|
| Inline `<script>` di views | Tersebar di sebagian besar view grid & grafik | Pindahkan ke file `.js` eksternal; kirim data lewat `data-*` attribute atau `<script type="application/json">` |
| Inline `onclick=` pada kolom jqGrid | Banyak (dibangun via formatter) | Ganti dengan event delegation: `$('#grid').on('click', '.btn-edit', handler)` |
| Inline `style=` | Banyak | Pindahkan ke kelas CSS |
| `eval()` — butuh `unsafe-eval` | jqGrid `footerData`, legacy formatter | Refactor formatter agar memakai fungsi, bukan string yang di-`eval` |
| CDN: cdnjs, code.highcharts.com, static.cloudflareinsights.com | 3 origin | Host lokal di `public/libraries/`, atau tambahkan atribut SRI |
| Google Fonts (`fonts.googleapis.com`, `fonts.gstatic.com`) | 2 origin | Self-host font untuk menghilangkan dependensi eksternal |
| AdminLTE / Bootstrap / Select2 / jQuery | Sudah lokal di `public/libraries/` | **Tidak menjadi hambatan** |

**Target konfigurasi setelah refactor:**
```php
// app/Config/ContentSecurityPolicy.php
public bool $reportOnly = true;            // Tahap 1: pantau tanpa memblokir
public ?string $reportURI = '/csp-report';

public $defaultSrc   = 'none';
public $scriptSrc    = ['self'];           // unsafe-inline & unsafe-eval DIHAPUS
public $styleSrc     = ['self'];
public $imageSrc     = ['self', 'data:', 'https://taspusat-storage.s3.us-east-1.amazonaws.com'];
public $fontSrc      = ['self', 'data:'];
public $connectSrc   = 'self';
public $formAction   = 'self';
public $frameAncestors = 'none';
public $objectSrc    = 'none';
public $baseURI      = 'self';
public bool $upgradeInsecureRequests = true;
public bool $autoNonce = true;             // CI4 menyuntik nonce otomatis
```
Untuk inline script yang benar-benar tidak bisa dihilangkan, pakai nonce:
```php
<script <?= csp_script_nonce() ?>>
    // ...
</script>
```

**Rencana bertahap yang disarankan:**
1. Set `reportOnly = true`, kumpulkan laporan pelanggaran selama 2 minggu.
2. Hilangkan inline JS/CSS berdasarkan laporan tersebut, dimulai dari view yang paling sering diakses.
3. Refactor pemakaian `eval()` di jqGrid agar `unsafe-eval` bisa dilepas.
4. Self-host seluruh aset CDN, atau tambahkan SRI.
5. Aktifkan `autoNonce = true` dan set `reportOnly = false`.

---

## 6. Tabel Ringkasan Temuan

| No | Temuan | Severity | Status | Solusi Ringkas |
|---|---|---|---|---|
| C-01 | SQL Injection pada filter grid (`operationAll`) | Critical | ✅ Diperbaiki (22 Agu 2026) — parser filter via `GridFilter` + sisi model (parameter `cabang`) | Whitelist nama kolom & nama tabel + escape/bind untuk nilai |
| C-02 | ACL tidak pernah dieksekusi (`MyAuth::auth()` tak terpanggil) | Critical | ✅ Diperbaiki (20 Agu 2026) | `AclFilter` global + `useracl` dikeluarkan dari daftar except |
| C-03 | CSRF dinonaktifkan global | Critical | ✅ Diperbaiki (22 Agu 2026) | Filter `csrf` aktif + token global via `$.ajaxSetup`; `regenerate=false`, `expires=0` |
| C-04 | Bypass autentikasi via regex di `AuthFilter` | Critical | ✅ Diperbaiki (21 Agu 2026) | Regex dihapus; route `reset/(:segment)` eksplisit + `except` |
| C-05 | Skrip debug `public/test_*.php` dapat diakses publik | Critical | ✅ Ditutup (24 Agu 2026) | Artefak lokal, tidak ikut rilis; `.gitignore` sudah menutup dan `test_param.php` kini tidak lagi tracked (31 Agu 2026) |
| H-01 | Session fixation (tanpa `regenerate()`) | High | ✅ Diperbaiki (22 Agu 2026) | `session()->regenerate(true)` di 3 jalur login (termasuk WebAuthn) |
| H-02 | Kredensial `sa` hardcoded di file ter-commit | High | 🟡 Sebagian (29 Agu 2026) — `cekmenu.php` di-`git rm --cached` + `.gitignore`; scratch sudah bersih. **Sisa:** `my_helper.php:18` masih literal (dead code), riwayat Git, dan rotasi password belum dilakukan | Rotasi password; user DB hak minimum; bersihkan repo |
| H-03 | SQL Injection pada `ORDER BY` (`sidx`/`sord`) | High | ✅ Diperbaiki (22 Agu 2026) | `GridSort` di 26 model: validasi identifier + whitelist + cast integer |
| H-04 | Tanpa rate limiting / anti brute force | High | ✅ Diperbaiki (24 Agu 2026) | `LoginThrottle` pada login, unlock, forgot-password; jatah hanya berkurang saat gagal |
| H-05 | Hash password disimpan di session | High | ✅ Diperbaiki (29 Agu 2026) | Dihapus dari 4 titik pembangun sesi (termasuk SSO & WebAuthn); `Profil::editpassword` & `MloginModel::cek` baca via `userpk` |
| H-06 | Password policy lemah; tanpa `password_needs_rehash()` | High | Belum sesuai | Min 12 karakter + kompleksitas; rehash saat login |
| H-07 | Hash MD5 legacy masih diterima | High | 🟢 Risiko diterima (31 Agu 2026) — sengaja dibuka sebagai jalur migrasi; upgrade ke bcrypt berjalan otomatis saat user login dan tidak ada jalur yang menulis MD5 baru. **Sisa:** akun dorman perlu reset paksa saat batas waktu | Pantau `masih_md5` mingguan; migrasi paksa + tutup jalur MD5 pada batas waktu (usulan 30 Nov 2026) |
| H-08 | Cookie tanpa `Secure`; HTTPS tidak dipaksa | High | Belum sesuai | `$secure=true`, `forceGlobalSecureRequests=true` |
| M-01 | CSP lemah; COOP/COEP/CORP belum ada | Medium | ⚠️ Sebagian (31 Agu 2026) | COOP+CORP+`base-uri`/`frame-ancestors`/`object-src` dipasang; Permissions-Policy 3→11 fitur. COEP ditunda (aset CDN), `unsafe-inline`/`unsafe-eval` ditunda (47 skrip inline) |
| M-02 | Username enumeration di lupa password | Medium | ✅ Diperbaiki (31 Agu 2026) | Satu respons untuk semua kasus + jatah rate limit ikut diseragamkan. **Sisa:** selisih waktu respons |
| M-03 | Token reset hanya 44 bit entropi | Medium | Sebagian | Pakai `bin2hex(random_bytes(32))` penuh |
| M-04 | Endpoint pengubah state menerima GET | Medium | Belum sesuai | Pisahkan route GET (baca) dan POST (aksi) |
| M-05 | Host header injection pada `baseURL` | Medium | ✅ Diperbaiki (31 Agu 2026) | Daftar putih host (`.env` + bawaan); host asing tidak menyentuh `baseURL`, jatuh ke `app.baseURL` statis |
| M-06 | Query tanpa batas untuk hitung baris (DoS) | Medium | Belum sesuai | Ganti ke `SELECT COUNT(*)`; batasi ukuran halaman |
| M-07 | Audit trail tidak memadai | Medium | ✅ Diperbaiki (29 Agu 2026) | `saveLog()` berbasis peristiwa + kolom `event`/`context`; login gagal, logout, dan CRUD (nilai lama/baru) dicatat |
| M-08 | Lock screen hanya sisi klien | Medium | Sebagian | Tegakkan idle timeout & status terkunci di server |
| M-09 | XSS: flashdata/pesan validasi tanpa escaping | Medium | ✅ Diperbaiki (31 Agu 2026) — cakupan meluas: ditemukan **reflected XSS** (`useracl?userpk=`) dan **stored XSS** lewat menu sidebar | `json_encode()` untuk konteks JS, `esc()`/`esc(, 'attr')` untuk HTML & atribut; escaping di titik perakitan untuk HTML yang dirakit di PHP |
| L-01 | `tokenRandomize = false` (BREACH) | Low | Belum sesuai | Set `true` |
| L-02 | Pemanggilan method dinamis dari input GET | Low | Sebagian | Ganti dengan array pemetaan eksplisit |
| L-03 | `mkdir(0777)` | Low | Belum sesuai | Gunakan `0755` |
| L-04 | `header()`/`exit()` melewati Response CI4 | Low | Belum sesuai | Pakai `$this->response->setStatusCode()` |
| L-05 | `.gitignore` belum menutup file scratch/debug | Low | ✅ Selesai (31 Agu 2026) | Pola scratch & test sudah ditambahkan; `cekmenu.php` dan `test_param.php` tidak lagi tracked |
| L-06 | Kunci SMTP perlu dirotasi (pencegahan) | Info | Sebagian | Rotasi kunci Brevo; batasi scope |

---

## 7. Security Score

# **32 / 100**

### Rincian Penilaian

| Domain | Bobot | Skor | Alasan |
|---|---|---|---|
| Access Control | 20 | 2 | ACL lengkap tersedia di DB namun tidak pernah dieksekusi; ditambah bypass regex di filter autentikasi. Setiap user terautentikasi memiliki akses penuh. |
| Injection Defense | 20 | 4 | SQLi dapat dieksploitasi di seluruh modul laporan — pada nilai, nama kolom, dan `ORDER BY`. Nilai parsial diberikan karena model generasi baru sudah memakai Query Builder. |
| Authentication | 15 | 6 | bcrypt, WebAuthn, dan alur reset password yang solid — tetapi session fixation, tanpa rate limiting, dan password minimal 5 karakter membatalkan sebagian besar keunggulan itu. |
| Session & CSRF | 10 | 1 | CSRF sepenuhnya tidak aktif meski konfigurasi dan token sudah tersedia; tanpa `regenerate()`; cookie tanpa `Secure`. |
| Configuration & Headers | 10 | 5 | 5 dari 9 header terpasang benar dan error handling produksi tepat; namun skrip debug publik dan HTTPS tidak dipaksakan menurunkan nilai. |
| Data Protection | 10 | 3 | Kredensial `sa` hardcoded di file ter-commit; hash password disimpan di session. `.env` sudah benar di-gitignore. |
| Logging & Monitoring | 5 | 1 | Hanya login berhasil yang tercatat; tanpa jejak login gagal maupun perubahan data. |
| Dependency Management | 5 | 5 | Seluruh dependensi mutakhir, tidak ada paket rentan atau deprecated. |
| Input Validation | 5 | 5 | CI4 Validation dipakai pada endpoint autentikasi; custom rule periode bulan yang reusable; whitelist nama tabel cabang. |
| **Total** | **100** | **32** | |

### Interpretasi

Skor 32 mencerminkan aplikasi dengan **fondasi teknis yang layak namun kontrol keamanan inti yang tidak aktif**. Ini penting dibedakan: sebagian besar temuan bukan karena kode ditulis buruk, melainkan karena mekanisme keamanan yang **sudah tersedia tidak dihubungkan**:

- Filter CSRF ada dan terkonfigurasi benar — hanya dikomentari satu baris.
- Sistem ACL lengkap dengan tabel database dan logika validasi — hanya tidak pernah dipanggil.
- Header keamanan terpasang, error handling produksi tepat, dependensi mutakhir.

Konsekuensinya, **perbaikan berdampak tertinggi relatif murah**. Mengaktifkan CSRF (C-03) dan memasang `AclFilter` (C-02) adalah pekerjaan beberapa jam yang menaikkan skor secara signifikan. Yang menuntut usaha nyata hanyalah C-01 — refactor `operationAll()` beserta penyesuaian di seluruh model grid.

### Urutan Prioritas Perbaikan

**Tahap 1 — Segera (hari ini)**
1. ~~Hapus `public/test_*.php` dari server produksi (C-05)~~ — ✅ ditutup 24 Agustus 2026: artefak lokal, tidak ikut rilis. Sisa yang dicatat saat itu (`test_param.php` masih tracked) sudah bersih per 31 Agustus 2026; berkas tidak lagi ada di Git.
2. ~~Aktifkan filter `csrf` di `Filters.php` (C-03).~~ — ✅ selesai 22 Agustus 2026. Ternyata bukan satu baris: `regenerate` harus dimatikan dan `expires` dijadikan `0`, kalau tidak seluruh AJAX kena 403.
3. ~~Hapus pengecualian regex di `AuthFilter` (C-04)~~ — ✅ selesai 21 Agustus 2026.
4. Rotasi password `sa` dan kunci SMTP Brevo (H-02, L-06) — **masih terbuka.** Penyebaran lewat berkas ter-commit sudah dihentikan 29 Agustus 2026 (`cekmenu.php` di-untrack), tetapi password lama tetap ada di riwayat Git dan di `app/Helpers/my_helper.php:18`, jadi rotasi belum bisa dicoret.

**Tahap 2 — Minggu ini**
5. ~~Pasang `AclFilter` (C-02).~~ — ✅ selesai 20 Agustus 2026. Catatan: `exceptAuth['method']` sengaja **tidak** dikosongkan (daftar itu hanya berlaku setelah user terbukti punya hak pada class terkait); yang dicabut adalah `useracl` dari `exceptAuth['class']` — di situlah jalur eskalasi haknya.
6. ~~Tambahkan `session()->regenerate(true)` saat login (H-01).~~ ✅ selesai 22 Agu 2026.
7. ~~Terapkan throttler pada login, unlock, forgot-password (H-04).~~ — ✅ selesai 24 Agustus 2026.
8. Aktifkan HTTPS + cookie `Secure` (H-08).

**Tahap 3 — Sprint berikutnya**
9. ~~Refactor `operationAll()` ke whitelist + escape, dan tutup parameter `cabang` di model grid~~ — ✅ selesai 22 Agustus 2026 (C-01). ~~Sisa: whitelist kolom sortable untuk `ORDER BY` (H-03).~~ — ✅ selesai 22 Agustus 2026 (H-03).
10. ~~Hapus hash password dari session (H-05)~~ — ✅ selesai 29 Agustus 2026. Sisa: perketat kebijakan password (H-06).
11. ~~Lengkapi audit trail (M-07)~~ — ✅ selesai 29 Agustus 2026. Sisa: ganti `count()` ke `SELECT COUNT(*)` (M-06).
12. ~~Lengkapi header keamanan (COOP/CORP/`base-uri`/`frame-ancestors`/`object-src`, Permissions-Policy)~~ — ✅ selesai 31 Agustus 2026 (M-01). Sisa pada M-01: host aset CDN secara lokal (prasyarat COEP), lalu pindahkan 47 blok skrip inline agar `unsafe-inline`/`unsafe-eval` bisa dilepas.
13. Tutup jalur hash MD5 (H-07) — **sengaja dijadwalkan di akhir.** Migrasi berjalan bertahap mengikuti login user sejak 31 Agustus 2026; yang tersisa saat batas waktu (usulan 30 November 2026) hanyalah akun dorman, dan itu dibereskan sekali jalan lewat migrasi paksa. Sampai saat itu, pantau `masih_md5` mingguan dan reset manual akun ber-hak tinggi yang masih MD5.

---

*Laporan ini dibuat melalui static source review. Tidak ada source code aplikasi yang diubah. Verifikasi lanjutan lewat dynamic testing (DAST) dan penetration testing disarankan setelah perbaikan Tahap 1 dan 2 diterapkan.*
