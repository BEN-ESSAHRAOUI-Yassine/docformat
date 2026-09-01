<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesPath = base_path('tests/fixtures/docx');
    }

    protected function fixturePath(string $filename): string
    {
        return $this->fixturesPath.'/'.$filename;
    }

    protected function manifestPath(string $name): string
    {
        return $this->fixturesPath.'/'.$name.'.manifest.json';
    }

    protected function loadManifest(string $name): array
    {
        $path = $this->manifestPath($name);

        return json_decode(file_get_contents($path), true);
    }

    protected function tempPath(string $filename): string
    {
        $dir = storage_path('app/temporary');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.'/'.$filename;
    }

    protected function cleanTemp(string $filename): void
    {
        $path = $this->tempPath($filename);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
