<?php

namespace App\Controllers;

use App\Models\MtruckingtradoluartasModel;

class Truckingtradoluartassby extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new MtruckingtradoluartasModel();
    }

    public function index()
    {
        $cabang = 'SBY';
        
        $comboBulan = $this->model->getComboBulan($cabang);
        $comboJenisTrado = $this->model->getComboJenisTrado($cabang);

        $htmlComboBulan = '<option value="ALL">ALL</option>';
        foreach ($comboBulan as $row) {
            $htmlComboBulan .= '<option value="' . $row['FBulan'] . '">' . $row['FBulan'] . '</option>';
        }

        $htmlComboJenisTrado = '<option value="All">All</option>';
        foreach ($comboJenisTrado as $row) {
            $htmlComboJenisTrado .= '<option value="' . $row['FJenisTrado'] . '">' . $row['FJenisTrado'] . '</option>';
        }

        $data = [
            'title' => 'Penggunaan Trado Luar TAS Per Bulan (SBY)',
            'menuaktif' => 'Laporan Trucking',
            'comboBulan' => $htmlComboBulan,
            'comboJenisTrado' => $htmlComboJenisTrado,
        ];
        
        return $this->render('truckingtradoluartassby/index', $data);
    }

    public function grid()
    {
        $cabang = 'SBY';
        
        $params = [
            'page'    => $this->request->getPost('page'),
            'rows'    => $this->request->getPost('rows'),
            'sidx'    => $this->request->getPost('sidx'),
            'sord'    => $this->request->getPost('sord'),
            '_search' => $this->request->getPost('_search'),
            'filters' => $this->request->getPost('filters'),
            'bulan'   => $this->request->getPost('bulan'),
            'jenis_trado' => $this->request->getPost('jenis_trado'),
        ];

        $data = $this->model->getGridData($cabang, $params);
        $lastUpdate = $this->model->getLastUpdate($cabang);
        
        $data['userdata']['last_update'] = $lastUpdate;
        
        return $this->response->setJSON($data);
    }
}
