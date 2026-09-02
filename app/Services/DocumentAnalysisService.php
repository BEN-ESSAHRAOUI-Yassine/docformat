<?php

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentVersion;
use App\Services\DocxEngine\DocxReader;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\PhpWord;

class DocumentAnalysisService
{
    public function __construct(
        private DocxReader $reader,
        private HeadingDetectionService $headingDetection,
    ) {}

    public function analyze(Document $document, DocumentVersion $version): DocumentAnalysis
    {
        $analysis = DocumentAnalysis::create([
            'document_id' => $document->id,
            'document_version_id' => $version->id,
            'status' => AnalysisStatus::ANALYZING,
        ]);

        try {
            $filePath = Storage::disk('docformat')->path($version->file_path);

            $this->reader->load($filePath);
            $phpWord = $this->reader->getPhpWord();

            if (! $phpWord) {
                throw new \RuntimeException('Failed to load DOCX file.');
            }

            $elements = $this->extractElements($phpWord, $document->id, $analysis->id);

            $headings = $this->headingDetection->detectHeadings($phpWord);
            $this->storeDetectedHeadings($headings, $document->id, $analysis->id);

            $hierarchyWarnings = $this->headingDetection->validateHierarchy($headings);

            $analysis->update([
                'status' => AnalysisStatus::COMPLETED,
                'metadata' => [
                    'element_count' => $elements + count($headings),
                    'hierarchy_warnings' => $hierarchyWarnings,
                ],
            ]);

            $document->update(['status' => 'analysis_completed']);

        } catch (\Throwable $e) {
            $analysis->update([
                'status' => AnalysisStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $document->update(['status' => 'failed']);

            throw $e;
        }

        return $analysis;
    }

    public function assignHeading(DetectedElement $element, int $level): DetectedElement
    {
        if ($level < 1 || $level > 6) {
            throw new \InvalidArgumentException('Heading level must be between 1 and 6.');
        }

        $originalData = [
            'type' => $element->type,
            'content' => $element->content,
            'metadata' => $element->metadata,
        ];

        $element->update([
            'type' => 'heading',
            'heading_level' => $level,
            'metadata' => array_merge($element->metadata ?? [], [
                'confidence' => 1.0,
                'original_data' => $originalData,
                'manual' => true,
            ]),
        ]);

        return $element;
    }

    /**
     * @return int Number of elements extracted
     */
    private function extractElements(PhpWord $phpWord, int $documentId, int $analysisId): int
    {
        $index = 0;

        foreach ($phpWord->getSections() as $sectionIndex => $section) {
            foreach ($section->getElements() as $element) {
                $data = $this->extractElement($element, $documentId, $analysisId, $index, $sectionIndex);

                if ($data) {
                    DetectedElement::create($data);
                    $index++;
                }
            }
        }

        return $index;
    }

    private function extractElement(
        AbstractElement $element,
        int $documentId,
        int $analysisId,
        int $index,
        int $sectionIndex,
    ): ?array {
        $base = [
            'document_analysis_id' => $analysisId,
            'document_id' => $documentId,
            'element_index' => $index,
            'heading_level' => null,
        ];

        if ($element instanceof Title) {
            return array_merge($base, [
                'type' => 'heading',
                'content' => $element->getText(),
                'heading_level' => (int) $element->getDepth(),
                'metadata' => ['section' => $sectionIndex],
            ]);
        }

        if ($element instanceof Table) {
            $rows = $element->getRows();
            $cells = 0;
            $content = [];
            $hasHeader = false;
            $columnWidths = [];

            foreach ($rows as $rowIndex => $row) {
                $rowCells = $row->getCells();
                $cells += count($rowCells);
                $rowData = [];

                if ($rowIndex === 0) {
                    $hasHeader = true;
                }

                foreach ($rowCells as $cellIndex => $cell) {
                    $rowData[] = $this->extractTextFromElement($cell);

                    if ($rowIndex === 0 && method_exists($cell, 'getStyle')) {
                        $cellStyle = $cell->getStyle();
                        if ($cellStyle && method_exists($cellStyle, 'getWidth')) {
                            $columnWidths[$cellIndex] = $cellStyle->getWidth();
                        }
                    }
                }

                $content[] = $rowData;
            }

            return array_merge($base, [
                'type' => 'table',
                'content' => null,
                'metadata' => [
                    'rows' => count($rows),
                    'columns' => $cells > 0 ? (int) ceil($cells / count($rows)) : 0,
                    'cells' => $cells,
                    'has_header' => $hasHeader,
                    'column_widths' => $columnWidths,
                    'content' => $content,
                    'section' => $sectionIndex,
                ],
            ]);
        }

        if ($element instanceof Image) {
            $style = $element->getStyle();
            $name = 'image_'.$index;
            $imageType = null;

            if (method_exists($element, 'getName')) {
                $n = $element->getName();
                if ($n) {
                    $name = $n;
                }
            }

            if (method_exists($element, 'getSource')) {
                $source = $element->getSource();
                if (is_string($source) && strlen($source) >= 4) {
                    $header = substr($source, 0, 4);
                    if (str_starts_with($header, "\x89PNG")) {
                        $imageType = 'image/png';
                    } elseif (str_starts_with($header, "\xFF\xD8\xFF")) {
                        $imageType = 'image/jpeg';
                    } elseif (str_starts_with($header, 'GIF8')) {
                        $imageType = 'image/gif';
                    }
                }
            }

            return array_merge($base, [
                'type' => 'figure',
                'content' => null,
                'metadata' => [
                    'name' => $name,
                    'image_type' => $imageType,
                    'width' => $style->getWidth(),
                    'height' => $style->getHeight(),
                    'is_watermark' => $element->isWatermark(),
                    'section' => $sectionIndex,
                ],
            ]);
        }

        if ($element instanceof PageBreak) {
            return array_merge($base, [
                'type' => 'page_break',
                'content' => null,
                'metadata' => ['section' => $sectionIndex],
            ]);
        }

        if ($element instanceof Text) {
            return array_merge($base, [
                'type' => 'paragraph',
                'content' => $element->getText(),
                'metadata' => ['section' => $sectionIndex],
            ]);
        }

        if ($element instanceof TextRun) {
            $text = '';
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $text .= $child->getText();
                }
            }

            if ($text !== '') {
                return array_merge($base, [
                    'type' => 'paragraph',
                    'content' => $text,
                    'metadata' => ['section' => $sectionIndex],
                ]);
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $headings
     */
    private function storeDetectedHeadings(array $headings, int $documentId, int $analysisId): void
    {
        foreach ($headings as $heading) {
            DetectedElement::create([
                'document_analysis_id' => $analysisId,
                'document_id' => $documentId,
                'type' => 'heading',
                'element_index' => $heading['index'],
                'content' => $heading['text'],
                'heading_level' => $heading['level'],
                'metadata' => [
                    'confidence' => $heading['confidence'],
                    'signals' => $heading['signals'],
                ],
            ]);
        }
    }

    private function extractTextFromElement(AbstractElement $element): string
    {
        if ($element instanceof Text) {
            return $element->getText();
        }

        if ($element instanceof TextRun) {
            $text = '';
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $text .= $child->getText();
                }
            }

            return $text;
        }

        return '';
    }
}
