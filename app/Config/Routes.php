<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->setDefaultController('Login');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
// $routes->set404Override('App\Controllers\Errors::show404');
$routes->setAutoRoute(false);

$routes->get('/', 'Login::index');

// Routes for App\Controllers\Cabang (Cabang)
$routes->match(['GET', 'POST'], 'Cabang', 'Cabang::index');
$routes->match(['GET', 'POST'], 'cabang', 'Cabang::index');
$routes->match(['GET', 'POST'], 'Cabang/index', 'Cabang::index');
$routes->match(['GET', 'POST'], 'cabang/index', 'Cabang::index');
$routes->match(['GET', 'POST'], 'Cabang/grid', 'Cabang::grid');
$routes->match(['GET', 'POST'], 'cabang/grid', 'Cabang::grid');
$routes->match(['GET', 'POST'], 'Cabang/form/(:any)/(:any)', 'Cabang::form/$1/$2');
$routes->match(['GET', 'POST'], 'cabang/form/(:any)/(:any)', 'Cabang::form/$1/$2');
$routes->match(['GET', 'POST'], 'Cabang/crud', 'Cabang::crud');
$routes->match(['GET', 'POST'], 'cabang/crud', 'Cabang::crud');
$routes->match(['GET', 'POST'], 'Cabang/operation/(:any)', 'Cabang::operation/$1');
$routes->match(['GET', 'POST'], 'cabang/operation/(:any)', 'Cabang::operation/$1');
$routes->match(['GET', 'POST'], 'Cabang/excel', 'Cabang::excel');
$routes->match(['GET', 'POST'], 'cabang/excel', 'Cabang::excel');

// Routes for App\Controllers\CekSisaStok (CekSisaStok)
$routes->match(['GET', 'POST'], 'CekSisaStok', 'CekSisaStok::index');
$routes->match(['GET', 'POST'], 'ceksisastok', 'CekSisaStok::index');
$routes->match(['GET', 'POST'], 'CekSisaStok/index', 'CekSisaStok::index');
$routes->match(['GET', 'POST'], 'ceksisastok/index', 'CekSisaStok::index');
$routes->match(['GET', 'POST'], 'CekSisaStok/hakakses/(:any)', 'CekSisaStok::hakakses/$1');
$routes->match(['GET', 'POST'], 'ceksisastok/hakakses/(:any)', 'CekSisaStok::hakakses/$1');

// Routes for App\Controllers\Errors (Errors)
$routes->match(['GET', 'POST'], 'Errors/show404', 'Errors::show404');
$routes->match(['GET', 'POST'], 'errors/show404', 'Errors::show404');

// Routes for App\Controllers\Extension (Extension)
$routes->match(['GET', 'POST'], 'Extension/notfound', 'Extension::notfound');
$routes->match(['GET', 'POST'], 'extension/notfound', 'Extension::notfound');
$routes->match(['GET', 'POST'], 'Extension/cekSESSION', 'Extension::cekSESSION');
$routes->match(['GET', 'POST'], 'extension/ceksession', 'Extension::cekSESSION');
$routes->match(['GET', 'POST'], 'extension/cekSESSION', 'Extension::cekSESSION');
$routes->match(['GET', 'POST'], 'Extension/ceksession', 'Extension::cekSESSION');
$routes->match(['GET', 'POST'], 'Extension/getHariLibur/(:any)/(:any)', 'Extension::getHariLibur/$1/$2');
$routes->match(['GET', 'POST'], 'extension/getharilibur/(:any)/(:any)', 'Extension::getHariLibur/$1/$2');
$routes->match(['GET', 'POST'], 'extension/getHariLibur/(:any)/(:any)', 'Extension::getHariLibur/$1/$2');
$routes->match(['GET', 'POST'], 'Extension/getharilibur/(:any)/(:any)', 'Extension::getHariLibur/$1/$2');
$routes->match(['GET', 'POST'], 'Extension/getAllData/(:any)/(:any)/(:any)/(:any)', 'Extension::getAllData/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'extension/getalldata/(:any)/(:any)/(:any)/(:any)', 'Extension::getAllData/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'extension/getAllData/(:any)/(:any)/(:any)/(:any)', 'Extension::getAllData/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'Extension/getalldata/(:any)/(:any)/(:any)/(:any)', 'Extension::getAllData/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'Extension/getMargin/(:any)', 'Extension::getMargin/$1');
$routes->match(['GET', 'POST'], 'extension/getmargin/(:any)', 'Extension::getMargin/$1');
$routes->match(['GET', 'POST'], 'extension/getMargin/(:any)', 'Extension::getMargin/$1');
$routes->match(['GET', 'POST'], 'Extension/getmargin/(:any)', 'Extension::getMargin/$1');
$routes->match(['GET', 'POST'], 'Extension/download/(:any)', 'Extension::download/$1');
$routes->match(['GET', 'POST'], 'extension/download/(:any)', 'Extension::download/$1');
$routes->match(['GET', 'POST'], 'Extension/detailOffering', 'Extension::detailOffering');
$routes->match(['GET', 'POST'], 'extension/detailoffering', 'Extension::detailOffering');
$routes->match(['GET', 'POST'], 'extension/detailOffering', 'Extension::detailOffering');
$routes->match(['GET', 'POST'], 'Extension/detailoffering', 'Extension::detailOffering');
$routes->match(['GET', 'POST'], 'Extension/getRoles', 'Extension::getRoles');
$routes->match(['GET', 'POST'], 'extension/getroles', 'Extension::getRoles');
$routes->match(['GET', 'POST'], 'extension/getRoles', 'Extension::getRoles');
$routes->match(['GET', 'POST'], 'Extension/getroles', 'Extension::getRoles');
$routes->match(['GET', 'POST'], 'Extension/lookup_aco', 'Extension::lookup_aco');
$routes->match(['GET', 'POST'], 'extension/lookup_aco', 'Extension::lookup_aco');
$routes->match(['GET', 'POST'], 'Extension/grid_acos', 'Extension::grid_acos');
$routes->match(['GET', 'POST'], 'extension/grid_acos', 'Extension::grid_acos');
$routes->match(['GET', 'POST'], 'Extension/grid_acos1', 'Extension::grid_acos1');
$routes->match(['GET', 'POST'], 'extension/grid_acos1', 'Extension::grid_acos1');
$routes->match(['GET', 'POST'], 'Extension/set', 'Extension::set');
$routes->match(['GET', 'POST'], 'extension/set', 'Extension::set');
$routes->match(['GET', 'POST'], 'Extension/image/(:any)', 'Extension::image/$1');
$routes->match(['GET', 'POST'], 'extension/image/(:any)', 'Extension::image/$1');
$routes->match(['GET', 'POST'], 'Extension/printupdate', 'Extension::printupdate');
$routes->match(['GET', 'POST'], 'extension/printupdate', 'Extension::printupdate');
$routes->match(['GET', 'POST'], 'Extension/uploadWA/(:any)', 'Extension::uploadWA/$1');
$routes->match(['GET', 'POST'], 'extension/uploadwa/(:any)', 'Extension::uploadWA/$1');
$routes->match(['GET', 'POST'], 'extension/uploadWA/(:any)', 'Extension::uploadWA/$1');
$routes->match(['GET', 'POST'], 'Extension/uploadwa/(:any)', 'Extension::uploadWA/$1');
$routes->match(['GET', 'POST'], 'Extension/uploadBerkas/(:any)', 'Extension::uploadBerkas/$1');
$routes->match(['GET', 'POST'], 'extension/uploadberkas/(:any)', 'Extension::uploadBerkas/$1');
$routes->match(['GET', 'POST'], 'extension/uploadBerkas/(:any)', 'Extension::uploadBerkas/$1');
$routes->match(['GET', 'POST'], 'Extension/uploadberkas/(:any)', 'Extension::uploadBerkas/$1');
$routes->match(['GET', 'POST'], 'Extension/excelUser', 'Extension::excelUser');
$routes->match(['GET', 'POST'], 'extension/exceluser', 'Extension::excelUser');
$routes->match(['GET', 'POST'], 'extension/excelUser', 'Extension::excelUser');
$routes->match(['GET', 'POST'], 'Extension/exceluser', 'Extension::excelUser');

// Routes for App\Controllers\Grafikemklluar (Grafikemklluar)
$routes->match(['GET', 'POST'], 'Grafikemklluar', 'Grafikemklluar::index');
$routes->match(['GET', 'POST'], 'grafikemklluar', 'Grafikemklluar::index');
$routes->match(['GET', 'POST'], 'Grafikemklluar/index', 'Grafikemklluar::index');
$routes->match(['GET', 'POST'], 'grafikemklluar/index', 'Grafikemklluar::index');
$routes->match(['GET', 'POST'], 'Grafikemklluar/carisama/(:any)/(:any)', 'Grafikemklluar::carisama/$1/$2');
$routes->match(['GET', 'POST'], 'grafikemklluar/carisama/(:any)/(:any)', 'Grafikemklluar::carisama/$1/$2');

// Routes for App\Controllers\Grafiktradoluar (Grafiktradoluar)
$routes->match(['GET', 'POST'], 'Grafiktradoluar', 'Grafiktradoluar::index');
$routes->match(['GET', 'POST'], 'grafiktradoluar', 'Grafiktradoluar::index');
$routes->match(['GET', 'POST'], 'Grafiktradoluar/index', 'Grafiktradoluar::index');
$routes->match(['GET', 'POST'], 'grafiktradoluar/index', 'Grafiktradoluar::index');

// Routes for App\Controllers\Historytradoluar (Historytradoluar)
$routes->match(['GET', 'POST'], 'Historytradoluar', 'Historytradoluar::index');
$routes->match(['GET', 'POST'], 'historytradoluar', 'Historytradoluar::index');
$routes->match(['GET', 'POST'], 'Historytradoluar/index', 'Historytradoluar::index');
$routes->match(['GET', 'POST'], 'historytradoluar/index', 'Historytradoluar::index');
$routes->match(['GET', 'POST'], 'Historytradoluar/loadhistory', 'Historytradoluar::loadhistory');
$routes->match(['GET', 'POST'], 'historytradoluar/loadhistory', 'Historytradoluar::loadhistory');
$routes->match(['GET', 'POST'], 'Historytradoluar/listhistory', 'Historytradoluar::listhistory');
$routes->match(['GET', 'POST'], 'historytradoluar/listhistory', 'Historytradoluar::listhistory');
$routes->match(['GET', 'POST'], 'Historytradoluar/hakakses/(:any)', 'Historytradoluar::hakakses/$1');
$routes->match(['GET', 'POST'], 'historytradoluar/hakakses/(:any)', 'Historytradoluar::hakakses/$1');

// Routes for App\Controllers\Home (Home)
$routes->match(['GET', 'POST'], 'Home', 'Home::index');
$routes->match(['GET', 'POST'], 'home', 'Home::index');
$routes->match(['GET', 'POST'], 'Home/index', 'Home::index');
$routes->match(['GET', 'POST'], 'home/index', 'Home::index');
$routes->match(['GET', 'POST'], 'Home/debug', 'Home::debug');
$routes->match(['GET', 'POST'], 'home/debug', 'Home::debug');

// Routes for App\Controllers\Itmanagement (Itmanagement)
$routes->match(['GET', 'POST'], 'Itmanagement', 'Itmanagement::index');
$routes->match(['GET', 'POST'], 'itmanagement', 'Itmanagement::index');
$routes->match(['GET', 'POST'], 'Itmanagement/index', 'Itmanagement::index');
$routes->match(['GET', 'POST'], 'itmanagement/index', 'Itmanagement::index');
$routes->match(['GET', 'POST'], 'Itmanagement/hakakses/(:any)', 'Itmanagement::hakakses/$1');
$routes->match(['GET', 'POST'], 'itmanagement/hakakses/(:any)', 'Itmanagement::hakakses/$1');

// Routes for App\Controllers\Lapanalisajlhjob (Lapanalisajlhjob)
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob', 'Lapanalisajlhjob::index');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob', 'Lapanalisajlhjob::index');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/index', 'Lapanalisajlhjob::index');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/index', 'Lapanalisajlhjob::index');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/marketing/(:any)', 'Lapanalisajlhjob::marketing/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/marketing/(:any)', 'Lapanalisajlhjob::marketing/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/show', 'Lapanalisajlhjob::show');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/show', 'Lapanalisajlhjob::show');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/grid/(:any)', 'Lapanalisajlhjob::grid/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/grid/(:any)', 'Lapanalisajlhjob::grid/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/gridasuransi/(:any)', 'Lapanalisajlhjob::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/gridasuransi/(:any)', 'Lapanalisajlhjob::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/operation/(:any)', 'Lapanalisajlhjob::operation/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/operation/(:any)', 'Lapanalisajlhjob::operation/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/getlastupdatestnk/(:any)', 'Lapanalisajlhjob::getlastupdatestnk/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/getlastupdatestnk/(:any)', 'Lapanalisajlhjob::getlastupdatestnk/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/getlastupdateasuransi/(:any)', 'Lapanalisajlhjob::getlastupdateasuransi/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/getlastupdateasuransi/(:any)', 'Lapanalisajlhjob::getlastupdateasuransi/$1');
$routes->match(['GET', 'POST'], 'Lapanalisajlhjob/hakakses/(:any)', 'Lapanalisajlhjob::hakakses/$1');
$routes->match(['GET', 'POST'], 'lapanalisajlhjob/hakakses/(:any)', 'Lapanalisajlhjob::hakakses/$1');

// Routes for App\Controllers\Login (Login)
$routes->match(['GET', 'POST'], 'Login', 'Login::index');
$routes->match(['GET', 'POST'], 'login', 'Login::index');
$routes->match(['GET', 'POST'], 'Login/index', 'Login::index');
$routes->match(['GET', 'POST'], 'login/index', 'Login::index');
$routes->match(['GET', 'POST'], 'Login/proses', 'Login::proses');
$routes->match(['GET', 'POST'], 'login/proses', 'Login::proses');
$routes->match(['GET', 'POST'], 'Login/logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'login/logout', 'Login::logout');

// Routes for Logout
$routes->get('logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'Logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'Logout/index', 'Login::logout');
$routes->match(['GET', 'POST'], 'logout/index', 'Login::logout');

// Routes for App\Controllers\Menu (Menu)
$routes->match(['GET', 'POST'], 'Menu', 'Menu::index');
$routes->match(['GET', 'POST'], 'menu', 'Menu::index');
$routes->match(['GET', 'POST'], 'Menu/index', 'Menu::index');
$routes->match(['GET', 'POST'], 'menu/index', 'Menu::index');
$routes->match(['GET', 'POST'], 'Menu/grid', 'Menu::grid');
$routes->match(['GET', 'POST'], 'menu/grid', 'Menu::grid');
$routes->match(['GET', 'POST'], 'Menu/crud', 'Menu::crud');
$routes->match(['GET', 'POST'], 'menu/crud', 'Menu::crud');
$routes->match(['GET', 'POST'], 'Menu/getById/(:any)', 'Menu::getById/$1');
$routes->match(['GET', 'POST'], 'menu/getById/(:any)', 'Menu::getById/$1');
$routes->match(['GET', 'POST'], 'Menu/lookupAco', 'Menu::lookupAco');
$routes->match(['GET', 'POST'], 'menu/lookupAco', 'Menu::lookupAco');
$routes->match(['GET', 'POST'], 'Menu/reseq', 'Menu::reseq');
$routes->match(['GET', 'POST'], 'menu/reseq', 'Menu::reseq');

// Routes for App\Controllers\Myunzip (Myunzip)
$routes->match(['GET', 'POST'], 'Myunzip', 'Myunzip::index');
$routes->match(['GET', 'POST'], 'myunzip', 'Myunzip::index');
$routes->match(['GET', 'POST'], 'Myunzip/index', 'Myunzip::index');
$routes->match(['GET', 'POST'], 'myunzip/index', 'Myunzip::index');

// Routes for App\Controllers\NewShipper (NewShipper)
$routes->match(['GET', 'POST'], 'NewShipper', 'NewShipper::index');
$routes->match(['GET', 'POST'], 'newshipper', 'NewShipper::index');
$routes->match(['GET', 'POST'], 'NewShipper/index', 'NewShipper::index');
$routes->match(['GET', 'POST'], 'newshipper/index', 'NewShipper::index');
$routes->match(['GET', 'POST'], 'NewShipper/export/(:any)', 'NewShipper::export/$1');
$routes->match(['GET', 'POST'], 'newshipper/export/(:any)', 'NewShipper::export/$1');
$routes->match(['GET', 'POST'], 'NewShipper/medan', 'NewShipper::medan');
$routes->match(['GET', 'POST'], 'newshipper/medan', 'NewShipper::medan');

// Routes for App\Controllers\NewShipperCabang (NewShipperCabang)
$routes->match(['GET', 'POST'], 'NewShipperCabang/mks', 'NewShipperCabang::mks');
$routes->match(['GET', 'POST'], 'newshippercabang/mks', 'NewShipperCabang::mks');
$routes->match(['GET', 'POST'], 'NewShipperCabang/sby', 'NewShipperCabang::sby');
$routes->match(['GET', 'POST'], 'newshippercabang/sby', 'NewShipperCabang::sby');
$routes->match(['GET', 'POST'], 'NewShipperCabang/jkt', 'NewShipperCabang::jkt');
$routes->match(['GET', 'POST'], 'newshippercabang/jkt', 'NewShipperCabang::jkt');
$routes->match(['GET', 'POST'], 'NewShipperCabang/mdn', 'NewShipperCabang::mdn');
$routes->match(['GET', 'POST'], 'newshippercabang/mdn', 'NewShipperCabang::mdn');

// Routes for App\Controllers\Omset (Omset)
$routes->match(['GET', 'POST'], 'Omset', 'Omset::index');
$routes->match(['GET', 'POST'], 'omset', 'Omset::index');
$routes->match(['GET', 'POST'], 'Omset/index', 'Omset::index');
$routes->match(['GET', 'POST'], 'omset/index', 'Omset::index');
$routes->match(['GET', 'POST'], 'Omset/grid', 'Omset::grid');
$routes->match(['GET', 'POST'], 'omset/grid', 'Omset::grid');

// Routes for App\Controllers\Omsetmarketingjkt (Omsetmarketingjkt)
$routes->match(['GET', 'POST'], 'Omsetmarketingjkt', 'Omsetmarketingjkt::index');
$routes->match(['GET', 'POST'], 'omsetmarketingjkt', 'Omsetmarketingjkt::index');
$routes->match(['GET', 'POST'], 'Omsetmarketingjkt/index', 'Omsetmarketingjkt::index');
$routes->match(['GET', 'POST'], 'omsetmarketingjkt/index', 'Omsetmarketingjkt::index');

// Routes for App\Controllers\Omsetmarketingmdn (Omsetmarketingmdn)
$routes->match(['GET', 'POST'], 'Omsetmarketingmdn', 'Omsetmarketingmdn::index');
$routes->match(['GET', 'POST'], 'omsetmarketingmdn', 'Omsetmarketingmdn::index');
$routes->match(['GET', 'POST'], 'Omsetmarketingmdn/index', 'Omsetmarketingmdn::index');
$routes->match(['GET', 'POST'], 'omsetmarketingmdn/index', 'Omsetmarketingmdn::index');

// Routes for App\Controllers\Omsetmarketingmks (Omsetmarketingmks)
$routes->match(['GET', 'POST'], 'Omsetmarketingmks', 'Omsetmarketingmks::index');
$routes->match(['GET', 'POST'], 'omsetmarketingmks', 'Omsetmarketingmks::index');
$routes->match(['GET', 'POST'], 'Omsetmarketingmks/index', 'Omsetmarketingmks::index');
$routes->match(['GET', 'POST'], 'omsetmarketingmks/index', 'Omsetmarketingmks::index');

// Routes for App\Controllers\Omsetmarketingsby (Omsetmarketingsby)
$routes->match(['GET', 'POST'], 'Omsetmarketingsby', 'Omsetmarketingsby::index');
$routes->match(['GET', 'POST'], 'omsetmarketingsby', 'Omsetmarketingsby::index');
$routes->match(['GET', 'POST'], 'Omsetmarketingsby/index', 'Omsetmarketingsby::index');
$routes->match(['GET', 'POST'], 'omsetmarketingsby/index', 'Omsetmarketingsby::index');

// Routes for App\Controllers\Omsetmarketingsmg (Omsetmarketingsmg)
$routes->match(['GET', 'POST'], 'Omsetmarketingsmg', 'Omsetmarketingsmg::index');
$routes->match(['GET', 'POST'], 'omsetmarketingsmg', 'Omsetmarketingsmg::index');
$routes->match(['GET', 'POST'], 'Omsetmarketingsmg/index', 'Omsetmarketingsmg::index');
$routes->match(['GET', 'POST'], 'omsetmarketingsmg/index', 'Omsetmarketingsmg::index');

// Routes for App\Controllers\Omsetrekapmarketingjkt (Omsetrekapmarketingjkt)
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt', 'Omsetrekapmarketingjkt::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt', 'Omsetrekapmarketingjkt::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt/index', 'Omsetrekapmarketingjkt::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt/index', 'Omsetrekapmarketingjkt::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt/combotradoluar', 'Omsetrekapmarketingjkt::combotradoluar');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt/combotradoluar', 'Omsetrekapmarketingjkt::combotradoluar');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt/combotahunJkt', 'Omsetrekapmarketingjkt::combotahunJkt');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt/combotahunjkt', 'Omsetrekapmarketingjkt::combotahunJkt');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt/combotahunJkt', 'Omsetrekapmarketingjkt::combotahunJkt');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt/combotahunjkt', 'Omsetrekapmarketingjkt::combotahunJkt');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingjkt/combomarketing', 'Omsetrekapmarketingjkt::combomarketing');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingjkt/combomarketing', 'Omsetrekapmarketingjkt::combomarketing');

// Routes for App\Controllers\Omsetrekapmarketingmdn (Omsetrekapmarketingmdn)
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn', 'Omsetrekapmarketingmdn::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn', 'Omsetrekapmarketingmdn::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn/index', 'Omsetrekapmarketingmdn::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn/index', 'Omsetrekapmarketingmdn::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn/combotradoluar', 'Omsetrekapmarketingmdn::combotradoluar');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn/combotradoluar', 'Omsetrekapmarketingmdn::combotradoluar');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn/combotahunMdn', 'Omsetrekapmarketingmdn::combotahunMdn');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn/combotahunmdn', 'Omsetrekapmarketingmdn::combotahunMdn');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn/combotahunMdn', 'Omsetrekapmarketingmdn::combotahunMdn');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn/combotahunmdn', 'Omsetrekapmarketingmdn::combotahunMdn');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmdn/combomarketing', 'Omsetrekapmarketingmdn::combomarketing');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmdn/combomarketing', 'Omsetrekapmarketingmdn::combomarketing');

// Routes for App\Controllers\Omsetrekapmarketingmks (Omsetrekapmarketingmks)
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks', 'Omsetrekapmarketingmks::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks', 'Omsetrekapmarketingmks::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks/index', 'Omsetrekapmarketingmks::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks/index', 'Omsetrekapmarketingmks::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks/combotradoluar', 'Omsetrekapmarketingmks::combotradoluar');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks/combotradoluar', 'Omsetrekapmarketingmks::combotradoluar');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks/combotahunMks', 'Omsetrekapmarketingmks::combotahunMks');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks/combotahunmks', 'Omsetrekapmarketingmks::combotahunMks');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks/combotahunMks', 'Omsetrekapmarketingmks::combotahunMks');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks/combotahunmks', 'Omsetrekapmarketingmks::combotahunMks');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingmks/combomarketing', 'Omsetrekapmarketingmks::combomarketing');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingmks/combomarketing', 'Omsetrekapmarketingmks::combomarketing');

// Routes for App\Controllers\Omsetrekapmarketingsby (Omsetrekapmarketingsby)
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby', 'Omsetrekapmarketingsby::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby', 'Omsetrekapmarketingsby::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby/index', 'Omsetrekapmarketingsby::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby/index', 'Omsetrekapmarketingsby::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby/combotradoluar', 'Omsetrekapmarketingsby::combotradoluar');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby/combotradoluar', 'Omsetrekapmarketingsby::combotradoluar');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby/combotahunSby', 'Omsetrekapmarketingsby::combotahunSby');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby/combotahunsby', 'Omsetrekapmarketingsby::combotahunSby');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby/combotahunSby', 'Omsetrekapmarketingsby::combotahunSby');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby/combotahunsby', 'Omsetrekapmarketingsby::combotahunSby');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsby/combomarketing', 'Omsetrekapmarketingsby::combomarketing');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsby/combomarketing', 'Omsetrekapmarketingsby::combomarketing');

// Routes for App\Controllers\Omsetrekapmarketingsmg (Omsetrekapmarketingsmg)
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg', 'Omsetrekapmarketingsmg::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg', 'Omsetrekapmarketingsmg::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg/index', 'Omsetrekapmarketingsmg::index');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg/index', 'Omsetrekapmarketingsmg::index');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg/combotradoluar', 'Omsetrekapmarketingsmg::combotradoluar');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg/combotradoluar', 'Omsetrekapmarketingsmg::combotradoluar');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg/combotahunSmg', 'Omsetrekapmarketingsmg::combotahunSmg');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg/combotahunsmg', 'Omsetrekapmarketingsmg::combotahunSmg');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg/combotahunSmg', 'Omsetrekapmarketingsmg::combotahunSmg');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg/combotahunsmg', 'Omsetrekapmarketingsmg::combotahunSmg');
$routes->match(['GET', 'POST'], 'Omsetrekapmarketingsmg/combomarketing', 'Omsetrekapmarketingsmg::combomarketing');
$routes->match(['GET', 'POST'], 'omsetrekapmarketingsmg/combomarketing', 'Omsetrekapmarketingsmg::combomarketing');

// Routes for App\Controllers\Orderan (Orderan)
$routes->match(['GET', 'POST'], 'Orderan', 'Orderan::index');
$routes->match(['GET', 'POST'], 'orderan', 'Orderan::index');
$routes->match(['GET', 'POST'], 'Orderan/index', 'Orderan::index');
$routes->match(['GET', 'POST'], 'orderan/index', 'Orderan::index');

// Routes for App\Controllers\Overtopemkl (Overtopemkl)
$routes->match(['GET', 'POST'], 'Overtopemkl', 'Overtopemkl::index');
$routes->match(['GET', 'POST'], 'overtopemkl', 'Overtopemkl::index');
$routes->match(['GET', 'POST'], 'Overtopemkl/index', 'Overtopemkl::index');
$routes->match(['GET', 'POST'], 'overtopemkl/index', 'Overtopemkl::index');
$routes->match(['GET', 'POST'], 'Overtopemkl/grid/(:any)', 'Overtopemkl::grid/$1');
$routes->match(['GET', 'POST'], 'overtopemkl/grid/(:any)', 'Overtopemkl::grid/$1');
$routes->match(['GET', 'POST'], 'Overtopemkl/getlastupdate/(:any)', 'Overtopemkl::getlastupdate/$1');
$routes->match(['GET', 'POST'], 'overtopemkl/getlastupdate/(:any)', 'Overtopemkl::getlastupdate/$1');

// Routes for App\Controllers\Overtopemklmarketing (Overtopemklmarketing)
$routes->match(['GET', 'POST'], 'Overtopemklmarketing', 'Overtopemklmarketing::index');
$routes->match(['GET', 'POST'], 'overtopemklmarketing', 'Overtopemklmarketing::index');
$routes->match(['GET', 'POST'], 'Overtopemklmarketing/index', 'Overtopemklmarketing::index');
$routes->match(['GET', 'POST'], 'overtopemklmarketing/index', 'Overtopemklmarketing::index');
$routes->match(['GET', 'POST'], 'Overtopemklmarketing/grid/(:any)', 'Overtopemklmarketing::grid/$1');
$routes->match(['GET', 'POST'], 'overtopemklmarketing/grid/(:any)', 'Overtopemklmarketing::grid/$1');
$routes->match(['GET', 'POST'], 'Overtopemklmarketing/getlastupdate/(:any)', 'Overtopemklmarketing::getlastupdate/$1');
$routes->match(['GET', 'POST'], 'overtopemklmarketing/getlastupdate/(:any)', 'Overtopemklmarketing::getlastupdate/$1');

// Routes for App\Controllers\Overtopemklrealtime (Overtopemklrealtime)
$routes->match(['GET', 'POST'], 'Overtopemklrealtime', 'Overtopemklrealtime::index');
$routes->match(['GET', 'POST'], 'overtopemklrealtime', 'Overtopemklrealtime::index');
$routes->match(['GET', 'POST'], 'Overtopemklrealtime/index', 'Overtopemklrealtime::index');
$routes->match(['GET', 'POST'], 'overtopemklrealtime/index', 'Overtopemklrealtime::index');
$routes->match(['GET', 'POST'], 'Overtopemklrealtime/grid/(:any)', 'Overtopemklrealtime::grid/$1');
$routes->match(['GET', 'POST'], 'overtopemklrealtime/grid/(:any)', 'Overtopemklrealtime::grid/$1');
$routes->match(['GET', 'POST'], 'Overtopemklrealtime/getlastupdate/(:any)', 'Overtopemklrealtime::getlastupdate/$1');
$routes->match(['GET', 'POST'], 'overtopemklrealtime/getlastupdate/(:any)', 'Overtopemklrealtime::getlastupdate/$1');
$routes->match(['GET', 'POST'], 'Overtopemklrealtime/hakakses/(:any)', 'Overtopemklrealtime::hakakses/$1');
$routes->match(['GET', 'POST'], 'overtopemklrealtime/hakakses/(:any)', 'Overtopemklrealtime::hakakses/$1');

// Routes for App\Controllers\Parameter (Parameter)
$routes->match(['GET', 'POST'], 'Parameter', 'Parameter::index');
$routes->match(['GET', 'POST'], 'parameter', 'Parameter::index');
$routes->match(['GET', 'POST'], 'Parameter/index', 'Parameter::index');
$routes->match(['GET', 'POST'], 'parameter/index', 'Parameter::index');
$routes->match(['GET', 'POST'], 'Parameter/grid', 'Parameter::grid');
$routes->match(['GET', 'POST'], 'parameter/grid', 'Parameter::grid');
$routes->match(['GET', 'POST'], 'Parameter/operation/(:any)', 'Parameter::operation/$1');
$routes->match(['GET', 'POST'], 'parameter/operation/(:any)', 'Parameter::operation/$1');
$routes->match(['GET', 'POST'], 'Parameter/view/(:any)', 'Parameter::view/$1');
$routes->match(['GET', 'POST'], 'parameter/view/(:any)', 'Parameter::view/$1');
$routes->match(['GET', 'POST'], 'Parameter/add', 'Parameter::add');
$routes->match(['GET', 'POST'], 'parameter/add', 'Parameter::add');
$routes->match(['GET', 'POST'], 'Parameter/edit/(:any)', 'Parameter::edit/$1');
$routes->match(['GET', 'POST'], 'parameter/edit/(:any)', 'Parameter::edit/$1');
$routes->match(['GET', 'POST'], 'Parameter/delete/(:any)', 'Parameter::delete/$1');
$routes->match(['GET', 'POST'], 'parameter/delete/(:any)', 'Parameter::delete/$1');
$routes->match(['GET', 'POST'], 'Parameter/export', 'Parameter::export');
$routes->match(['GET', 'POST'], 'parameter/export', 'Parameter::export');

// Routes for App\Controllers\Piutangemkl (Piutangemkl)
$routes->match(['get', 'post'], 'Piutangemkl', 'Piutangemkl::index');
$routes->match(['get', 'post'], 'piutangemkl', 'Piutangemkl::index');
$routes->match(['get', 'post'], 'Piutangemkl/index', 'Piutangemkl::index');
$routes->match(['get', 'post'], 'piutangemkl/index', 'Piutangemkl::index');
$routes->match(['get', 'post'], 'Piutangemkl/grid', 'Piutangemkl::grid');
$routes->match(['get', 'post'], 'piutangemkl/grid', 'Piutangemkl::grid');
$routes->match(['get', 'post'], 'Piutangemkl/grid/(:any)', 'Piutangemkl::grid/$1');
$routes->match(['get', 'post'], 'piutangemkl/grid/(:any)', 'Piutangemkl::grid/$1');
$routes->match(['GET', 'POST'], 'Piutangemkl/operation/(:any)', 'Piutangemkl::operation/$1');
$routes->match(['GET', 'POST'], 'piutangemkl/operation/(:any)', 'Piutangemkl::operation/$1');
$routes->match(['GET', 'POST'], 'Piutangemkl/getlastupdate/(:any)', 'Piutangemkl::getlastupdate/$1');
$routes->match(['GET', 'POST'], 'piutangemkl/getlastupdate/(:any)', 'Piutangemkl::getlastupdate/$1');

// Routes for App\Controllers\Profil (Profil)
$routes->match(['GET', 'POST'], 'Profil', 'Profil::index');
$routes->match(['GET', 'POST'], 'profil', 'Profil::index');
$routes->match(['GET', 'POST'], 'Profil/index', 'Profil::index');
$routes->match(['GET', 'POST'], 'profil/index', 'Profil::index');
$routes->match(['GET', 'POST'], 'Profil/editprofil', 'Profil::editprofil');
$routes->match(['GET', 'POST'], 'profil/editprofil', 'Profil::editprofil');
$routes->match(['GET', 'POST'], 'Profil/editpassword', 'Profil::editpassword');
$routes->match(['GET', 'POST'], 'profil/editpassword', 'Profil::editpassword');

// Routes for App\Controllers\RekapMarketing (RekapMarketing)
$routes->match(['GET', 'POST'], 'RekapMarketing/cabang', 'RekapMarketing::cabang');
$routes->match(['GET', 'POST'], 'rekapmarketing/cabang', 'RekapMarketing::cabang');
$routes->match(['GET', 'POST'], 'RekapMarketing', 'RekapMarketing::index');
$routes->match(['GET', 'POST'], 'rekapmarketing', 'RekapMarketing::index');
$routes->match(['GET', 'POST'], 'RekapMarketing/index', 'RekapMarketing::index');
$routes->match(['GET', 'POST'], 'rekapmarketing/index', 'RekapMarketing::index');
$routes->match(['GET', 'POST'], 'RekapMarketing/detail', 'RekapMarketing::detail');
$routes->match(['GET', 'POST'], 'rekapmarketing/detail', 'RekapMarketing::detail');
$routes->match(['GET', 'POST'], 'RekapMarketing/crud/(:any)', 'RekapMarketing::crud/$1');
$routes->match(['GET', 'POST'], 'rekapmarketing/crud/(:any)', 'RekapMarketing::crud/$1');
$routes->match(['GET', 'POST'], 'RekapMarketing/dataMARKETING', 'RekapMarketing::dataMARKETING');
$routes->match(['GET', 'POST'], 'rekapmarketing/datamarketing', 'RekapMarketing::dataMARKETING');
$routes->match(['GET', 'POST'], 'rekapmarketing/dataMARKETING', 'RekapMarketing::dataMARKETING');
$routes->match(['GET', 'POST'], 'RekapMarketing/datamarketing', 'RekapMarketing::dataMARKETING');
$routes->match(['GET', 'POST'], 'RekapMarketing/dataCABANG', 'RekapMarketing::dataCABANG');
$routes->match(['GET', 'POST'], 'rekapmarketing/datacabang', 'RekapMarketing::dataCABANG');
$routes->match(['GET', 'POST'], 'rekapmarketing/dataCABANG', 'RekapMarketing::dataCABANG');
$routes->match(['GET', 'POST'], 'RekapMarketing/datacabang', 'RekapMarketing::dataCABANG');
$routes->match(['GET', 'POST'], 'RekapMarketing/index2', 'RekapMarketing::index2');
$routes->match(['GET', 'POST'], 'rekapmarketing/index2', 'RekapMarketing::index2');

// Routes for App\Controllers\RekapMarketingDetail (RekapMarketingDetail)
$routes->match(['GET', 'POST'], 'RekapMarketingDetail', 'RekapMarketingDetail::index');
$routes->match(['GET', 'POST'], 'rekapmarketingdetail', 'RekapMarketingDetail::index');
$routes->match(['GET', 'POST'], 'RekapMarketingDetail/index', 'RekapMarketingDetail::index');
$routes->match(['GET', 'POST'], 'rekapmarketingdetail/index', 'RekapMarketingDetail::index');
$routes->match(['GET', 'POST'], 'RekapMarketingDetail/dataCABANG', 'RekapMarketingDetail::dataCABANG');
$routes->match(['GET', 'POST'], 'rekapmarketingdetail/datacabang', 'RekapMarketingDetail::dataCABANG');
$routes->match(['GET', 'POST'], 'rekapmarketingdetail/dataCABANG', 'RekapMarketingDetail::dataCABANG');
$routes->match(['GET', 'POST'], 'RekapMarketingDetail/datacabang', 'RekapMarketingDetail::dataCABANG');

// Routes for App\Controllers\RekapOrderan (RekapOrderan)
$routes->match(['GET', 'POST'], 'RekapOrderan', 'RekapOrderan::index');
$routes->match(['GET', 'POST'], 'rekaporderan', 'RekapOrderan::index');
$routes->match(['GET', 'POST'], 'RekapOrderan/index', 'RekapOrderan::index');
$routes->match(['GET', 'POST'], 'rekaporderan/index', 'RekapOrderan::index');
$routes->match(['GET', 'POST'], 'RekapOrderan/dataCABANG', 'RekapOrderan::dataCABANG');
$routes->match(['GET', 'POST'], 'rekaporderan/datacabang', 'RekapOrderan::dataCABANG');
$routes->match(['GET', 'POST'], 'rekaporderan/dataCABANG', 'RekapOrderan::dataCABANG');
$routes->match(['GET', 'POST'], 'RekapOrderan/datacabang', 'RekapOrderan::dataCABANG');

// Routes for App\Controllers\Ritasitrado (Ritasitrado)
$routes->match(['GET', 'POST'], 'Ritasitrado/pesanmdn', 'Ritasitrado::pesanmdn');
$routes->match(['GET', 'POST'], 'ritasitrado/pesanmdn', 'Ritasitrado::pesanmdn');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesansby', 'Ritasitrado::pesansby');
$routes->match(['GET', 'POST'], 'ritasitrado/pesansby', 'Ritasitrado::pesansby');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesanjkt', 'Ritasitrado::pesanjkt');
$routes->match(['GET', 'POST'], 'ritasitrado/pesanjkt', 'Ritasitrado::pesanjkt');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesantnl', 'Ritasitrado::pesantnl');
$routes->match(['GET', 'POST'], 'ritasitrado/pesantnl', 'Ritasitrado::pesantnl');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesanmks', 'Ritasitrado::pesanmks');
$routes->match(['GET', 'POST'], 'ritasitrado/pesanmks', 'Ritasitrado::pesanmks');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesanpku', 'Ritasitrado::pesanpku');
$routes->match(['GET', 'POST'], 'ritasitrado/pesanpku', 'Ritasitrado::pesanpku');
$routes->match(['GET', 'POST'], 'Ritasitrado/pesanbtg', 'Ritasitrado::pesanbtg');
$routes->match(['GET', 'POST'], 'ritasitrado/pesanbtg', 'Ritasitrado::pesanbtg');
$routes->match(['GET', 'POST'], 'Ritasitrado', 'Ritasitrado::index');
$routes->match(['GET', 'POST'], 'ritasitrado', 'Ritasitrado::index');
$routes->match(['GET', 'POST'], 'Ritasitrado/index', 'Ritasitrado::index');
$routes->match(['GET', 'POST'], 'ritasitrado/index', 'Ritasitrado::index');

// Routes for App\Controllers\Roles (Roles)
$routes->match(['GET', 'POST'], 'Roles', 'Roles::index');
$routes->match(['GET', 'POST'], 'roles', 'Roles::index');
$routes->match(['GET', 'POST'], 'Roles/index', 'Roles::index');
$routes->match(['GET', 'POST'], 'roles/index', 'Roles::index');
$routes->match(['GET', 'POST'], 'Roles/getById/(:any)', 'Roles::getById/$1');
$routes->match(['GET', 'POST'], 'roles/getById/(:any)', 'Roles::getById/$1');
$routes->match(['GET', 'POST'], 'Roles/grid', 'Roles::grid');
$routes->match(['GET', 'POST'], 'roles/grid', 'Roles::grid');
$routes->match(['GET', 'POST'], 'Roles/crud', 'Roles::crud');
$routes->match(['GET', 'POST'], 'roles/crud', 'Roles::crud');

// Routes for App\Controllers\Sop (Sop)
$routes->match(['GET', 'POST'], 'Sop', 'Sop::index');
$routes->match(['GET', 'POST'], 'sop', 'Sop::index');
$routes->match(['GET', 'POST'], 'Sop/index', 'Sop::index');
$routes->match(['GET', 'POST'], 'sop/index', 'Sop::index');

// Routes for App\Controllers\Statuskendaraan (Statuskendaraan)
$routes->match(['GET', 'POST'], 'Statuskendaraan', 'Statuskendaraan::index');
$routes->match(['GET', 'POST'], 'statuskendaraan', 'Statuskendaraan::index');
$routes->match(['GET', 'POST'], 'Statuskendaraan/index', 'Statuskendaraan::index');
$routes->match(['GET', 'POST'], 'statuskendaraan/index', 'Statuskendaraan::index');
$routes->match(['GET', 'POST'], 'Statuskendaraan/crud/(:any)', 'Statuskendaraan::crud/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/crud/(:any)', 'Statuskendaraan::crud/$1');
$routes->match(['GET', 'POST'], 'Statuskendaraan/grid/(:any)', 'Statuskendaraan::grid/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/grid/(:any)', 'Statuskendaraan::grid/$1');
$routes->match(['GET', 'POST'], 'Statuskendaraan/gridasuransi/(:any)', 'Statuskendaraan::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/gridasuransi/(:any)', 'Statuskendaraan::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'Statuskendaraan/operation/(:any)', 'Statuskendaraan::operation/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/operation/(:any)', 'Statuskendaraan::operation/$1');
$routes->match(['GET', 'POST'], 'Statuskendaraan/getlastupdatestnk/(:any)', 'Statuskendaraan::getlastupdatestnk/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/getlastupdatestnk/(:any)', 'Statuskendaraan::getlastupdatestnk/$1');
$routes->match(['GET', 'POST'], 'Statuskendaraan/getlastupdateasuransi/(:any)', 'Statuskendaraan::getlastupdateasuransi/$1');
$routes->match(['GET', 'POST'], 'statuskendaraan/getlastupdateasuransi/(:any)', 'Statuskendaraan::getlastupdateasuransi/$1');

// Routes for App\Controllers\Statuskendaraantambah (Statuskendaraantambah)
$routes->match(['GET', 'POST'], 'Statuskendaraantambah', 'Statuskendaraantambah::index');
$routes->match(['GET', 'POST'], 'statuskendaraantambah', 'Statuskendaraantambah::index');
$routes->match(['GET', 'POST'], 'Statuskendaraantambah/index', 'Statuskendaraantambah::index');
$routes->match(['GET', 'POST'], 'statuskendaraantambah/index', 'Statuskendaraantambah::index');
$routes->match(['GET', 'POST'], 'Statuskendaraantambah/grid/(:any)', 'Statuskendaraantambah::grid/$1');
$routes->match(['GET', 'POST'], 'statuskendaraantambah/grid/(:any)', 'Statuskendaraantambah::grid/$1');

// Routes for App\Controllers\Stnk (Stnk)
$routes->match(['GET', 'POST'], 'Stnk', 'Stnk::index');
$routes->match(['GET', 'POST'], 'stnk', 'Stnk::index');
$routes->match(['GET', 'POST'], 'Stnk/index', 'Stnk::index');
$routes->match(['GET', 'POST'], 'stnk/index', 'Stnk::index');
$routes->match(['GET', 'POST'], 'Stnk/grid/(:any)', 'Stnk::grid/$1');
$routes->match(['GET', 'POST'], 'stnk/grid/(:any)', 'Stnk::grid/$1');
$routes->match(['GET', 'POST'], 'Stnk/gridasuransi/(:any)', 'Stnk::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'stnk/gridasuransi/(:any)', 'Stnk::gridasuransi/$1');
$routes->match(['GET', 'POST'], 'Stnk/operation/(:any)', 'Stnk::operation/$1');
$routes->match(['GET', 'POST'], 'stnk/operation/(:any)', 'Stnk::operation/$1');

// Routes for App\Controllers\Supirpercabang (Supirpercabang)
$routes->match(['GET', 'POST'], 'Supirpercabang', 'Supirpercabang::index');
$routes->match(['GET', 'POST'], 'supirpercabang', 'Supirpercabang::index');
$routes->match(['GET', 'POST'], 'Supirpercabang/index', 'Supirpercabang::index');
$routes->match(['GET', 'POST'], 'supirpercabang/index', 'Supirpercabang::index');
$routes->match(['GET', 'POST'], 'Supirpercabang/grid/(:any)', 'Supirpercabang::grid/$1');
$routes->match(['GET', 'POST'], 'supirpercabang/grid/(:any)', 'Supirpercabang::grid/$1');
$routes->match(['GET', 'POST'], 'Supirpercabang/crud', 'Supirpercabang::crud');
$routes->match(['GET', 'POST'], 'supirpercabang/crud', 'Supirpercabang::crud');

// Routes for App\Controllers\Tracing (Tracing)
$routes->match(['GET', 'POST'], 'Tracing', 'Tracing::index');
$routes->match(['GET', 'POST'], 'tracing', 'Tracing::index');
$routes->match(['GET', 'POST'], 'Tracing/index', 'Tracing::index');
$routes->match(['GET', 'POST'], 'tracing/index', 'Tracing::index');
$routes->match(['GET', 'POST'], 'Tracing/grid', 'Tracing::grid');
$routes->match(['GET', 'POST'], 'tracing/grid', 'Tracing::grid');
$routes->match(['GET', 'POST'], 'Tracing/operation/(:any)', 'Tracing::operation/$1');
$routes->match(['GET', 'POST'], 'tracing/operation/(:any)', 'Tracing::operation/$1');
$routes->match(['GET', 'POST'], 'Tracing/excel', 'Tracing::excel');
$routes->match(['GET', 'POST'], 'tracing/excel', 'Tracing::excel');
$routes->match(['GET', 'POST'], 'Tracing/lappemakaiantracingtahunan', 'Tracing::lappemakaiantracingtahunan');
$routes->match(['GET', 'POST'], 'tracing/lappemakaiantracingtahunan', 'Tracing::lappemakaiantracingtahunan');

// Routes for App\Controllers\Truckingemkllain (Truckingemkllain)
$routes->match(['GET', 'POST'], 'Truckingemkllain', 'Truckingemkllain::index');
$routes->match(['GET', 'POST'], 'truckingemkllain', 'Truckingemkllain::index');
$routes->match(['GET', 'POST'], 'Truckingemkllain/index', 'Truckingemkllain::index');
$routes->match(['GET', 'POST'], 'truckingemkllain/index', 'Truckingemkllain::index');
$routes->match(['GET', 'POST'], 'Truckingemkllain/crud/(:any)', 'Truckingemkllain::crud/$1');
$routes->match(['GET', 'POST'], 'truckingemkllain/crud/(:any)', 'Truckingemkllain::crud/$1');
$routes->match(['GET', 'POST'], 'Truckingemkllain/hakakses/(:any)', 'Truckingemkllain::hakakses/$1');
$routes->match(['GET', 'POST'], 'truckingemkllain/hakakses/(:any)', 'Truckingemkllain::hakakses/$1');

// Routes for App\Controllers\Truckingemkllaindetail (Truckingemkllaindetail)
$routes->match(['GET', 'POST'], 'Truckingemkllaindetail/bubbleSort/(:any)', 'Truckingemkllaindetail::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'truckingemkllaindetail/bubblesort/(:any)', 'Truckingemkllaindetail::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'truckingemkllaindetail/bubbleSort/(:any)', 'Truckingemkllaindetail::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetail/bubblesort/(:any)', 'Truckingemkllaindetail::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetail', 'Truckingemkllaindetail::index');
$routes->match(['GET', 'POST'], 'truckingemkllaindetail', 'Truckingemkllaindetail::index');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetail/index', 'Truckingemkllaindetail::index');
$routes->match(['GET', 'POST'], 'truckingemkllaindetail/index', 'Truckingemkllaindetail::index');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetail/hakakses/(:any)', 'Truckingemkllaindetail::hakakses/$1');
$routes->match(['GET', 'POST'], 'truckingemkllaindetail/hakakses/(:any)', 'Truckingemkllaindetail::hakakses/$1');

// Routes for App\Controllers\Truckingemkllaindetailexp (Truckingemkllaindetailexp)
$routes->match(['GET', 'POST'], 'Truckingemkllaindetailexp/bubbleSort/(:any)', 'Truckingemkllaindetailexp::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'truckingemkllaindetailexp/bubblesort/(:any)', 'Truckingemkllaindetailexp::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'truckingemkllaindetailexp/bubbleSort/(:any)', 'Truckingemkllaindetailexp::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetailexp/bubblesort/(:any)', 'Truckingemkllaindetailexp::bubbleSort/$1');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetailexp', 'Truckingemkllaindetailexp::index');
$routes->match(['GET', 'POST'], 'truckingemkllaindetailexp', 'Truckingemkllaindetailexp::index');
$routes->match(['GET', 'POST'], 'Truckingemkllaindetailexp/index', 'Truckingemkllaindetailexp::index');
$routes->match(['GET', 'POST'], 'truckingemkllaindetailexp/index', 'Truckingemkllaindetailexp::index');

// Routes for App\Controllers\Truckinghargatradoluarlebihmahal (Truckinghargatradoluarlebihmahal)
$routes->match(['GET', 'POST'], 'Truckinghargatradoluarlebihmahal', 'Truckinghargatradoluarlebihmahal::index');
$routes->match(['GET', 'POST'], 'truckinghargatradoluarlebihmahal', 'Truckinghargatradoluarlebihmahal::index');
$routes->match(['GET', 'POST'], 'Truckinghargatradoluarlebihmahal/index', 'Truckinghargatradoluarlebihmahal::index');
$routes->match(['GET', 'POST'], 'truckinghargatradoluarlebihmahal/index', 'Truckinghargatradoluarlebihmahal::index');

// Routes for App\Controllers\Truckingtradodalam (Truckingtradodalam)
$routes->match(['GET', 'POST'], 'Truckingtradodalam', 'Truckingtradodalam::index');
$routes->match(['GET', 'POST'], 'truckingtradodalam', 'Truckingtradodalam::index');
$routes->match(['GET', 'POST'], 'Truckingtradodalam/index', 'Truckingtradodalam::index');
$routes->match(['GET', 'POST'], 'truckingtradodalam/index', 'Truckingtradodalam::index');
$routes->match(['GET', 'POST'], 'Truckingtradodalam/form/(:any)', 'Truckingtradodalam::form/$1');
$routes->match(['GET', 'POST'], 'truckingtradodalam/form/(:any)', 'Truckingtradodalam::form/$1');
$routes->match(['GET', 'POST'], 'Truckingtradodalam/hakakses/(:any)', 'Truckingtradodalam::hakakses/$1');
$routes->match(['GET', 'POST'], 'truckingtradodalam/hakakses/(:any)', 'Truckingtradodalam::hakakses/$1');

// Routes for App\Controllers\Truckingtradoluar (Truckingtradoluar)
$routes->match(['GET', 'POST'], 'Truckingtradoluar', 'Truckingtradoluar::index');
$routes->match(['GET', 'POST'], 'truckingtradoluar', 'Truckingtradoluar::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluar/index', 'Truckingtradoluar::index');
$routes->match(['GET', 'POST'], 'truckingtradoluar/index', 'Truckingtradoluar::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluar/crud/(:any)', 'Truckingtradoluar::crud/$1');
$routes->match(['GET', 'POST'], 'truckingtradoluar/crud/(:any)', 'Truckingtradoluar::crud/$1');

// Routes for App\Controllers\Truckingtradoluardetail (Truckingtradoluardetail)
$routes->match(['GET', 'POST'], 'Truckingtradoluardetail', 'Truckingtradoluardetail::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetail', 'Truckingtradoluardetail::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluardetail/index', 'Truckingtradoluardetail::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetail/index', 'Truckingtradoluardetail::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluardetail/hakakses/(:any)', 'Truckingtradoluardetail::hakakses/$1');
$routes->match(['GET', 'POST'], 'truckingtradoluardetail/hakakses/(:any)', 'Truckingtradoluardetail::hakakses/$1');

// Routes for App\Controllers\Truckingtradoluardetailexp (Truckingtradoluardetailexp)
$routes->match(['GET', 'POST'], 'Truckingtradoluardetailexp', 'Truckingtradoluardetailexp::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetailexp', 'Truckingtradoluardetailexp::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluardetailexp/index', 'Truckingtradoluardetailexp::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetailexp/index', 'Truckingtradoluardetailexp::index');

// Routes for App\Controllers\Truckingtradoluardetailexpsby (Truckingtradoluardetailexpsby)
$routes->match(['GET', 'POST'], 'Truckingtradoluardetailexpsby', 'Truckingtradoluardetailexpsby::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetailexpsby', 'Truckingtradoluardetailexpsby::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluardetailexpsby/index', 'Truckingtradoluardetailexpsby::index');
$routes->match(['GET', 'POST'], 'truckingtradoluardetailexpsby/index', 'Truckingtradoluardetailexpsby::index');

// Routes for App\Controllers\Truckingtradoluartas (Truckingtradoluartas)
$routes->match(['GET', 'POST'], 'Truckingtradoluartas', 'Truckingtradoluartas::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartas', 'Truckingtradoluartas::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartas/index', 'Truckingtradoluartas::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartas/index', 'Truckingtradoluartas::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartas/combotradoluar', 'Truckingtradoluartas::combotradoluar');
$routes->match(['GET', 'POST'], 'truckingtradoluartas/combotradoluar', 'Truckingtradoluartas::combotradoluar');

// Routes for App\Controllers\Truckingtradoluartasjkt (Truckingtradoluartasjkt)
$routes->match(['GET', 'POST'], 'Truckingtradoluartasjkt', 'Truckingtradoluartasjkt::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartasjkt', 'Truckingtradoluartasjkt::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartasjkt/index', 'Truckingtradoluartasjkt::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartasjkt/index', 'Truckingtradoluartasjkt::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartasjkt/combotradoluar/(:any)', 'Truckingtradoluartasjkt::combotradoluar/$1');
$routes->match(['GET', 'POST'], 'truckingtradoluartasjkt/combotradoluar/(:any)', 'Truckingtradoluartasjkt::combotradoluar/$1');

// Routes for App\Controllers\Truckingtradoluartasmks (Truckingtradoluartasmks)
$routes->match(['GET', 'POST'], 'Truckingtradoluartasmks', 'Truckingtradoluartasmks::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartasmks', 'Truckingtradoluartasmks::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartasmks/index', 'Truckingtradoluartasmks::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartasmks/index', 'Truckingtradoluartasmks::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartasmks/combotradoluar/(:any)', 'Truckingtradoluartasmks::combotradoluar/$1');
$routes->match(['GET', 'POST'], 'truckingtradoluartasmks/combotradoluar/(:any)', 'Truckingtradoluartasmks::combotradoluar/$1');

// Routes for App\Controllers\Truckingtradoluartassby (Truckingtradoluartassby)
$routes->match(['GET', 'POST'], 'Truckingtradoluartassby', 'Truckingtradoluartassby::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartassby', 'Truckingtradoluartassby::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartassby/index', 'Truckingtradoluartassby::index');
$routes->match(['GET', 'POST'], 'truckingtradoluartassby/index', 'Truckingtradoluartassby::index');
$routes->match(['GET', 'POST'], 'Truckingtradoluartassby/combotradoluar/(:any)', 'Truckingtradoluartassby::combotradoluar/$1');
$routes->match(['GET', 'POST'], 'truckingtradoluartassby/combotradoluar/(:any)', 'Truckingtradoluartassby::combotradoluar/$1');

// Routes for App\Controllers\User (User)
$routes->match(['GET', 'POST'], 'User', 'User::index');
$routes->match(['GET', 'POST'], 'user', 'User::index');
$routes->match(['GET', 'POST'], 'User/index', 'User::index');
$routes->match(['GET', 'POST'], 'user/index', 'User::index');
$routes->match(['GET', 'POST'], 'User/grid', 'User::grid');
$routes->match(['GET', 'POST'], 'user/grid', 'User::grid');
$routes->match(['GET', 'POST'], 'User/form/(:any)/(:any)/(:any)', 'User::form/$1/$2/$3');
$routes->match(['GET', 'POST'], 'user/form/(:any)/(:any)/(:any)', 'User::form/$1/$2/$3');
$routes->match(['GET', 'POST'], 'User/crud', 'User::crud');
$routes->match(['GET', 'POST'], 'user/crud', 'User::crud');
$routes->match(['GET', 'POST'], 'User/generate/(:any)', 'User::generate/$1');
$routes->match(['GET', 'POST'], 'user/generate/(:any)', 'User::generate/$1');
$routes->match(['GET', 'POST'], 'User/operation/(:any)', 'User::operation/$1');
$routes->match(['GET', 'POST'], 'user/operation/(:any)', 'User::operation/$1');
$routes->match(['GET', 'POST'], 'User/getRoles', 'User::getRoles');
$routes->match(['GET', 'POST'], 'user/getroles', 'User::getRoles');
$routes->match(['GET', 'POST'], 'user/getRoles', 'User::getRoles');
$routes->match(['GET', 'POST'], 'User/getById/(:any)', 'User::getById/$1');
$routes->match(['GET', 'POST'], 'user/getById/(:any)', 'User::getById/$1');

// Routes for App\Controllers\User.old (User.old)
$routes->match(['GET', 'POST'], 'UserAcl/view/(:any)', 'UserAcl::view/$1');
$routes->match(['GET', 'POST'], 'useracl/view/(:any)', 'UserAcl::view/$1');
$routes->match(['GET', 'POST'], 'UserAcl/userroles/(:any)', 'UserAcl::userroles/$1');
$routes->match(['GET', 'POST'], 'useracl/userroles/(:any)', 'UserAcl::userroles/$1');
$routes->match(['GET', 'POST'], 'UserAcl', 'UserAcl::index');
$routes->match(['GET', 'POST'], 'useracl', 'UserAcl::index');
$routes->match(['GET', 'POST'], 'UserAcl/index', 'UserAcl::index');
$routes->match(['GET', 'POST'], 'useracl/index', 'UserAcl::index');
$routes->match(['GET', 'POST'], 'UserAcl/grid/(:any)', 'UserAcl::grid/$1');
$routes->match(['GET', 'POST'], 'useracl/grid/(:any)', 'UserAcl::grid/$1');
$routes->match(['GET', 'POST'], 'UserAcl/operation/(:any)', 'UserAcl::operation/$1');
$routes->match(['GET', 'POST'], 'useracl/operation/(:any)', 'UserAcl::operation/$1');

// Routes for App\Controllers\Usermenu (Usermenu)
$routes->match(['GET', 'POST'], 'Usermenu', 'Usermenu::index');
$routes->match(['GET', 'POST'], 'usermenu', 'Usermenu::index');
$routes->match(['GET', 'POST'], 'Usermenu/index', 'Usermenu::index');
$routes->match(['GET', 'POST'], 'usermenu/index', 'Usermenu::index');
$routes->match(['GET', 'POST'], 'Usermenu/grid/(:any)', 'Usermenu::grid/$1');
$routes->match(['GET', 'POST'], 'usermenu/grid/(:any)', 'Usermenu::grid/$1');
$routes->match(['GET', 'POST'], 'Usermenu/form/(:any)/(:any)/(:any)/(:any)', 'Usermenu::form/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'usermenu/form/(:any)/(:any)/(:any)/(:any)', 'Usermenu::form/$1/$2/$3/$4');
$routes->match(['GET', 'POST'], 'Usermenu/crud', 'Usermenu::crud');
$routes->match(['GET', 'POST'], 'usermenu/crud', 'Usermenu::crud');
$routes->match(['GET', 'POST'], 'Usermenu/operation/(:any)', 'Usermenu::operation/$1');
$routes->match(['GET', 'POST'], 'usermenu/operation/(:any)', 'Usermenu::operation/$1');
$routes->match(['GET', 'POST'], 'Usermenu/excel', 'Usermenu::excel');
$routes->match(['GET', 'POST'], 'usermenu/excel', 'Usermenu::excel');
$routes->match(['GET', 'POST'], 'Usermenu/hakakses/(:any)', 'Usermenu::hakakses/$1');
$routes->match(['GET', 'POST'], 'usermenu/hakakses/(:any)', 'Usermenu::hakakses/$1');

// Routes for App\Controllers\manage\Acl (manage/Acl)
$routes->match(['GET', 'POST'], 'manage/Acl', 'manage\Acl::index');
$routes->match(['GET', 'POST'], 'manage/acl', 'manage\Acl::index');
$routes->match(['GET', 'POST'], 'manage/Acl/index', 'manage\Acl::index');
$routes->match(['GET', 'POST'], 'manage/acl/index', 'manage\Acl::index');
$routes->match(['GET', 'POST'], 'manage/Acl/fetch', 'manage\Acl::fetch');
$routes->match(['GET', 'POST'], 'manage/acl/fetch', 'manage\Acl::fetch');
$routes->match(['GET', 'POST'], 'manage/Acl/listFolderFiles/(:any)', 'manage\Acl::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/acl/listfolderfiles/(:any)', 'manage\Acl::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/acl/listFolderFiles/(:any)', 'manage\Acl::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/Acl/listfolderfiles/(:any)', 'manage\Acl::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/Acl/get_php_classes/(:any)', 'manage\Acl::get_php_classes/$1');
$routes->match(['GET', 'POST'], 'manage/acl/get_php_classes/(:any)', 'manage\Acl::get_php_classes/$1');
$routes->match(['GET', 'POST'], 'manage/Acl/get_class_methods/(:any)/(:any)', 'manage\Acl::get_class_methods/$1/$2');
$routes->match(['GET', 'POST'], 'manage/acl/get_class_methods/(:any)/(:any)', 'manage\Acl::get_class_methods/$1/$2');
$routes->match(['GET', 'POST'], 'manage/Acl/get_method_comment/(:any)/(:any)', 'manage\Acl::get_method_comment/$1/$2');
$routes->match(['GET', 'POST'], 'manage/acl/get_method_comment/(:any)/(:any)', 'manage\Acl::get_method_comment/$1/$2');

// Routes for App\Controllers\manage\Acos (manage/Acos)
$routes->match(['GET', 'POST'], 'manage/Acos/fetch', 'manage\Acos::fetch');
$routes->match(['GET', 'POST'], 'manage/acos/fetch', 'manage\Acos::fetch');
$routes->match(['GET', 'POST'], 'manage/Acos/listFolderFiles/(:any)', 'manage\Acos::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/acos/listfolderfiles/(:any)', 'manage\Acos::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/acos/listFolderFiles/(:any)', 'manage\Acos::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/Acos/listfolderfiles/(:any)', 'manage\Acos::listFolderFiles/$1');
$routes->match(['GET', 'POST'], 'manage/Acos/get_php_classes/(:any)', 'manage\Acos::get_php_classes/$1');
$routes->match(['GET', 'POST'], 'manage/acos/get_php_classes/(:any)', 'manage\Acos::get_php_classes/$1');
$routes->match(['GET', 'POST'], 'manage/Acos/get_class_methods/(:any)/(:any)', 'manage\Acos::get_class_methods/$1/$2');
$routes->match(['GET', 'POST'], 'manage/acos/get_class_methods/(:any)/(:any)', 'manage\Acos::get_class_methods/$1/$2');
$routes->match(['GET', 'POST'], 'manage/Acos/get_method_comment/(:any)/(:any)', 'manage\Acos::get_method_comment/$1/$2');
$routes->match(['GET', 'POST'], 'manage/acos/get_method_comment/(:any)/(:any)', 'manage\Acos::get_method_comment/$1/$2');

// Ensure common CI3 variations for Login/Logout are handled
$routes->match(['GET', 'POST'], 'Login/Logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'Login/logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'login/Logout', 'Login::logout');
$routes->match(['GET', 'POST'], 'Logout/index', 'Logout::index');
$routes->match(['GET', 'POST'], 'logout/index', 'Logout::index');

// Special overrides or groups that need manual definition
$routes->group('manage', function ($routes) {
  // These are also in GeneratedRoutes, but keeping the group structure if needed for filters
  $routes->match(['GET', 'POST'], 'acos/fetch', 'manage\\Acos::fetch');
  $routes->match(['GET', 'POST'], 'acl/fetch', 'manage\\Acl::fetch');
});

// Routes for App\Controllers\Parameter
$routes->match(['GET', 'POST'], 'Parameter', 'Parameter::index');
$routes->match(['GET', 'POST'], 'parameter', 'Parameter::index');
$routes->match(['GET', 'POST'], 'Parameter/index', 'Parameter::index');
$routes->match(['GET', 'POST'], 'parameter/index', 'Parameter::index');
$routes->match(['GET', 'POST'], 'Parameter/grid', 'Parameter::grid');
$routes->match(['GET', 'POST'], 'parameter/grid', 'Parameter::grid');
$routes->match(['GET', 'POST'], 'Parameter/crud', 'Parameter::crud');
$routes->match(['GET', 'POST'], 'parameter/crud', 'Parameter::crud');
$routes->match(['GET', 'POST'], 'Parameter/getById', 'Parameter::getById');
$routes->match(['GET', 'POST'], 'parameter/getById', 'Parameter::getById');
