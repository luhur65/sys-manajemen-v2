<?php

namespace App\Controllers;

use App\Models\MtracingModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Migrated from CI3: application/controllers/Home.php

class Home extends BaseController
{
    protected $mtracingModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
         
        date_default_timezone_set("Asia/Jakarta");
        ini_set('memory_limit', '-1');
        
        $this->response->setHeader("Expires", gmdate("D, d M Y H:i:s", time()) . " GMT");
        $this->response->setHeader("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->setHeader("Cache-Control", "no-cache, must-revalidate");
        $this->response->setHeader("Pragma", "no-cache");
        
        $this->mtracingModel = new MtracingModel();
    }

    public function index()
    {
        $data['title'] = 'Dashboard';
        $data['menuaktif'] = 'home';
        
        // Define buttons based on permissions
        $buttons = [];
        
        if (hasMenuAccess('truckingtradoluar', 'index')) {
            $buttons[] = [
                'title' => 'Pengunaan Trado Luar',
                'link'  => base_url('truckingtradoluar'),
                'icon'  => 'fas fa-truck-moving',
                'color' => 'bg-info'
            ];
        }
        
        if (hasMenuAccess('truckingtradodalam', 'index')) {
            $buttons[] = [
                'title' => 'Pengunaan Trado Dalam',
                'link'  => base_url('truckingtradodalam'),
                'icon'  => 'fas fa-truck',
                'color' => 'bg-success'
            ];
        }
        
        if (hasMenuAccess('truckingemkllain', 'index')) {
            $buttons[] = [
                'title' => 'Pengunaan EMKL Lain',
                'link'  => base_url('truckingemkllain'),
                'icon'  => 'fas fa-ship',
                'color' => 'bg-warning'
            ];
        }
        
        if (hasMenuAccess('truckinghargatradoluarlebihmahal', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Harga Trado Luar Yang Lebih Mahal',
                'link'  => base_url('truckinghargatradoluarlebihmahal'),
                'icon'  => 'fas fa-file-invoice-dollar',
                'color' => 'bg-danger'
            ];
        }
        
        if (hasMenuAccess('ritasitrado', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Ritasi Trado',
                'link'  => base_url('ritasitrado'),
                'icon'  => 'fas fa-chart-line',
                'color' => 'bg-primary'
            ];
        }
        
        if (hasMenuAccess('statuskendaraan', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Status Kendaraan',
                'link'  => base_url('statuskendaraan'),
                'icon'  => 'fas fa-clipboard-check',
                'color' => 'bg-olive'
            ];
        }
        
        if (hasMenuAccess('rekapmarketing', 'cabang')) {
            $buttons[] = [
                'title' => 'Laporan Rekap Marketing',
                'link'  => base_url('rekapmarketing/cabang'),
                'icon'  => 'fas fa-users',
                'color' => 'bg-purple'
            ];
        }

        if (hasMenuAccess('omset', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Omset Emkl',
                'link'  => base_url('omset'),
                'icon'  => 'fas fa-money-bill',
                'color' => 'bg-purple'
            ];
        }

        if (hasMenuAccess('piutangemkl', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Piutang Emkl',
                'link'  => base_url('piutangemkl'),
                'icon'  => 'fas fa-money-bill',
                'color' => 'bg-olive'
            ];
        }

        if (hasMenuAccess('piutangoverdue', 'index')) {
            $buttons[] = [
                'title' => 'Laporan Piutang Overdue',
                'link'  => base_url('piutangoverdue'),
                'icon'  => 'fas fa-exclamation-triangle',
                'color' => 'bg-maroon'
            ];
        }

        $data['buttons'] = $buttons;
        
        return $this->render('home', $data);
    }
}
