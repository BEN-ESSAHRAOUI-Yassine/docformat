<?php

if (! function_exists('fixturePath')) {
    function fixturePath(string $filename): string
    {
        return base_path('tests/fixtures/docx').'/'.$filename;
    }
}

if (! function_exists('tempPath')) {
    function tempPath(string $filename): string
    {
        $dir = storage_path('app/temporary');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/'.$filename;
    }
}

if (! function_exists('cleanTemp')) {
    function cleanTemp(string $filename): void
    {
        $path = tempPath($filename);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
