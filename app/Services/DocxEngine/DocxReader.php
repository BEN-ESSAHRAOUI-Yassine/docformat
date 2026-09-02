<?php

namespace App\Services\DocxEngine;

use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class DocxReader
{
    private ?PhpWord $phpWord = null;

    private const HEADING_STYLES = [
        'Heading1', 'heading 1', 'Title',
        'Heading2', 'heading 2',
        'Heading3', 'heading 3',
        'Heading4', 'heading 4',
        'Heading5', 'heading 5',
        'Heading6', 'heading 6',
    ];

    public function load(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("DOCX file not found: {$filePath}");
        }

        $this->phpWord = IOFactory::load($filePath);
    }

    public function getPhpWord(): ?PhpWord
    {
        return $this->phpWord;
    }

    public function getSections(): array
    {
        if (! $this->phpWord) {
            return [];
        }

        return $this->phpWord->getSections();
    }

    /**
     * @return array{headings: array, paragraphs: array, tables: array, images: array, pageBreaks: array, sections: int}
     */
    public function extractAll(): array
    {
        $result = [
            'headings' => [],
            'paragraphs' => [],
            'tables' => [],
            'images' => [],
            'pageBreaks' => [],
            'sections' => 0,
        ];

        if (! $this->phpWord) {
            return $result;
        }

        $sections = $this->phpWord->getSections();
        $result['sections'] = count($sections);

        foreach ($sections as $section) {
            $this->extractFromSection($section, $result);
        }

        return $result;
    }

    /**
     * Extract headings from the document.
     *
     * @return array<int, array{index: int, text: string, style: string, level: int}>
     */
    public function extractHeadings(): array
    {
        $headings = [];
        $index = 0;

        if (! $this->phpWord) {
            return $headings;
        }

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Title) {
                    $styleVal = $element->getStyle();
                    $headings[] = [
                        'index' => $index++,
                        'text' => $element->getText(),
                        'style' => is_string($styleVal) ? $styleVal : ($styleVal->getName() ?? ''),
                        'level' => (int) $element->getDepth(),
                    ];
                } elseif ($this->isHeadingElement($element)) {
                    $headings[] = [
                        'index' => $index++,
                        'text' => $this->extractTextFromElement($element),
                        'style' => $element->getStyle()->getName() ?? '',
                        'level' => $this->extractHeadingLevel($element),
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Extract tables from the document.
     *
     * @return array<int, array{rows: int, cells: int, content: array, columnCount: int, hasHeader: bool, columnWidths: array<int, int>, properties: array}>
     */
    public function extractTables(): array
    {
        $tables = [];

        if (! $this->phpWord) {
            return $tables;
        }

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table) {
                    $tables[] = $this->extractTableData($element);
                }
            }
        }

        return $tables;
    }

    /**
     * Extract images from the document.
     *
     * @return array<int, array{index: int, isWatermark: bool, style: array, name: string, type: string|null, source: string|null}>
     */
    public function extractImages(): array
    {
        $images = [];
        $index = 0;

        if (! $this->phpWord) {
            return $images;
        }

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Image) {
                    $images[] = $this->extractImageData($element, $index++);
                }
            }
        }

        return $images;
    }

    /**
     * Extract page breaks from the document.
     *
     * @return array<int, int>
     */
    public function extractPageBreaks(): array
    {
        $breaks = [];
        $index = 0;

        if (! $this->phpWord) {
            return $breaks;
        }

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof PageBreak) {
                    $breaks[] = $index++;
                }
            }
        }

        return $breaks;
    }

    /**
     * Extract paragraph texts from the document.
     *
     * @return array<int, string>
     */
    public function extractParagraphs(): array
    {
        $paragraphs = [];

        if (! $this->phpWord) {
            return $paragraphs;
        }

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Text) {
                    $paragraphs[] = $element->getText();
                } elseif ($element instanceof TextRun && ! $this->isHeadingElement($element)) {
                    $text = $this->extractTextFromElement($element);
                    if ($text !== '') {
                        $paragraphs[] = $text;
                    }
                }
            }
        }

        return $paragraphs;
    }

    /**
     * Count all element types in the document.
     *
     * @return array{headings: int, tables: int, images: int, pageBreaks: int, paragraphs: int, sections: int}
     */
    public function countElements(): array
    {
        $extracted = $this->extractAll();

        return [
            'headings' => count($extracted['headings']),
            'tables' => count($extracted['tables']),
            'images' => count($extracted['images']),
            'pageBreaks' => count($extracted['pageBreaks']),
            'paragraphs' => count($extracted['paragraphs']),
            'sections' => $extracted['sections'],
        ];
    }

    private function isHeadingElement(AbstractElement $element): bool
    {
        if ($element instanceof TextRun) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getName')) {
                $name = strtolower($style->getName() ?? '');
                if (str_starts_with($name, 'heading') || $name === 'title') {
                    return true;
                }
            }
        }

        // Also check paragraph-level style for heading
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if ($style && is_string($style)) {
                $name = strtolower($style);

                return str_starts_with($name, 'heading') || $name === 'title';
            }
        }

        return false;
    }

    private function extractTextFromElement(AbstractElement $element): string
    {
        $text = '';

        if ($element instanceof Text) {
            return $element->getText();
        }

        if ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $text .= $child->getText();
                }
            }
        }

        return $text;
    }

    private function extractHeadingLevel(AbstractElement $element): int
    {
        if ($element instanceof Title) {
            return $element->getDepth();
        }

        if ($element instanceof TextRun) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getName')) {
                $name = strtolower($style->getName() ?? '');
                if (preg_match('/heading\s*(\d+)/', $name, $matches)) {
                    return (int) $matches[1];
                }
                if ($name === 'title') {
                    return 1;
                }
            }
        }

        // Check paragraph-level style
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if ($style && is_string($style)) {
                $name = strtolower($style);
                if (preg_match('/heading\s*(\d+)/', $name, $matches)) {
                    return (int) $matches[1];
                }
                if ($name === 'title') {
                    return 1;
                }
            }
        }

        return 1;
    }

    private function extractTableData(Table $table): array
    {
        $rows = $table->getRows();
        $tableData = [
            'rows' => count($rows),
            'cells' => 0,
            'content' => [],
            'columnCount' => 0,
            'hasHeader' => false,
            'columnWidths' => [],
            'properties' => [
                'borders' => null,
                'alignment' => null,
                'cellMargin' => null,
            ],
        ];

        $style = $table->getStyle();
        if ($style) {
            if (method_exists($style, 'getBorders')) {
                $borders = $style->getBorders();
                $tableData['properties']['borders'] = $borders !== null;
            }
            if (method_exists($style, 'getAlignment')) {
                $tableData['properties']['alignment'] = $style->getAlignment();
            }
        }

        foreach ($rows as $rowIndex => $row) {
            $cells = $row->getCells();
            $tableData['cells'] += count($cells);
            $rowData = [];

            if ($rowIndex === 0) {
                $tableData['hasHeader'] = true;
                $tableData['columnCount'] = count($cells);
            }

            foreach ($cells as $cellIndex => $cell) {
                $rowData[] = $this->extractTextFromElement($cell);

                if ($rowIndex === 0 && method_exists($cell, 'getStyle')) {
                    $cellStyle = $cell->getStyle();
                    if ($cellStyle && method_exists($cellStyle, 'getWidth')) {
                        $tableData['columnWidths'][$cellIndex] = $cellStyle->getWidth();
                    }
                }
            }

            $tableData['content'][] = $rowData;
        }

        return $tableData;
    }

    private function extractImageData(Image $image, int $index): array
    {
        $style = $image->getStyle();
        $data = [
            'index' => $index,
            'isWatermark' => $image->isWatermark(),
            'style' => [
                'width' => $style->getWidth(),
                'height' => $style->getHeight(),
            ],
            'name' => 'image_'.$index,
            'type' => null,
            'source' => null,
        ];

        if (method_exists($image, 'getSource')) {
            $source = $image->getSource();
            if (is_resource($source) || is_string($source)) {
                $data['source'] = 'binary';
                $data['type'] = $this->detectImageType($source);
            }
        }

        if (method_exists($image, 'getName')) {
            $name = $image->getName();
            if ($name) {
                $data['name'] = $name;
            }
        }

        return $data;
    }

    private function detectImageType($source): ?string
    {
        if (is_string($source) && strlen($source) >= 4) {
            $header = substr($source, 0, 4);
            if (str_starts_with($header, "\x89PNG")) {
                return 'image/png';
            }
            if (str_starts_with($header, "\xFF\xD8\xFF")) {
                return 'image/jpeg';
            }
            if (str_starts_with($header, 'GIF8')) {
                return 'image/gif';
            }
        }

        return null;
    }

    private function extractFromSection(Section $section, array &$result): void
    {
        foreach ($section->getElements() as $element) {
            if ($element instanceof Title) {
                $styleVal = $element->getStyle();
                $result['headings'][] = [
                    'index' => count($result['headings']),
                    'text' => $element->getText(),
                    'style' => is_string($styleVal) ? $styleVal : ($styleVal->getName() ?? ''),
                    'level' => (int) $element->getDepth(),
                ];
            } elseif ($this->isHeadingElement($element)) {
                $result['headings'][] = [
                    'index' => count($result['headings']),
                    'text' => $this->extractTextFromElement($element),
                    'style' => $element->getStyle()->getName() ?? '',
                    'level' => $this->extractHeadingLevel($element),
                ];
            } elseif ($element instanceof Table) {
                $result['tables'][] = $this->extractTableData($element);
            } elseif ($element instanceof Image) {
                $result['images'][] = $this->extractImageData($element, count($result['images']));
            } elseif ($element instanceof PageBreak) {
                $result['pageBreaks'][] = count($result['pageBreaks']);
            } elseif ($element instanceof Text) {
                $result['paragraphs'][] = $element->getText();
            } elseif ($element instanceof TextRun && ! $this->isHeadingElement($element)) {
                $text = $this->extractTextFromElement($element);
                if ($text !== '') {
                    $result['paragraphs'][] = $text;
                }
            }
        }
    }
}
