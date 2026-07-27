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

        $valDari = null;
        $valSampai = null;
        if (!empty($tgl_dari)) {
            $valDari = substr($tgl_dari, 3, 4) . substr($tgl_dari, 0, 2);
        }
        if (!empty($tgl_sampai)) {
            $valSampai = substr($tgl_sampai, 3, 4) . substr($tgl_sampai, 0, 2);
        }

        if ($valDari !== null && $valSampai !== null && $valDari > $valSampai) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['error' => 'Bulan sampai tidak boleh lebih kecil dari Bulan dari!']);
            } else {
                session()->setFlashdata('error_grafik', 'Bulan sampai tidak boleh lebih kecil dari Bulan dari!');
            }
        }

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
        $minBulan = '';
        $maxBulan = '';
        if (method_exists($this->mgrafik, $method)) {
            // Dapatkan seluruh data tanpa filter where SQL (karena format bulan antar cabang tidak konsisten)
            $dataSemuaRaw = $this->mgrafik->$method('')->getResultArray();
            
            // Normalisasi, konversi, dan sorting data di PHP
            $dataSemuaClean = [];
            foreach ($dataSemuaRaw as $row) {
                if (empty($row['bulan'])) continue;
                
                $normBulan = $this->normalizeBulan($row['bulan']);
                if (!$normBulan) continue;
                
                $row['bulan'] = $normBulan; // Timpa format aslinya ke MM-YYYY
                $sortKey = substr($normBulan, 3, 4) . substr($normBulan, 0, 2); // YYYYMM
                $row['_sortKey'] = (int)$sortKey;
                $dataSemuaClean[] = $row;
            }

            // Sort berdasarkan YYYYMM secara Ascending
            usort($dataSemuaClean, function($a, $b) {
                return $a['_sortKey'] <=> $b['_sortKey'];
            });

            if (!empty($dataSemuaClean)) {
                $minBulan = $dataSemuaClean[0]['bulan'];
                $maxBulan = $dataSemuaClean[count($dataSemuaClean) - 1]['bulan'];
            }

            // Filter secara manual di PHP
            foreach ($dataSemuaClean as $row) {
                if ($valDari !== null && $row['_sortKey'] < (int)$valDari) continue;
                if ($valSampai !== null && $row['_sortKey'] > (int)$valSampai) continue;
                
                $dataMentah[] = $row;
            }
        }
        
        $data['minBulan'] = $minBulan;
        $data['maxBulan'] = $maxBulan;

        // Auto-populate input filter values using the actual data range if not submitted
        if (empty($tgl_dari) && !empty($minBulan)) {
            $data['tgl_dari'] = $minBulan;
        }
        if (empty($tgl_sampai) && !empty($maxBulan)) {
            $data['tgl_sampai'] = $maxBulan;
        }
        $data['debug_raw_data'] = isset($dataSemuaRaw) ? array_slice($dataSemuaRaw, 0, 10) : [];

        $dataProcessed = $this->processData($dataMentah, 'CABANG', $namaCabangLengkap);
        $data = array_merge($data, $dataProcessed);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($data);
        }

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
                // Lewati data yang bulannya kosong atau formatnya tidak valid (menghindari error Undefined array key -1)
                if (empty($row['bulan']) || strlen(trim($row['bulan'])) < 7) {
                    continue;
                }

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
                    $TotalBiayaPerBulan[$groupbln] += (float)$biaya;
                    $TotalLabaPerBulan[$groupbln] += (float)$laba;
                } else {
                    $groupbln++;
                    $bulan[$groupbln] = "'" . $txtbln[(int)$blndicari - 1] . " " . $thndicari . "'";
                    $tahun[$groupbln] = $thndicari;
                    $TotalBiayaPerBulan[$groupbln] = (float)$biaya;
                    $TotalLabaPerBulan[$groupbln] = (float)$laba;
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

    private function normalizeBulan($bulanStr)
    {
        $bulanStr = strtoupper(trim($bulanStr));
        
        // Coba tangkap tahun (format 20XX)
        $year = '';
        if (preg_match('/(20\d{2})/', $bulanStr, $m)) {
            $year = $m[1];
        } else {
            return null; // Harus ada tahun
        }

        // Jika format aslinya sudah berupa MM-YYYY atau YYYY-MM
        if (preg_match('/^(\d{2})-(\d{4})$/', $bulanStr, $m)) {
            return $m[1] . '-' . $m[2];
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $bulanStr, $m)) {
            return $m[2] . '-' . $m[1];
        }
        
        // Mapping teks bulan (mencakup singkatan unik seperti AGUS)
        $map = [
            'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04', 
            'MEI' => '05', 'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 
            'AGS' => '08', 'AGU' => '08', 'AUG' => '08', 'SEP' => '09', 
            'OKT' => '10', 'OCT' => '10', 'NOV' => '11', 'DES' => '12', 'DEC' => '12'
        ];
        
        foreach ($map as $txt => $num) {
            if (strpos($bulanStr, $txt) !== false) {
                return $num . '-' . $year;
            }
        }
        
        // Coba parsing jika 2 digit awalnya angka (misal "07 2025" atau "07/2025")
        if (preg_match('/^(\d{2})\b/', $bulanStr, $m)) {
            return $m[1] . '-' . $year;
        }
        
        return null;
    }
}
