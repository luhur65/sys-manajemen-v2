<?php
// Migrated from CI3: application/helpers/global_helper.php
 

function ip() {
    // Sengaja TIDAK membaca HTTP_CLIENT_IP / HTTP_X_FORWARDED_FOR sendiri lagi.
    // Versi lama fungsi ini mempercayai header itu tanpa pernah memeriksa siapa
    // pengirimnya, jadi siapa pun cukup melampirkan `Client-IP: 8.8.8.8` untuk
    // menentukan sendiri isi kolom `ip` di tabel log aktivitas -- jejak yang
    // justru paling dibutuhkan saat ada percobaan login mencurigakan malah bisa
    // dikarang oleh pelakunya.
    //
    // getIPAddress() menukar REMOTE_ADDR dengan isi header HANYA kalau request
    // memang datang dari proxy yang terdaftar di Config\App::$proxyIPs (header
    // mana yang dibaca ditentukan Config\App::$proxyHeader), dan mengembalikan
    // '0.0.0.0' kalau yang didapat bukan IP yang sah. Di CLI, tempat REMOTE_ADDR
    // tidak ada, hasilnya juga '0.0.0.0' -- bukan exception.
    return service('request')->getIPAddress();
}
//get_ip--------------------------------------------------------------

//get_detect--------------------------------------------------------------
function detect() {
    $agent = service('request')->getUserAgent();
    
    if ($agent->isBrowser()) {
        $user_agent = $agent->getBrowser() . ' ' . $agent->getVersion();
    } elseif ($agent->isRobot()) {
        $user_agent = $agent->getRobot();
    } elseif ($agent->isMobile()) {
        $user_agent = $agent->getMobile();
    } else {
        $user_agent = 'Unidentified User Agent';
    }
    
    $platform = $agent->getPlatform();
    $full_agent = $agent->getAgentString();
    
    return $platform
        ? $user_agent . ' on ' . $platform
        : $user_agent;
}
?>
