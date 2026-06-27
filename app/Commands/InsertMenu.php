<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class InsertMenu extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'menu:insert';
    protected $description = 'Insert new menu for Grafik Biaya Kantor';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        try {
            // 1. Insert ke tblacos
            $className = 'Grafikbiayakantorbandinglaba';
            $methodName = 'index';
            $namaAco = 'menu Grafik Biaya Kantor Banding Laba';

            // Cek apakah sudah ada
            $cekAco = $db->table('tblacos')->where('class', $className)->where('method', $methodName)->get()->getRow();
            if ($cekAco) {
                $acoId = $cekAco->acosid;
                CLI::write("ACOS sudah ada dengan ID: " . $acoId, 'green');
            } else {
                $lastAco = $db->table('tblacos')->selectMax('acosid')->get()->getRow();
                $acoId = ($lastAco && $lastAco->acosid) ? $lastAco->acosid + 1 : 1;

                $dataAco = [
                    'acosid' => $acoId,
                    'class' => $className,
                    'method' => $methodName,
                    'displayname' => $namaAco,
                    'modifiedby' => 'admin',
                    'modifiedon' => date('Y-m-d H:i:s')
                ];
                $db->table('tblacos')->insert($dataAco);
                CLI::write("ACOS berhasil dibuat dengan ID: " . $acoId, 'green');
            }

            // 2. Insert ke tblmenu
            $parent = $db->table('tblmenu')->like('menuname', 'Grafik', 'both')->where('menuparent', 0)->get()->getRow();
            $parentId = $parent ? $parent->menuid : 0;
            
            $lastSeq = $db->table('tblmenu')->where('menuparent', $parentId)->selectMax('menuseq')->get()->getRow();
            $nextSeq = ($lastSeq && $lastSeq->menuseq) ? $lastSeq->menuseq + 1 : 1;

            $cekMenu = $db->table('tblmenu')->where('acoid', $acoId)->get()->getRow();
            if ($cekMenu) {
                CLI::write("Menu sudah ada dengan ID: " . $cekMenu->menuid, 'green');
            } else {
                $lastMenu = $db->table('tblmenu')->selectMax('menuid')->get()->getRow();
                $menuId = ($lastMenu && $lastMenu->menuid) ? $lastMenu->menuid + 1 : 1;

                $dataMenu = [
                    'menuid' => $menuId,
                    'menuname' => 'Grafik Biaya Kntr Bnding Laba',
                    'menuseq' => $nextSeq,
                    'menuparent' => $parentId,
                    'menuicon' => 'ICON-GRAPH',
                    'menuexe' => '',
                    'acoid' => $acoId,
                    'link' => 'grafikbiayakantorbandinglaba',
                    'modifiedby' => 'admin',
                    'modifiedon' => date('Y-m-d H:i:s')
                ];
                $db->table('tblmenu')->insert($dataMenu);
                CLI::write("Menu berhasil dibuat dengan ID: " . $menuId, 'green');
            }

            // 3. Insert ke tblacl
            $cekAcl = $db->table('tblacl')->where('roleid', 3)->where('acoid', $acoId)->get()->getRow();
            if ($cekAcl) {
                CLI::write("ACL untuk role 3 (Admin) sudah ada.", 'green');
            } else {
                $lastAcl = $db->table('tblacl')->selectMax('aclid')->get()->getRow();
                $aclId = ($lastAcl && $lastAcl->aclid) ? $lastAcl->aclid + 1 : 1;

                $dataAcl = [
                    'aclid' => $aclId,
                    'roleid' => 3,
                    'acoid' => $acoId,
                    'modifiedby' => 'admin',
                    'modifiedon' => date('Y-m-d H:i:s')
                ];
                $db->table('tblacl')->insert($dataAcl);
                CLI::write("ACL untuk role 3 (Admin) berhasil ditambahkan.", 'green');
            }

        } catch (\Exception $e) {
            $cols = $db->getFieldNames('tblacl');
            CLI::error("Error: " . $e->getMessage() . "\nKolom tblacl: " . implode(', ', $cols));
        }
    }
}
