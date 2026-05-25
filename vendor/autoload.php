<?php
require_once __DIR__ . '/mbstring_polyfill.php';
spl_autoload_register(function ($class) {
    $maps = [
        'Dompdf\\' => __DIR__ . '/dompdf-2.0.4/src/',
        'FontLib\\' => __DIR__ . '/php-font-lib-master/src/',
        'Svg\\' => __DIR__ . '/php-svg-lib-master/src/',
        'Masterminds\\' => __DIR__ . '/html5-php-master/src/',
    ];
    // Additional class map for Dompdf lib/
    if ($class === 'Dompdf\\Cpdf') {
        $file = __DIR__ . '/dompdf-2.0.4/lib/Cpdf.php';
        if (file_exists($file)) { require $file; return; }
    }
    foreach ($maps as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) { require $file; return; }
    }
});
