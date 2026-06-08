<?php

namespace App\Controllers;

use App\Models\MtruckingtradoluardetailexpModel;

class Truckingtradoluardetailexp extends BaseController
{
    protected $tradoluarModel;

    public function __construct()
    {
        $this->tradoluarModel = new MtruckingtradoluardetailexpModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Pemakaian Trado Luar Detail',
            'menuaktif' => 'Laporan Trucking'
        ];
        
        return $this->render('truckingtradoluardetailexp/index', $data);
    }

    public function grid()
    {
        $cabang = $this->request->getPost('cabang') ?? 'MDN';
        
        $params = [
            'page'    => $this->request->getPost('page'),
            'rows'    => $this->request->getPost('rows'),
            'sidx'    => $this->request->getPost('sidx'),
            'sord'    => $this->request->getPost('sord'),
            '_search' => $this->request->getPost('_search'),
            'filters' => $this->request->getPost('filters'),
            'tgl_dari' => $this->request->getPost('tgl_dari'),
            'tgl_sampai' => $this->request->getPost('tgl_sampai'),
        ];

        $data = $this->tradoluarModel->getGridData($cabang, $params);
        $lastUpdate = $this->tradoluarModel->getLastUpdate($cabang);
        
        $data['userdata']['last_update'] = $lastUpdate;
        
        return $this->response->setJSON($data);
    }
}
