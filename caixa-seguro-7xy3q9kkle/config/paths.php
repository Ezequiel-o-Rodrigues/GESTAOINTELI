<?php
class PathConfig {
    const BASE_DIR = __DIR__ . '/../';
    
    // URLs públicas (para navegador)
    public static function url($path = '') {
        $base = self::baseUrl();
        $path = ltrim($path, '/');

        return $base . ($path === '' ? '/' : '/' . $path);
    }
    
    public static function api($endpoint = '') {
        return self::url('api/' . ltrim($endpoint, '/'));
    }
    
    public static function modules($module = '') {
        return self::url('modules/' . ltrim($module, '/'));
    }

    private static function baseUrl() {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $subdirectoryPositions = array_filter([
            strpos($scriptName, '/modules/'),
            strpos($scriptName, '/api/')
        ], static function ($position) {
            return $position !== false;
        });

        if ($subdirectoryPositions) {
            return substr($scriptName, 0, min($subdirectoryPositions));
        }

        $directory = rtrim(dirname($scriptName), '/.');
        return $directory === '' ? '' : $directory;
    }
    
    // Caminhos físicos no servidor
    public static function root($path = '') {
        return self::BASE_DIR . ltrim($path, '/');
    }
    
    public static function config($file = '') {
        return self::root('config/' . ltrim($file, '/'));
    }
    
    public static function modules_dir($module = '') {
        return self::root('modules/' . ltrim($module, '/'));
    }
}
?>