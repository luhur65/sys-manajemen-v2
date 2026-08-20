<?php

namespace App\Controllers;

use App\Models\MshippernewModel;

class NewShipperCabang extends BaseController
{
    /**
     * Whitelist kolom filter grid jqGrid (tabel shipper baru).
     * Kolom di luar daftar ini ditolak oleh GridFilter.
     */
    protected array $filterFields = [
        'FNCabang',
        'FNShipper',
        'FNMarketing',
        'FTgl' => "FORMAT(CAST(FTgl AS DATETIME), 'dd-MMM-yyyy', 'en-US')",
    ];

    protected ?string $filterDbGroup = 'dbtruck';

    protected $mshippernewModel;

    public function __construct()
    {
        $this->mshippernewModel = new MshippernewModel();
    }

    public function mks()
    {
        $data = [
            'menuaktif' => 'Laporan Shipper Baru'
        ];
        return $this->render('newshippercabang/mks/index', $data);
    }

    public function getGridDataMks()
    {
        $page = $this->request->getPost('page') ?: 1;
        $limit = $this->request->getPost('rows') ?: 50;
        $sidx = $this->request->getPost('sidx') ?: 'FTgl';
        $sord = $this->request->getPost('sord') ?: 'desc';
        $search = $this->request->getPost('_search');
        $filters = $this->request->getPost('filters');
        
        $datefrom = $this->request->getPost('datefrom');
        $dateto = $this->request->getPost('dateto');
        
        $dateFilters = [];
        if(!empty($datefrom)) {
            $dateFilters['datefrom'] = date('Y-m-d', strtotime($datefrom));
        }
        if(!empty($dateto)) {
            $dateFilters['dateto'] = date('Y-m-d', strtotime($dateto));
        }

        $where = "";
        $operation = $search == "true" ? $this->operationAll($filters) : '';
        if ($operation !== '') {
            $where = "(" . $operation . ")";
        }

        $offset = ($page - 1) * $limit;
        
        $countResult = $this->mshippernewModel->getList('MAKASSAR', $dateFilters, $where, 0, 0, $sidx, $sord, true);
        $total_pages = 0;
        if ($countResult > 0) {
            $total_pages = ceil($countResult / $limit);
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $dataResult = $this->mshippernewModel->getList('MAKASSAR', $dateFilters, $where, $limit, $offset, $sidx, $sord, false);

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $countResult;
        $response->rows = [];

        $i = 0;
        foreach ($dataResult as $row) {
            $response->rows[$i]['id'] = $row['FNShipper'];
            $response->rows[$i]['cell'] = [
                $row['FNCabang'],
                $row['FNShipper'],
                $row['FTgl'] ? date('d-M-Y', strtotime($row['FTgl'])) : '',
                $row['FNMarketing']
            ];
            $i++;
        }

        return $this->response->setJSON($response);
    }

}
