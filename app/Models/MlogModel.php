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

