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
        return base_url($path);
    }
}
