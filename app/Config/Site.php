<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Site extends BaseConfig
{
    public string $siteTitle     = 'Management Information System';
    public string $siteName      = 'PT. TRANSPORINDO AGUNG SEJAHTERA';
    public string $siteSlogan    = 'Management Information System';
    public string $siteVersion   = '1.0.0';
    
    // Meta Tags
    public string $metaAuthor    = 'TAS IT Department';
    public string $metaDesc      = 'Management Information System for PT. Transporindo Agung Sejahtera';
    public string $metaKeywords  = 'MIS, Management Information System, Transporindo, Logistics';
    
    // Assets
    public string $siteIcon      = 'libraries/tas-lib/img/logo-min.png';
    public string $siteLogo      = 'libraries/tas-lib/img/logo-min.png';
}
