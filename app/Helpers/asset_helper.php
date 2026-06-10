<?php

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        $filePathOnly = parse_url($path, PHP_URL_PATH);
        $absolutePath = FCPATH . ltrim($filePathOnly, '/');
        
        if (file_exists($absolutePath)) {
            $version = filemtime($absolutePath);
            $separator = (strpos($path, '?') !== false) ? '&' : '?';
            return base_url($path) . $separator . 'v=' . $version;
        }
        
        return base_url($path);
    }
}
