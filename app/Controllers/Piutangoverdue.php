<?php

namespace App\Controllers;

use App\Libraries\Export\ExcelExporter;
use App\Libraries\Export\NullExportNotifier;
use App\Models\MpiutangoverdueModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Laporan Piutang Overdue.
 * Konsep meniru Piutangemkl, tetapi sumber data dari stored procedure
 * (usp_get*overdue & usp_getlistshipperoverdue). Karena SP mengembalikan
 * seluruh baris sekaligus, filter toolbar, sorting, paging dan grand total
 * diproses di PHP.
 */
class Piutangoverdue extends BaseController
{
    /** Field grid yang boleh dipakai untuk filter toolbar & sorting. */
    private const FIELDS = [
        'FShipper', 'FMarketing', 'FTglInvoice', 'FTglTT', 'FNInvoice', 'FNominal',
        'FTop', 'FReal', 'FSelisih', 'FKeterangan', 'FCabang',
    ];
    private const NUMERIC = ['FNominal', 'FTop', 'FReal', 'FSelisih'];
    private const DATES   = ['FTglInvoice', 'FTglTT'];

    /** Kolom Excel = kolom grid, urutan & label sama. */
    private const EXCEL_COLUMNS = [
        ['key' => 'FCabang',     'label' => 'Cabang',           'width' => 14],
        ['key' => 'FShipper',    'label' => 'Nama Shipper',     'width' => 40],
        ['key' => 'FMarketing',  'label' => 'Marketing',        'width' => 28],
        ['key' => 'FTglInvoice', 'label' => 'Tgl Invoice',      'type' => 'date', 'width' => 14],
        ['key' => 'FTglTT',      'label' => 'Tgl Tanda Terima', 'type' => 'date', 'width' => 16],
        ['key' => 'FNInvoice',   'label' => 'No Invoice',       'width' => 42],
        ['key' => 'FNominal',    'label' => 'Nominal Invoice',  'type' => 'number', 'width' => 18],
        ['key' => 'FTop',        'label' => 'TOP (Hari)',       'type' => 'integer', 'width' => 11],
        ['key' => 'FReal',       'label' => 'Real (Hari)',      'type' => 'integer', 'width' => 11],
        ['key' => 'FSelisih',    'label' => 'Selisih (Hari)',   'type' => 'integer', 'width' => 13],
        ['key' => 'FKeterangan', 'label' => 'Keterangan',       'width' => 36],
    ];

    /** Dipakai bila usp_getcabangoverdue gagal (mis. DB crm tidak ada di lokal). */
    private const CABANG_FALLBACK = ['MEDAN', 'JAKARTA', 'SURABAYA', 'MAKASSAR', 'SEMARANG', 'BITUNG'];

    protected $model;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->model = new MpiutangoverdueModel();
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index()
    {
        $data['title'] = 'Laporan Piutang Overdue';
        return $this->render('piutangoverdue/gridpiutangoverdue', $data);
    }

    /**
     * Sumber data dropdown filter (JSON list string).
     * type: cabang | bulan | marketing | shipper
     */
    public function options()
    {
        $type      = (string) $this->request->getGet('type');
        $cabang    = $this->param('cabang');
        $bulan     = $this->param('bulan');
        $marketing = $this->param('marketing');

        try {
            switch ($type) {
                case 'cabang':
                    try {
                        $list = $this->model->getCabang();
                    } catch (\Throwable $e) {
                        log_message('error', 'Piutangoverdue cabang (usp_getcabangoverdue): ' . $e->getMessage());
                        $list = self::CABANG_FALLBACK;
                    }
                    if (!in_array('ALL', $list, true)) {
                        array_unshift($list, 'ALL');
                    }
                    break;
                case 'bulan':
                    $list = $this->model->getBulan($cabang);
                    break;
                case 'marketing':
                    $list = $this->model->getMarketing($cabang, $bulan);
                    break;
                case 'shipper':
                    $list = $this->model->getShipper($cabang, $bulan, $marketing);
                    break;
                default:
                    return $this->response->setStatusCode(400)->setJSON(['error' => 'type tidak valid']);
            }
        } catch (\Throwable $e) {
            log_message('error', "Piutangoverdue::options($type): " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Gagal memuat data ' . $type]);
        }

        return $this->response->setJSON($list);
    }

    public function grid()
    {
        $page  = max(1, (int) ($this->request->getPost('page') ?: 1));
        $limit = (int) ($this->request->getPost('totalrows') ?: $this->request->getPost('rows') ?: 50);
        $limit = max(1, $limit);

        try {
            $rows = $this->loadRows('post');
        } catch (\Throwable $e) {
            log_message('error', 'Piutangoverdue::grid (usp_getlistshipperoverdue): ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Gagal memuat data laporan']);
        }

        $count       = count($rows);
        $total_pages = $count > 0 ? (int) ceil($count / $limit) : 0;
        if ($page > $total_pages) {
            $page = max(1, $total_pages);
        }
        $start = ($page - 1) * $limit;

        $responce          = new \stdClass();
        $responce->page    = $page;
        $responce->total   = $total_pages;
        $responce->records = $count;
        $responce->rows    = [];
        foreach (array_slice($rows, $start, $limit) as $i => $row) {
            $row['id'] = $start + $i + 1;
            $responce->rows[] = $row;
        }
        $responce->userdata = [
            'GrandTotalNominal' => array_sum(array_column($rows, 'FNominal')),
        ];

        return $this->response->setJSON($responce);
    }

    /**
     * Export Excel: isi sama dengan grid (filter atas, filter toolbar dan
     * sort yang sedang aktif), tanpa paging.
     *
     * Mode sinkron: file dibuat per chunk lalu langsung di-download.
     * Untuk mode background + socket, lihat Config\Export dan
     * App\Libraries\Export\SocketExportNotifier.
     */
    public function excel()
    {
        try {
            $rows = $this->loadRows('get');
        } catch (\Throwable $e) {
            log_message('error', 'Piutangoverdue::excel: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Gagal memuat data laporan');
        }

        $cabang = $this->param('cabang');
        $bulan  = $this->param('bulan');

        $exporter = new ExcelExporter(self::EXCEL_COLUMNS, new NullExportNotifier());
        $exporter->setTitle('LAPORAN PIUTANG OVERDUE', [
            'Cabang'      => $cabang,
            'Bulan'       => $bulan,
            'Marketing'   => $this->param('marketing'),
            'Shipper'     => $this->param('shipper'),
            'Jumlah Hari' => (string) $this->jumlahHari('get'),
            'Dicetak'     => date('d-m-Y H:i:s'),
        ]);

        $fileName = 'Laporan_Piutang_Overdue_' . $cabang . '_' . $bulan . '_' . date('Ymd_His');

        try {
            $path = $exporter->export(
                ExcelExporter::chunk($rows),
                count($rows),
                $fileName,
                ['FNInvoice' => 'GRAND TOTAL', 'FNominal' => array_sum(array_column($rows, 'FNominal'))]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Piutangoverdue::excel write: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Gagal membuat file Excel');
        }

        return ExcelExporter::download($path, preg_replace('/[^A-Za-z0-9_\-]+/', '_', $fileName) . '.xlsx');
    }

    /**
     * Ambil data dari SP lalu terapkan filter toolbar & sort.
     * Dipakai bersama oleh grid() (POST) dan excel() (GET) agar isinya sama.
     */
    private function loadRows(string $method): array
    {
        $in = fn(string $k) => $method === 'post' ? $this->request->getPost($k) : $this->request->getGet($k);

        $rows = $this->model->getList(
            $this->param('cabang', $method),
            $this->param('bulan', $method),
            $this->param('marketing', $method),
            $this->param('shipper', $method),
            $this->jumlahHari($method)
        );

        // Format tanggal untuk tampilan (juga dipakai filter toolbar).
        foreach ($rows as &$r) {
            foreach (self::DATES as $f) {
                $r[$f] = $r[$f] ? date('d-M-Y', strtotime($r[$f])) : '';
            }
        }
        unset($r);

        $sord = strtolower((string) $in('sord')) === 'asc' ? 'asc' : 'desc';
        $rows = $this->applyFilters($rows, $in('filters'));

        return $this->applySort($rows, (string) $in('sidx'), $sord); // sidx kosong = urutan asli SP
    }

    private function jumlahHari(string $method): int
    {
        $val = $method === 'post' ? $this->request->getPost('jumlahhari') : $this->request->getGet('jumlahhari');
        return (int) ($val ?? 14);
    }

    /** Ambil parameter filter, default 'ALL'. */
    private function param(string $name, string $method = 'get'): string
    {
        $val = $method === 'post' ? $this->request->getPost($name) : $this->request->getGet($name);
        $val = trim((string) $val);
        return $val === '' ? 'ALL' : $val;
    }

    /** Filter toolbar jqGrid (JSON filters) diterapkan di memori. */
    private function applyFilters(array $rows, $filters): array
    {
        if (!$filters) {
            return $rows;
        }
        $f = json_decode((string) $filters, true);
        if (!is_array($f) || empty($f['rules'])) {
            return $rows;
        }

        $rules = [];
        foreach ($f['rules'] as $rule) {
            $field = $rule['field'] ?? '';
            $data  = trim((string) ($rule['data'] ?? ''));
            if ($data !== '' && in_array($field, self::FIELDS, true)) {
                $rules[] = [$field, mb_strtolower($data)];
            }
        }
        if (!$rules) {
            return $rows;
        }
        $isOr = strtoupper($f['groupOp'] ?? 'AND') === 'OR';

        return array_values(array_filter($rows, static function ($row) use ($rules, $isOr) {
            foreach ($rules as [$field, $needle]) {
                $val = $row[$field];
                if (in_array($field, self::NUMERIC, true)) {
                    // cocokkan juga format ribuan, mis. "4,044,000"
                    $hay = mb_strtolower(number_format((float) $val, 0, '.', ',') . ' ' . (string) (0 + $val));
                } else {
                    $hay = mb_strtolower((string) $val);
                }
                $match = mb_strpos($hay, $needle) !== false;
                if ($isOr && $match) {
                    return true;
                }
                if (!$isOr && !$match) {
                    return false;
                }
            }
            return !$isOr;
        }));
    }

    private function applySort(array $rows, string $sidx, string $sord): array
    {
        // Tanpa kolom sort (atau kolom tidak dikenal): pertahankan urutan dari SP.
        if (!in_array($sidx, self::FIELDS, true)) {
            return $rows;
        }
        $dir = $sord === 'asc' ? 1 : -1;

        usort($rows, static function ($a, $b) use ($sidx, $dir) {
            if (in_array($sidx, self::DATES, true)) {
                $x = $a[$sidx] ? strtotime($a[$sidx]) : 0;
                $y = $b[$sidx] ? strtotime($b[$sidx]) : 0;
            } elseif (in_array($sidx, self::NUMERIC, true)) {
                $x = $a[$sidx];
                $y = $b[$sidx];
            } else {
                $x = mb_strtolower((string) $a[$sidx]);
                $y = mb_strtolower((string) $b[$sidx]);
            }
            return ($x <=> $y) * $dir;
        });

        return $rows;
    }
}
