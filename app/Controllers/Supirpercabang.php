<?php

namespace App\Controllers;

use App\Models\MsupirpercabangModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Supirpercabang extends BaseController
{
    protected $msupirpercabang;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
        $this->msupirpercabang = new MsupirpercabangModel();
    }

    public function index()
    {
        $data['menuaktif'] = 'Supir Percabang';
        $data['title'] = 'Laporan Supir Per Cabang';
        return $this->render('supirpercabang/index', $data);
    }

    public function grid()
    {
        $request = $this->request->getPost();
        
        $page = isset($request['page']) ? intval($request['page']) : 1;
        $limit = isset($request['rows']) ? intval($request['rows']) : 50;
        $sidx = isset($request['sidx']) ? $request['sidx'] : 'FKSupir';
        $sord = isset($request['sord']) ? $request['sord'] : 'ASC';
        $filters = isset($request['filters']) ? $request['filters'] : '';
        $search = isset($request['_search']) ? $request['_search'] : false;

        $cbg = isset($request['cabang']) ? $request['cabang'] : 'MDN';
        if ($cbg == 'All') $cbg = 'MDN'; // default if someone sends 'All' but it's not supported natively or we map it to MDN
        
        // Map old dropdown to codes if needed, or if UI sends 'MDN', 'JKT', etc directly.
        $cabang = strtoupper($cbg);

        $where = "";
        if ($search == "true" || $search === true) {
            $where .= " AND (" . $this->operation($filters) . ")";
        }

        $count = $this->msupirpercabang->count_supir($where, $cabang);
        $total_pages = $count > 0 ? ceil($count / $limit) : 0;
        
        if ($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if ($start < 0) $start = 0;

        $data = $this->msupirpercabang->get_supir($start, $limit, $sidx, $sord, $where, $cabang);

        $response = new \stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $response->rows = [];

        $i = 0;
        foreach ($data->getResultArray() as $row) {
            $response->rows[$i]['id'] = $row['FKSupir'];
            $response->rows[$i]['cell'] = [
                $row['FKSupir'],
                $row['FNSupir'],
                $row['FAlamat'],
                $row['FKota'],
                $row['FTelp'],
                ($row['FAktif'] ? 'Aktif' : 'Tidak Aktif')
            ];
            $i++;
        }

        return $this->response->setJSON($response);
    }

    public function detail()
    {
        $id = $this->request->getPost('id');
        $cabang = $this->request->getPost('cabang');

        $detail = $this->msupirpercabang->detail_supir($id, $cabang);

        if ($detail) {
            $data['FKSupir'] = $detail['FKSupir'];
            $data['FNSupir'] = $detail['FNSupir'];
            $data['FAlamat'] = $detail['FAlamat'];
            $data['FKota'] = $detail['FKota'];
            $data['FTelp'] = $detail['FTelp'];
            $data['FStatus'] = ($detail['FAktif'] ? 'Aktif' : 'Tidak Aktif');
            
            $data['FotoSupir'] = $this->adatidak($detail['FFotoSupir']) ? $detail['FFotoSupir'] : base_url('asset/supir/nogambarsupir.jpg');
            $data['FotoSim'] = $this->adatidak($detail['FFotoSIM']) ? $detail['FFotoSIM'] : base_url('asset/supir/nogambarsim.jpg');
            $data['FotoKtp'] = $this->adatidak($detail['FFotoKTP']) ? $detail['FFotoKTP'] : base_url('asset/supir/nogambarktp.jpg');
            $data['FotoKK'] = $this->adatidak($detail['FFotoKK']) ? $detail['FFotoKK'] : base_url('asset/supir/nogambarkk.jpg');

            return view('supirpercabang/view', $data);
        } else {
            return "Data tidak ditemukan.";
        }
    }

    private function adatidak($file)
    {
        if (empty($file)) return false;
        $file_headers = @get_headers($file);
        if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found' || strpos($file_headers[0], '404') !== false) {
            return false;
        }
        return true;
    }

    private function operation($filters)
    {
        if (empty($filters)) return "1=1";
        $filters = json_decode($filters);
        if (!isset($filters->rules)) return "1=1";
        
        $where = "";
        $rules = $filters->rules;
        $groupOp = $filters->groupOp;
        
        foreach ($rules as $index => $rule) {
            $field = $rule->field;
            $data = $rule->data;
            
            if ($index > 0) {
                $where .= " " . $groupOp . " ";
            }
            
            $where .= " $field LIKE '%$data%' ";
        }
        
        return $where;
    }
}
