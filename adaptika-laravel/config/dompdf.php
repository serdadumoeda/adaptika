<?php

$isServerless = !empty($_ENV['VERCEL']) || !empty(getenv('VERCEL'));
$fontDir = $isServerless ? '/tmp/storage/fonts' : storage_path('fonts');
$tempDir = $isServerless ? '/tmp/storage/temp' : sys_get_temp_dir();

if ($isServerless) {
    if (!is_dir($fontDir)) {
        @mkdir($fontDir, 0755, true);
    }
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    'show_warnings' => false,

    'public_path' => null,

    'convert_entities' => true,

    'options' => [
        'font_dir' => $fontDir,

        'font_cache' => $fontDir,

        'temp_dir' => $tempDir,

        'chroot' => [
            base_path(),
            public_path(),
            resource_path(),
            '/tmp',
        ],

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,

        'log_output_file' => null,

        'enable_font_subsetting' => false,

        'pdf_backend' => 'CPDF',

        'default_media_type' => 'screen',

        'default_paper_size' => 'a4',

        'default_paper_orientation' => 'portrait',

        'default_font' => 'serif',

        'dpi' => 96,

        'enable_php' => false,

        'enable_javascript' => true,

        'enable_remote' => true,

        'allowed_remote_hosts' => null,

        'font_height_ratio' => 1.1,

        'enable_html5_parser' => true,
    ],

];
