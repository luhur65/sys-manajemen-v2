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
        
        if (hasPermission('truckingtradoluar', 'index')) {
            $buttons[] = [
                'title' => 'Pengunaan Trado Luar',
                'link'  => base_url('truckingtradoluar'),
                'icon'  => 'fas fa-truck-moving',
                'color' => 'bg-info'
            ];
        }
        
        // if (hasPermission('truckingtradodalam', 'index')) {
        //     $buttons[] = [
        //         'title' => 'Pengunaan Trado Dalam',
        //         'link'  => base_url('truckingtradodalam'),
        //         'icon'  => 'fas fa-truck',
        //         'color' => 'bg-success'
        //     ];
        // }
        
        // if (hasPermission('truckingemkllain', 'index')) {
        //     $buttons[] = [
        //         'title' => 'Pengunaan EMKL Lain',
        //         'link'  => base_url('truckingemkllain'),
        //         'icon'  => 'fas fa-ship',
        //         'color' => 'bg-warning'
        //     ];
        // }
        
        // if (hasPermission('truckinghargatradoluarlebihmahal', 'index')) {
        //     $buttons[] = [
        //         'title' => 'Laporan Harga Trado Luar Yang Lebih Mahal',
        //         'link'  => base_url('truckinghargatradoluarlebihmahal'),
        //         'icon'  => 'fas fa-file-invoice-dollar',
        //         'color' => 'bg-danger'
        //     ];
        // }
        
        // if (hasPermission('ritasitrado', 'index')) {
        //     $buttons[] = [
        //         'title' => 'Laporan Ritasi Trado',
        //         'link'  => base_url('ritasitrado'),
        //         'icon'  => 'fas fa-chart-line',
        //         'color' => 'bg-primary'
        //     ];
        // }
        
        // if (hasPermission('statuskendaraan', 'index')) {
        //     $buttons[] = [
        //         'title' => 'Laporan Status Kendaraan',
        //         'link'  => base_url('statuskendaraan'),
        //         'icon'  => 'fas fa-clipboard-check',
        //         'color' => 'bg-olive'
        //     ];
        // }
        
        // if (hasPermission('rekapmarketing', 'cabang')) {
        //     $buttons[] = [
        //         'title' => 'Laporan Rekap Marketing',
        //         'link'  => base_url('rekapmarketing/cabang'),
        //         'icon'  => 'fas fa-users',
        //         'color' => 'bg-purple'
        //     ];
        // }
        
        $data['buttons'] = $buttons;
        
        return $this->render('home', $data);
    }
}
