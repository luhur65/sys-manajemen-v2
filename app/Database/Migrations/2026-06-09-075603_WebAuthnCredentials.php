<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class WebAuthnCredentials extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'userpk' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'credentialId' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'credentialPublicKey' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tbluser_webauthn');
    }

    public function down()
    {
        $this->forge->dropTable('tbluser_webauthn');
    }
}
