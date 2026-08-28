<?php
namespace App\Models;

use CodeIgniter\Model;

// Migrated from CI3: application/models/Mlog.php


class MlogModel extends Model
{
    protected $table      = 'log_activity';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = []; // TODO: Add allowed fields
    protected $useTimestamps = false;


    private $CI;
    public function __construct() {
        parent::__construct();
        // $this->CI =& get_instance();
        // $this->database=$this->CI->load->database('dbglobal', TRUE);
    }

    public function saveLog($data,$message=null,$MessageError=null){
        $id_user = session()->get(SESSION_NAME.'userpk') ?: 0;
        $router = service('router');

        // `user_id` di atas adalah pemilik akun — dan pada sesi Panel Casting
        // itu justru orang yang TIDAK melakukan apa-apa. Tanpa jejak berikut,
        // baris log ini terbaca seolah-olah dia sendiri yang mengerjakannya,
        // dan tidak ada apa pun di tabel ini yang bisa membantahnya.
        if (\App\Libraries\AuditUser::isImpersonating()) {
            $jejak   = '[' . \App\Libraries\AuditUser::describe() . ']';
            $message = ! empty($message) ? $message . ' ' . $jejak : $jejak;
        }

        $dataActivity=[
            'user_id'       => $id_user,
            'module'        => !empty($data) ? basename(FCPATH) : null,
            'controller'    => $router->controllerName(),
            'action'        => $router->methodName(),
            'message'       => !empty($message) ? $message : null,
            'message_error' => !empty($MessageError) ? $MessageError : null,
            'ip'            => ip(),
            'detect'        => detect()
        ];

        $this->db->table('log_activity')->insert($dataActivity);
    }
}

