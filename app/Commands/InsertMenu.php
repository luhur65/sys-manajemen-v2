<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class InsertMenu extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'menu:insert';
    protected $description = 'Insert new menu (ACOS + menu + ACL) secara interaktif';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        $className   = CLI::getOption('class') ?? CLI::prompt('Nama class controller (contoh: Grafikbiayakantorbandinglaba)', null, 'required');
        $methodName   = CLI::getOption('method') ?? CLI::prompt('Nama method', 'index');
        $namaAco      = CLI::getOption('displayname') ?? CLI::prompt('Display name ACOS', null, 'required');
        $menuName     = CLI::getOption('menuname') ?? CLI::prompt('Nama menu (menuname)', null, 'required');
        $menuIcon     = CLI::getOption('icon') ?? CLI::prompt('Icon menu', 'ICON-GRAPH');
        $link         = CLI::getOption('link') ?? CLI::prompt('Link menu', strtolower($className));
        $parentKeyword = CLI::getOption('parent') ?? CLI::prompt('Kata kunci menu parent (kosongkan untuk root)', '');
        $roleId       = (int) (CLI::getOption('role') ?? CLI::prompt('Role ID untuk ACL', '3'));

        try {
            // 1. Insert ke tblacos

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
            $parentId = 0;
            if ($parentKeyword !== '') {
                $parent = $db->table('tblmenu')->like('menuname', $parentKeyword, 'both')->where('menuparent', 0)->get()->getRow();
                $parentId = $parent ? $parent->menuid : 0;
            }
            
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
                    'menuname' => $menuName,
                    'menuseq' => $nextSeq,
                    'menuparent' => $parentId,
                    'menuicon' => $menuIcon,
                    'menuexe' => '',
                    'acoid' => $acoId,
                    'link' => $link,
                    'modifiedby' => 'admin',
                    'modifiedon' => date('Y-m-d H:i:s')
                ];
                $db->table('tblmenu')->insert($dataMenu);
                CLI::write("Menu berhasil dibuat dengan ID: " . $menuId, 'green');
            }

            // 3. Insert ke tblacl
            $cekAcl = $db->table('tblacl')->where('roleid', $roleId)->where('acoid', $acoId)->get()->getRow();
            if ($cekAcl) {
                CLI::write("ACL untuk role {$roleId} sudah ada.", 'green');
            } else {
                $lastAcl = $db->table('tblacl')->selectMax('aclid')->get()->getRow();
                $aclId = ($lastAcl && $lastAcl->aclid) ? $lastAcl->aclid + 1 : 1;

                $dataAcl = [
                    'aclid' => $aclId,
                    'roleid' => $roleId,
                    'acoid' => $acoId,
                    'modifiedby' => 'admin',
                    'modifiedon' => date('Y-m-d H:i:s')
                ];
                $db->table('tblacl')->insert($dataAcl);
                CLI::write("ACL untuk role {$roleId} berhasil ditambahkan.", 'green');
            }

        } catch (\Exception $e) {
            $cols = $db->getFieldNames('tblacl');
            CLI::error("Error: " . $e->getMessage() . "\nKolom tblacl: " . implode(', ', $cols));
        }
    }
}
