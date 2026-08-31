<?php

namespace App\Controllers;

use App\Libraries\GridSort;
use App\Models\MlogModel;
use App\Models\MuserModel;
use App\Models\MuserrolesModel;
use CodeIgniter\Controller;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class User extends BaseController
{
    /**
     * Kolom `karyawan` (database hrsso) yang boleh dipakai memfilter grid lookup.
     * Bentuknya sama dengan $filterFields: nama kolom di jqGrid => ekspresi SQL.
     */
    private const KARYAWAN_FILTER_FIELDS = [
        'id'           => 'k.id',
        'kodekaryawan' => 'k.kodekaryawan',
        'namakaryawan' => 'k.namakaryawan',
        'cabang'       => 'c.nama',
        'jabatan'      => 'j.nama',
    ];

    /**
     * Kolom yang boleh dipakai mengurutkan grid lookup karyawan.
     *
     * Sengaja peta tertutup, bukan validasi bentuk identifier: `sidx` masuk ke
     * ORDER BY sebagai teks dan nama kolom tidak bisa jadi bind parameter, jadi
     * hanya nilai yang ditulis di sini yang boleh sampai ke SQL.
     */
    private const KARYAWAN_SORTABLE = [
        'id'           => 'k.id',
        'kodekaryawan' => 'k.kodekaryawan',
        'namakaryawan' => 'k.namakaryawan',
        'cabang'       => 'c.nama',
        'jabatan'      => 'j.nama',
    ];

    /**
     * Whitelist kolom filter grid jqGrid (join tbluser + tblroles r).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'userpk' => 'tbluser.userpk',
        'userid' => 'tbluser.userid',
        'username' => 'tbluser.username',
        'dashboard' => 'tbluser.dashboard',
        'email' => 'tbluser.email',
        'nowhatsapp' => 'tbluser.nowhatsapp',
        'aktif' => 'tbluser.aktif',
        'modifiedby' => 'tbluser.modifiedby',
        'rolename' => 'r.rolename',
        'modifiedon' => "FORMAT(tbluser.modifiedon, 'dd-MM-yyyy HH:mm:ss')",
        'modifiedonview' => "FORMAT(tbluser.modifiedon, 'dd-MM-yyyy HH:mm:ss')",
    ];

    protected $muserModel;

    public function __construct()
    {
        $this->muserModel = new MuserModel();
    }

    public function index()
    {
        $data = [
            'title' => 'User'
        ];
        return $this->render('user/index', $data);
    }

    public function getRoles()
    {
        $db = \Config\Database::connect();
        $roles = $db->table('tblroles')->orderBy('rolename', 'asc')->get()->getResult();
        return $this->response->setJSON($roles);
    }

    /**
     * Grid lookup karyawan — sumbernya master karyawan di database HR (hrsso).
     *
     * `karyawan.id` di sana adalah identitas yang sama dengan klaim `karyawanId`
     * pada tiket SSO, jadi nilai yang dipilih di sini persis yang dicari
     * App\Controllers\SsoAuth::resolveUser() saat pengguna masuk lewat SSO.
     * Karena itu id-nya diambil dari HR, tidak pernah diketik manual, dan tidak
     * divalidasi terhadap `tblkaryawan` lokal — salinan lokal itu basi
     * (berhenti di id 361) sedangkan id karyawan yang sah bisa jauh di atasnya.
     */
    public function lookupKaryawan()
    {
        $page = max(1, (int) ($this->request->getPost('page') ?: 1));

        // GridSort::limit() hanya melakukan cast, jadi "abc" jadi 0 — dan 0
        // memicu pembagian nol di bawah serta ditolak SQL Server pada FETCH NEXT.
        // Batas atasnya menjaga lookup tetap satu halaman wajar meski client
        // meminta seluruh 354 baris sekaligus.
        $limit = min(200, max(1, GridSort::limit($this->request->getPost('rows') ?: 10)));

        $sidx = (string) ($this->request->getPost('sidx') ?: 'namakaryawan');
        $sort = self::KARYAWAN_SORTABLE[$sidx] ?? 'k.namakaryawan';
        $sord = GridSort::direction($this->request->getPost('sord'), 'ASC');

        $response          = new \stdClass();
        $response->page    = $page;
        $response->total   = 0;
        $response->records = 0;
        $response->rows    = [];

        $db = $this->hrssoDb();

        if ($db === null) {
            return $this->response->setJSON($response);
        }

        try {
            $where = ' WHERE k.statusaktif = ' . $this->karyawanAktifId($db);

            $operation = $this->request->getPost('_search') === 'true'
                ? $this->operationAll($this->request->getPost('filters'), self::KARYAWAN_FILTER_FIELDS, 'hrsso')
                : '';

            if ($operation !== '') {
                $where .= ' AND (' . $operation . ')';
            }

            $from = ' FROM karyawan k
                      LEFT JOIN cabang c ON c.id = k.cabang_id
                      LEFT JOIN jabatan j ON j.id = k.jabatan_id ';

            $count = (int) $db->query('SELECT COUNT(*) AS jml ' . $from . $where)->getRow()->jml;

            $totalPages = $count > 0 ? (int) ceil($count / $limit) : 0;

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $start = GridSort::offset($limit * $page - $limit);

            $rows = $db->query(
                "SELECT k.id, k.kodekaryawan, k.namakaryawan,
                        ISNULL(c.nama, '') AS cabang, ISNULL(j.nama, '') AS jabatan"
                . $from . $where . "
                 ORDER BY {$sort} {$sord}
                 OFFSET {$start} ROWS FETCH NEXT {$limit} ROWS ONLY"
            )->getResult();

            $response->page    = $page;
            $response->total   = $totalPages;
            $response->records = $count;

            foreach ($rows as $i => $row) {
                $response->rows[$i]['id']   = $row->id;
                $response->rows[$i]['cell'] = [
                    $row->id,
                    $row->kodekaryawan,
                    $row->namakaryawan,
                    $row->cabang,
                    $row->jabatan,
                ];
            }
        } catch (Throwable $e) {
            // Database HR tidak terjangkau atau salah konfigurasi. Grid dibiarkan
            // kosong — memilih karyawan jadi mustahil, tapi halaman User tetap
            // bisa dipakai untuk hal lain. Alasannya hanya masuk log.
            log_message('error', 'User::lookupKaryawan — gagal membaca database hrsso: ' . $e->getMessage());
        }

        return $this->response->setJSON($response);
    }

    /** Penanda cache bahwa database HR baru saja gagal dihubungi. */
    private const HRSSO_DOWN_KEY = 'hrsso_unreachable';

    /**
     * Berapa lama kegagalan koneksi HR diingat sebelum dicoba lagi (detik).
     */
    private const HRSSO_RETRY_SECONDS = 60;

    /**
     * Koneksi ke database HR, atau null kalau tidak tersedia.
     *
     * Dua penjaga, keduanya soal waktu tunggu. Driver SQLSRV milik CI4 tidak
     * meneruskan LoginTimeout, jadi koneksi ke host yang mati baru menyerah
     * setelah ~15 detik — dan halaman User memanggil ini setiap kali grid dimuat.
     *
     *  1. Kredensial kosong berarti fitur ini memang belum dikonfigurasi. Tidak
     *     ada gunanya membayar timeout untuk memastikannya.
     *  2. Kegagalan diingat sebentar, jadi HR yang sedang mati hanya membuat satu
     *     request membeku per rentang itu, bukan setiap request.
     */
    private function hrssoDb(): ?BaseConnection
    {
        $settings = config(\Config\Database::class)->hrsso ?? [];

        if (trim((string) ($settings['username'] ?? '')) === '') {
            // Dicatat, tidak didiamkan: dari luar, "belum dikonfigurasi" dan
            // "HR sedang mati" sama-sama terlihat sebagai lookup kosong. Tanpa
            // baris ini satu-satunya penyebab yang paling mungkin justru jadi
            // satu-satunya yang tak meninggalkan jejak di log.
            log_message('error', sprintf(
                'User: database.hrsso.username kosong — lookup karyawan dimatikan. '
                . 'Terbaca dari .env: hostname=%s database=%s port=%s.',
                (string) ($settings['hostname'] ?? '-'),
                (string) ($settings['database'] ?? '-'),
                (string) ($settings['port'] ?? '-')
            ));

            return null;
        }

        if (cache()->get(self::HRSSO_DOWN_KEY)) {
            return null;
        }

        try {
            $db = \Config\Database::connect('hrsso');
            // Koneksi SQLSRV dibuat malas; dipaksa sekarang supaya kegagalannya
            // tertangkap di sini, bukan meledak di tengah query pemanggil.
            $db->initialize();

            return $db;
        } catch (Throwable $e) {
            cache()->save(self::HRSSO_DOWN_KEY, 1, self::HRSSO_RETRY_SECONDS);
            log_message('error', 'User: database hrsso tidak terjangkau — ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Id parameter yang menandai karyawan AKTIF di database yang bersangkutan.
     *
     * Tidak di-hardcode (nilainya kebetulan 131 di hrsso) karena setiap database
     * punya tabel `parameter` sendiri dan id-nya berbeda antar database — aturan
     * yang sama dipakai auth-sso-api lewat resolveStatusAktifId(). Kalau barisnya
     * tidak ketemu, lookup sengaja gagal alih-alih menampilkan seluruh karyawan:
     * memetakan user ke karyawan yang sudah resign lebih buruk daripada lookup
     * yang kosong.
     */
    private function karyawanAktifId(BaseConnection $db): int
    {
        $row = $db->table('parameter')
            ->select('id')
            ->where('grp', 'STATUS AKTIF')
            ->where('text', 'AKTIF')
            ->get()
            ->getRow();

        if ($row === null) {
            throw new RuntimeException('Parameter grp="STATUS AKTIF" text="AKTIF" tidak ada di database hrsso.');
        }

        return (int) $row->id;
    }

    /**
     * Nama karyawan untuk sekumpulan karyawanid, dibaca dari database HR.
     *
     * tbluser dan karyawan hidup di dua instance SQL Server yang berbeda, jadi
     * keduanya tidak bisa di-JOIN; penggabungannya dilakukan di sini — pola yang
     * sama dipakai grid() untuk roles. Satu query untuk seluruh halaman grid,
     * bukan satu query per baris.
     *
     * Gagal-terbuka: database HR yang sedang mati tidak boleh membuat halaman
     * User ikut mati. Kolom karyawannya cuma jadi kosong.
     *
     * @param list<int> $ids
     *
     * @return array<int, string> karyawanid => nama
     */
    private function karyawanNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => (int) $id > 0)));

        if ($ids === []) {
            return [];
        }

        $db = $this->hrssoDb();

        if ($db === null) {
            return [];
        }

        try {
            $rows = $db->table('karyawan')
                ->select('id, namakaryawan')
                ->whereIn('id', $ids)
                ->get()
                ->getResult();

            $map = [];

            foreach ($rows as $row) {
                $map[(int) $row->id] = (string) $row->namakaryawan;
            }

            return $map;
        } catch (Throwable $e) {
            log_message('error', 'User::karyawanNames — gagal membaca database hrsso: ' . $e->getMessage());

            return [];
        }
    }

    public function grid()
    {
        $page = $this->request->getPost('page') ?? 1;
        $limit = $this->request->getPost('rows') ?? 10;
        $sidx = $this->request->getPost('sidx') ?? 1;
        $sord = $this->request->getPost('sord') ?? 'asc';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');

        $where1 = " WHERE 1=1 ";
        $where2 = "";

        $operation = $search == "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where2 = " AND (" . $operation . ")";
        }
        $where = $where1 . " " . $where2;

        $countQuery = $this->muserModel->count($where);
        $count = $countQuery->getNumRows();

        if ($count > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }

        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;
        if ($limit < 0) $limit = 0;

        $dataQuery = $this->muserModel->get($where, $sidx, $sord, $limit, $start);
        $data = $dataQuery->getResult();

        // Get userpks to fetch roles in bulk
        $userPks = array_map(function($row) { return $row->userpk; }, $data);

        $userRolesMap = [];
        if (!empty($userPks)) {
            $db = \Config\Database::connect();
            $rolesQuery = $db->table('tbluserroles ur')
                             ->select('ur.userpk, r.rolename')
                             ->join('tblroles r', 'ur.roleid = r.roleid', 'left')
                             ->whereIn('ur.userpk', $userPks)
                             ->get()
                             ->getResult();
            foreach ($rolesQuery as $r) {
                if (!isset($userRolesMap[$r->userpk])) {
                    $userRolesMap[$r->userpk] = [];
                }
                $userRolesMap[$r->userpk][] = $r->rolename;
            }
        }

        // Nama karyawan hidup di instance SQL Server lain (hrsso), jadi tidak
        // bisa ikut JOIN di query grid. Diambil sekali untuk seluruh halaman —
        // pola yang sama dengan $userRolesMap di atas.
        $karyawanMap = $this->karyawanNames(array_map(
            static fn ($row) => (int) ($row->karyawanid ?? 0),
            $data
        ));

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = [];

        $i = 0;
        foreach ($data as $row) {
            // CI3 specific hack
            $rolesForThisUser = isset($userRolesMap[$row->userpk]) ? implode(', ', $userRolesMap[$row->userpk]) : '';
            $row->rolename = $rolesForThisUser;

            if (strtoupper(session()->get('USERNAME') ?? '') != 'ADMIN') {
                if ($row->rolename == 'SUPERADMIN') {
                    continue;
                }
            }

            // We let frontend handle the buttons rendering (as requested in modern grid approaches)
            // But we pass the data needed
            $karyawanid = (int) ($row->karyawanid ?? 0);

            $response->rows[$i]['id'] = $row->userpk;
            $response->rows[$i]['cell'] = [
                $row->userpk, // placeholder for aksi in frontend
                $row->userid,
                $row->username,
                $karyawanMap[$karyawanid] ?? '',
                $row->dashboard,
                $row->email,
                $row->nowhatsapp,
                $row->rolename,
                $row->modifiedby,
                $row->modifiedonview
            ];
            $i++;
        }

        return $this->response->setJSON($response);
    }

    /**
     * Buang `user_roles` dari payload sebelum dibandingkan dengan baris tbluser.
     *
     * Roles tinggal di tbluserroles, bukan di tbluser, jadi memasukkannya ke
     * perbandingan hanya menghasilkan satu baris palsu "dari: kosong" di setiap
     * penyuntingan.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function tanpaRoles(array $data): array
    {
        unset($data['user_roles']);

        return $data;
    }

    public function crud()
    {
        $action = $this->request->getPost('oper');
        $id = $this->request->getPost('id');

        // 0 = belum dipetakan ke karyawan. Itu keadaan yang sah: akun sistem
        // seperti ADMIN atau ITMKS tidak punya padanan di master karyawan.
        $karyawanid = (int) ($this->request->getPost('karyawanid') ?: 0);

        $data = [
            'userid'     => $this->request->getPost('userid'),
            'username'   => $this->request->getPost('username'),
            'email'      => $this->request->getPost('email'),
            'nowhatsapp' => $this->request->getPost('nowhatsapp'),
            'password'   => $this->request->getPost('password'),
            'dashboard'  => $this->request->getPost('dashboard'),
            'karyawanid' => $karyawanid,
            'user_roles' => $this->request->getPost('user_roles')
        ];

        // Satu karyawan hanya boleh menempel pada satu user. SsoAuth::resolveUser()
        // menolak tiket yang cocok ke lebih dari satu baris tbluser, jadi dua user
        // dengan karyawanid sama membuat KEDUANYA tidak bisa login lewat SSO —
        // dengan pesan yang tidak menjelaskan sebabnya. Ditolak di sini selagi
        // penyebabnya masih terlihat. (tbluser tidak punya unique index selain
        // PK userpk, jadi pemeriksaan ini satu-satunya penjaga.)
        if ($karyawanid > 0 && in_array($action, ['add', 'edit'], true)) {
            $bentrok = \Config\Database::connect()
                ->table('tbluser')
                ->where('karyawanid', $karyawanid)
                ->where('userpk !=', (int) $id) // 0 saat add — tidak pernah cocok
                ->countAllResults();

            if ($bentrok > 0) {
                return $this->response->setJSON([
                    'status'  => 'gagal',
                    'message' => 'Karyawan ini sudah dipakai user lain. Satu karyawan hanya boleh dipetakan ke satu user.'
                ]);
            }
        }

        try {
            if ($action == 'add') {
                $status = $this->muserModel->saveUserData($data);
                // We cannot easily return ID because saveUserData doesn't return ID.
                // But wait, the grid will reload anyway.

                if ($status) {
                    // M-07: pembuatan akun adalah perubahan hak akses. Isi
                    // password tidak ikut tercatat — lihat MlogModel::REDAKSI.
                    $this->auditLog(MlogModel::DATA_CREATE, 'Tambah user', [
                        'tabel' => 'tbluser',
                        'data'  => $this->tanpaRoles($data),
                        'roles' => $data['user_roles'],
                    ]);
                }
            } elseif ($action == 'edit') {
                // Diambil sebelum disimpan; sesudahnya nilai lamanya sudah hilang.
                $sebelum = $this->muserModel->getByIdUser($id);

                $data['userpk'] = $id;
                if ($data['password'] == '') {
                    unset($data['password']);
                }
                $status = $this->muserModel->saveUserData($data);

                if ($status) {
                    $this->auditLog(MlogModel::DATA_UPDATE, 'Ubah data user', [
                        'tabel'     => 'tbluser',
                        'userpk'    => $id,
                        'perubahan' => MlogModel::changes($sebelum, $this->tanpaRoles($data)),
                        // Roles disimpan di tabel lain dan tidak ada di $sebelum,
                        // jadi dicatat sebagai keadaan akhir — bukan sebagai selisih
                        // yang seolah-olah berangkat dari kosong.
                        'roles'     => $data['user_roles'],
                    ]);
                }
            } elseif ($action == 'del') {
                $sebelum = $this->muserModel->getByIdUser($id);
                $status  = $this->muserModel->delete($id);

                if ($status) {
                    $this->auditLog(MlogModel::DATA_DELETE, 'Hapus user', [
                        'tabel'   => 'tbluser',
                        'userpk'  => $id,
                        'sebelum' => $sebelum,
                    ]);
                }
            }

            return $this->response->setJSON([
                'status' => $status ? 'sukses' : 'gagal',
                'message' => $status ? '' : json_encode($this->muserModel->errors()) . ' | ' . json_encode($this->muserModel->db->error())
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'gagal',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getById($id)
    {
        $user = $this->muserModel->getByIdUser($id);
        
        if (!empty($user)) {
            $muserroles = new MuserrolesModel();
            $roles = $muserroles->getByUserID($id);
            $user->user_roles = array_column($roles, 'roleid');

            // Namanya ikut dikirim supaya form bisa menampilkan siapa yang
            // terpetakan tanpa membuka lookup. Kosong berarti belum dipetakan —
            // atau karyawannya sudah tidak ada lagi di master HR.
            $karyawanid          = (int) ($user->karyawanid ?? 0);
            $user->namakaryawan  = $this->karyawanNames([$karyawanid])[$karyawanid] ?? '';
        }

        return $this->response->setJSON($user);
    }
}
