<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MgrafikbiayakantorbandinglabaModel;

class Grafikbiayakantorbandinglaba extends BaseController
{
    protected $mgrafik;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
        
        // Inisialisasi model
        $this->mgrafik = new MgrafikbiayakantorbandinglabaModel();
    }

    /** 
     * Fungsi menu Grafik penggunaan emkl luar 
     * @AclName menu Grafik penggunaan emkl luar 
     */
    public function index()
    {
        $data['menuaktif'] = 'Grafik';
        $data['title'] = 'Grafik Biaya Kantor Banding Laba';

        // Ambil filter dari request
        $cabang = $this->request->getGet('cabang') ?? 'JKT';
        $tgl_dari = $this->request->getGet('tgl_dari'); 
        $tgl_sampai = $this->request->getGet('tgl_sampai');
        
        $data['selectedCabang'] = $cabang;
        $data['tgl_dari'] = $tgl_dari;
        $data['tgl_sampai'] = $tgl_sampai;

        $whereArr = [];

        if (!empty($tgl_dari)) {
            // Asumsi input dari monthpicker: MM-YYYY (contoh: 05-2026)
            $valDari = substr($tgl_dari, 3, 4) . substr($tgl_dari, 0, 2);
            $whereArr[] = "(RIGHT(bulan, 4) + LEFT(bulan, 2)) >= '$valDari'";
        }
        if (!empty($tgl_sampai)) {
            $valSampai = substr($tgl_sampai, 3, 4) . substr($tgl_sampai, 0, 2);
            $whereArr[] = "(RIGHT(bulan, 4) + LEFT(bulan, 2)) <= '$valSampai'";
        }

        $where = count($whereArr) > 0 ? implode(" AND ", $whereArr) : "";

        $method = 'get_where' . $cabang;
        $cabangNames = [
            'JKT' => 'Jakarta',
            'MDN' => 'Medan',
            'SBY' => 'Surabaya',
            'MKS' => 'Makassar',
            'BTG' => 'Bitung',
            'SMG' => 'Semarang'
        ];
        $namaCabangLengkap = $cabangNames[$cabang] ?? 'Jakarta';

        $dataMentah = [];
        if (method_exists($this->mgrafik, $method)) {
            $dataMentah = $this->mgrafik->$method($where)->getResultArray();
        }
        
        // Auto-populate input filter values using the actual data range if not submitted
        if (empty($tgl_dari) && !empty($dataMentah)) {
            $data['tgl_dari'] = $dataMentah[0]['bulan'];
        }
        if (empty($tgl_sampai) && !empty($dataMentah)) {
            $data['tgl_sampai'] = $dataMentah[count($dataMentah) - 1]['bulan'];
        }
        
        $dataProcessed = $this->processData($dataMentah, 'CABANG', $namaCabangLengkap);
        $data = array_merge($data, $dataProcessed);

        return $this->render('grafik/grafikbiayakantorbandinglaba', $data);
    }

    private function processData($result, $prefix, $cabangName)
    {
        if (!empty($result)) {
            $nomor = 0;
            $groupbln = 0;
            $txtbln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
            
            $TotalBiayaPerBulan = [];
            $TotalLabaPerBulan = [];
            $bulan = [];
            $tahun = [];
            $blnsebelumnya = '';
            $lastUpdate = '';

            foreach ($result as $row) {
                if (isset($row['ftglinput']) && $row['ftglinput'] > $lastUpdate) {
                    $lastUpdate = $row['ftglinput'];
                }

                $blndicari = substr($row['bulan'], 0, 2);
                $thndicari = substr($row['bulan'], 3, 4);

                if ($nomor == 0) {
                    $blnsebelumnya = $blndicari;
                }

                $biaya = $row['FBiaya'] ?? 0;
                $laba  = $row['FLaba'] ?? 0;

                if ($blndicari == $blnsebelumnya) {
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    if ($nomor == 0) {
                        $TotalBiayaPerBulan[$groupbln] = 0;
                        $TotalLabaPerBulan[$groupbln] = 0;
                    }
                    $TotalBiayaPerBulan[$groupbln] += $biaya;
                    $TotalLabaPerBulan[$groupbln] += $laba;
                } else {
                    $groupbln++;
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    $TotalBiayaPerBulan[$groupbln] = $biaya;
                    $TotalLabaPerBulan[$groupbln] = $laba;
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

            $formattedLastUpdate = !empty($lastUpdate) ? date('d-m-Y H:i:s', strtotime($lastUpdate)) : '-';

            return [
                "cabang{$prefix}" => $cabangName,
                "FTgl{$prefix}" => $bulan,
                "Tahun{$prefix}" => $tahunRange,
                "jlhbln{$prefix}" => count($bulan),
                "TotalBiaya{$prefix}" => $TotalBiayaPerBulan,
                "TotalLaba{$prefix}" => $TotalLabaPerBulan,
                "LastUpdate{$prefix}" => $formattedLastUpdate
            ];
        } else {
            return [
                "cabang{$prefix}" => $cabangName,
                "FTgl{$prefix}" => '[]',
                "Tahun{$prefix}" => '[]',
                "jlhbln{$prefix}" => 0,
                "TotalBiaya{$prefix}" => '[]',
                "TotalLaba{$prefix}" => '[]',
                "LastUpdate{$prefix}" => '-'
            ];
        }
    }
}
