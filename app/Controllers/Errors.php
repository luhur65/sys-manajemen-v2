<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Errors extends BaseController
{
    public function index()
    {
        return $this->show404('Controller or method not found.');
    }

    public function show404($message = 'Controller or method not found.')
    {
        $this->response->setStatusCode(404);
        return view('errors/html/error_404', ['message' => $message]);
    }

    public function show403($message = 'Access denied.')
    {
        $this->response->setStatusCode(403);
        return view('errors/html/error_403', ['message' => $message]);
    }

    public function show500($message = 'Internal server error.')
    {
        $this->response->setStatusCode(500);
        return view('errors/html/error_500', ['message' => $message]);
    }

    public function show401($message = 'Unauthorized.')
    {
        $this->response->setStatusCode(401);
        return view('errors/html/error_401', ['message' => $message]);
    }

    public function show405($message = 'Method not allowed.')
    {
        $this->response->setStatusCode(405);
        return view('errors/html/error_405', ['message' => $message]);
    }
}
