<?php

namespace App\Controllers;

use App\Models\MtruckingtradoluarModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Grafiktradoluar extends BaseController
{
    protected $mtruckingtradoluar;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
        $this->mtruckingtradoluar = new MtruckingtradoluarModel();
    }

    public function index()
    {
        $data['menuaktif'] = 'Grafik';
        $data['title'] = 'Grafik Penggunaan Trado Luar';

        // Fetch & Process Data for each branch using DB
        $dataJKT = $this->processData($this->mtruckingtradoluar->get_whereJKT('')->getResultArray(), 'Jakarta');
        $dataMDN = $this->processData($this->mtruckingtradoluar->get_whereMDN('')->getResultArray(), 'Medan');
        $dataSBY = $this->processData($this->mtruckingtradoluar->get_whereSBY('')->getResultArray(), 'Surabaya');
        $dataMKS = $this->processData($this->mtruckingtradoluar->get_whereMKS('')->getResultArray(), 'Makassar');
        
        // Merge into view data
        $data = array_merge($data, $dataJKT, $dataMDN, $dataSBY, $dataMKS);

        return $this->render('trucking/grafiktradoluar', $data);
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
            
            $TotalMuatanPerBulan = [];
            $TotalBongkaranPerBulan = [];
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

                $muatan = $row['FUkuran20Muatan'] + $row['FUkuran2x20Muatan'] + $row['FUkuran40Muatan'];
                $bongkaran = $row['FUkuran20Bongkaran'] + $row['FUkuran2x20Bongkaran'] + $row['FUkuran40Bongkaran'];

                if ($blndicari == $blnsebelumnya) {
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    if ($nomor == 0) {
                        $TotalMuatanPerBulan[$groupbln] = 0;
                        $TotalBongkaranPerBulan[$groupbln] = 0;
                    }
                    $TotalMuatanPerBulan[$groupbln] += $muatan;
                    $TotalBongkaranPerBulan[$groupbln] += $bongkaran;
                } else {
                    $groupbln++;
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    $TotalMuatanPerBulan[$groupbln] = $muatan;
                    $TotalBongkaranPerBulan[$groupbln] = $bongkaran;
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
                "TotalMuatan{$prefix}" => $TotalMuatanPerBulan,
                "TotalBongkaran{$prefix}" => $TotalBongkaranPerBulan
            ];
        } else {
            return [
                "cabang{$prefix}" => $cabangName,
                "FTgl{$prefix}" => '[]',
                "Tahun{$prefix}" => '[]',
                "jlhbln{$prefix}" => 0,
                "TotalMuatan{$prefix}" => '[]',
                "TotalBongkaran{$prefix}" => '[]'
            ];
        }
    }
}
