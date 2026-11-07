<?php

namespace App\Controllers;

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
                        log_message('error', 'usp_getcabangoverdue: ' . $e->getMessage());
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
        $sidx  = (string) $this->request->getPost('sidx'); // kosong = urutan asli SP
        $sord  = strtolower((string) $this->request->getPost('sord')) === 'asc' ? 'asc' : 'desc';

        $jumlahHari = (int) ($this->request->getPost('jumlahhari') ?? 14);

        try {
            $rows = $this->model->getList(
                $this->param('cabang', 'post'),
                $this->param('bulan', 'post'),
                $this->param('marketing', 'post'),
                $this->param('shipper', 'post'),
                $jumlahHari
            );
        } catch (\Throwable $e) {
            log_message('error', 'usp_getlistshipperoverdue: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Gagal memuat data laporan']);
        }

        // Format tanggal untuk tampilan (juga dipakai filter toolbar).
        foreach ($rows as &$r) {
            foreach (self::DATES as $f) {
                $r[$f] = $r[$f] ? date('d-M-Y', strtotime($r[$f])) : '';
            }
        }
        unset($r);

        $rows = $this->applyFilters($rows, $this->request->getPost('filters'));
        $rows = $this->applySort($rows, $sidx, $sord);

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
