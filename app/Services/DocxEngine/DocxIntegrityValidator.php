<?php

namespace App\Services\DocxEngine;

use ZipArchive;

class DocxIntegrityValidator
{
    private const REQUIRED_ENTRIES = [
        '[Content_Types].xml',
        '_rels/.rels',
        'word/document.xml',
    ];

    /**
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validate(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['valid' => false, 'errors' => ['File does not exist.']];
        }

        $zip = new ZipArchive;

        if ($zip->open($filePath) !== true) {
            return ['valid' => false, 'errors' => ['File is not a valid ZIP archive.']];
        }

        $errors = [];

        foreach (self::REQUIRED_ENTRIES as $entry) {
            if ($zip->locateName($entry) === false) {
                $errors[] = "Missing required entry: {$entry}";
            }
        }

        foreach (['word/document.xml', 'word/styles.xml', 'word/_rels/document.xml.rels'] as $xmlEntry) {
            if ($zip->locateName($xmlEntry) !== false) {
                $content = $zip->getFromName($xmlEntry);

                if ($content !== false && simplexml_load_string($content) === false) {
                    $errors[] = "Malformed XML in: {$xmlEntry}";
                }
            }
        }

        $zip->close();

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}
