<?php

if (! function_exists('site_name')) {
    /**
     * Returns the application site name.
     *
     * @param bool $escape Whether to escape the value for HTML output.
     * @return string
     */
    function site_name(bool $escape = true): string
    {
        $name = config('App')->siteName ?? '';

        if (! $escape) {
            return $name;
        }

        if (function_exists('esc')) {
            return esc($name);
        }

        return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    }
}
