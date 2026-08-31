<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\GridFilter;
use App\Models\MlogModel;
use App\Models\MmenutopModel;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;
    protected $helpers = ['url', 'form', 'my_helper', 'global_helper', 'asset_helper'];
    protected string $layout = 'home';
    protected $mmenutopModel;

    /** Dibuat saat pertama kali auditLog() dipakai; lihat auditLog(). */
    protected ?MlogModel $auditModel = null;

    /**
     * Whitelist kolom yang boleh dipakai pada filter grid jqGrid.
     * Wajib diisi oleh controller yang memanggil operationAll().
     *
     * @see \App\Libraries\GridFilter::build() untuk bentuk yang diterima.
     */
    protected array $filterFields = [];

    /**
     * Grup database yang mengeksekusi query grid. Dipakai untuk meng-escape
     * nilai filter dengan aturan driver yang benar. null = grup default.
     */
    protected ?string $filterDbGroup = null;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
        $this->mmenutopModel = new MmenutopModel();
    }

    protected function render(string $page, array $data = [])
    {
        $data['sqlmenu'] = $this->mmenutopModel->get_data();

        $output  = view('partials/header', $data);
        $output .= view('partials/navbar', $data);
        $output .= view('partials/sidebar', $data);
        $output .= view($this->getLayout(), ['template' => $page, 'data' => $data]);
        $output .= view('partials/footer', $data);

        return $output;
    }
    protected function setLayout(string $layout)
    {
        $this->layout = $layout;
        return $this;
    }

    protected function getLayout(): string
    {
        return 'partials/layouts/' . $this->layout;
    }

    /**
     * M-07: satu pintu bagi controller untuk mencatat perubahan data.
     *
     * Sebelumnya `log_activity` hanya diisi dari halaman login, sehingga tidak
     * ada satu pun baris yang bisa menjawab siapa mengubah apa. Diletakkan di
     * BaseController supaya setiap controller CRUD tidak perlu menyalin
     * instansiasi model dan penanganan galatnya sendiri-sendiri.
     *
     * Kegagalan menulis log tidak pernah dilempar ke atas — lihat
     * MlogModel::saveLog().
     *
     * @param string               $event   Konstanta MlogModel::DATA_*.
     * @param string|null          $message Deskripsi bisnis, mis. "Ubah data user".
     * @param array<string, mixed> $context Pengenal baris dan nilai lama/baru.
     */
    protected function auditLog(string $event, ?string $message = null, array $context = []): void
    {
        $this->auditModel ??= new MlogModel();

        $this->auditModel->saveLog($event, $message, $context);
    }

    /**
     * Menerjemahkan JSON filter jqGrid menjadi kondisi WHERE yang aman.
     *
     * Nama kolom disaring lewat whitelist dan nilainya di-escape oleh driver
     * database yang bersangkutan, jadi tidak ada lagi input client yang masuk
     * sebagai sintaks SQL.
     *
     * @param mixed       $filters   Isi POST `filters`.
     * @param array|null  $fieldMap  Whitelist kolom; null memakai $filterFields.
     * @param string|null $dbGroup   Grup database; null memakai $filterDbGroup.
     *
     * @return string Kondisi tanpa kurung dan tanpa `AND` di depan, atau string
     *                kosong bila tidak ada rule yang valid.
     */
    protected function operationAll($filters, ?array $fieldMap = null, ?string $dbGroup = null): string
    {
        $gridFilter = new GridFilter($dbGroup ?? $this->filterDbGroup);

        return $gridFilter->build($filters, $fieldMap ?? $this->filterFields);
    }

    /**
     * Meng-escape satu nilai menjadi literal SQL yang aman, memakai driver dari
     * grup database yang akan menjalankan query-nya.
     *
     * Dipakai untuk filter tambahan di luar grid (dropdown cabang, marketing,
     * periode) yang sebelumnya dikonkatenasi mentah atau lewat addslashes().
     * addslashes() TIDAK aman untuk SQL Server: backslash bukan karakter escape
     * di sana, sehingga kutip tunggal tetap lolos.
     *
     * @return string Literal lengkap dengan tanda kutipnya.
     */
    protected function escapeFilterValue($value, ?string $dbGroup = null): string
    {
        $db = \Config\Database::connect($dbGroup ?? $this->filterDbGroup);

        return $db->escape((string) $value);
    }

    /**
     * Logic Umum (Reusable) untuk validasi Bulan Dari dan Bulan Sampai
     * Mempertahankan fitur UX: Error hanya muncul di input yang terakhir diubah user.
     */
    protected function getPeriodeBulanRules($last_changed = 'tgl_dari')
    {
        return [
            'tgl_dari' => [
                'rules' => ($last_changed !== 'tgl_sampai') ? 'permit_empty|month_less_than_equal[tgl_sampai]' : 'permit_empty',
                'errors' => [
                    'month_less_than_equal' => 'Bulan dari tidak boleh lebih besar dari Bulan sampai!'
                ]
            ],
            'tgl_sampai' => [
                'rules' => ($last_changed === 'tgl_sampai') ? 'permit_empty|month_greater_than_equal[tgl_dari]' : 'permit_empty',
                'errors' => [
                    'month_greater_than_equal' => 'Bulan sampai tidak boleh lebih kecil dari Bulan dari!'
                ]
            ]
        ];
    }
}
