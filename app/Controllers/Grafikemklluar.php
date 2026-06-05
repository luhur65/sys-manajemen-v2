<?php

namespace App\Controllers;

use App\Models\MtruckingemklluarModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Grafikemklluar extends BaseController
{
    protected $mtruckingemklluar;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
        $this->mtruckingemklluar = new MtruckingemklluarModel();
    }

    public function index()
    {
        $data['menuaktif'] = 'Grafik';
        $data['title'] = 'Grafik Penggunaan EMKL Luar';

        // Fetch & Process Data for each branch using DB
        $dataJKT = $this->processData($this->mtruckingemklluar->get_whereJKT('')->getResultArray(), 'Jakarta');
        $dataMDN = $this->processData($this->mtruckingemklluar->get_whereMDN('')->getResultArray(), 'Medan');
        $dataSBY = $this->processData($this->mtruckingemklluar->get_whereSBY('')->getResultArray(), 'Surabaya');
        $dataMKS = $this->processData($this->mtruckingemklluar->get_whereMKS('')->getResultArray(), 'Makassar');
        
        // Merge into view data
        $data = array_merge($data, $dataJKT, $dataMDN, $dataSBY, $dataMKS);

        return $this->render('trucking/grafikemklluar', $data);
    }

    private function processData($result, $cabangName)
    {
        $prefix = '';
        if ($cabangName == 'Jakarta') $prefix = 'JKT';
        elseif ($cabangName == 'Medan') $prefix = 'MDN';
        elseif ($cabangName == 'Surabaya') $prefix = 'SBY';
        elseif ($cabangName == 'Makassar') $prefix = 'MKS';

        if (!empty($result)) {
            $nomor = 0;
            $groupbln = 0;
            $txtbln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
            
            $TotalEmklluarPerBulan = [];
            $bulan = [];
            $tahun = [];
            $blnsebelumnya = '';

            foreach ($result as $row) {
                $thndicari = substr($row['FTgl'], 0, 4);
                $tgldicari = substr($row['FTgl'], 8, 2);
                $blndicari = substr($row['FTgl'], 5, 2);

                if ($nomor == 0) {
                    $blnsebelumnya = $blndicari;
                }

                $emklluar = $row['FUkuran20'] + $row['FUkuran2x20'] + $row['FUkuran40'];

                if ($blndicari == $blnsebelumnya) {
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    if ($nomor == 0) {
                        $TotalEmklluarPerBulan[$groupbln] = 0;
                    }
                    $TotalEmklluarPerBulan[$groupbln] += $emklluar;
                } else {
                    $groupbln++;
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    $TotalEmklluarPerBulan[$groupbln] = $emklluar;
                }

                $blnsebelumnya = $blndicari;
                $nomor++;
            }

            $tahunRange = '[]';
            if (!empty($tahun)) {
                if ($tahun[0] == end($tahun)) {
                    $tahunRange = $tahun[0];
                } else {
                    $tahunRange = $tahun[0] . ' - ' . end($tahun);
                }
            }

            return [
                "cabang{$prefix}" => $cabangName,
                "FTgl{$prefix}" => $bulan,
                "Tahun{$prefix}" => $tahunRange,
                "jlhbln{$prefix}" => count($bulan),
                "TotalEmklluar{$prefix}" => $TotalEmklluarPerBulan
            ];
        } else {
            return [
                "cabang{$prefix}" => $cabangName,
                "FTgl{$prefix}" => '[]',
                "Tahun{$prefix}" => '[]',
                "jlhbln{$prefix}" => 0,
                "TotalEmklluar{$prefix}" => '[]'
            ];
        }
    }
}
