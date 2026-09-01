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
     * @return array<int, array{rows: int, cells: int, content: array}>
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
     * @return array<int, array{index: int, isWatermark: bool, style: array}>
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
                    $style = $element->getStyle();
                    $images[] = [
                        'index' => $index++,
                        'isWatermark' => $element->isWatermark(),
                        'style' => [
                            'width' => $style->getWidth(),
                            'height' => $style->getHeight(),
                        ],
                    ];
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
        ];

        foreach ($rows as $row) {
            $cells = $row->getCells();
            $tableData['cells'] += count($cells);
            $rowData = [];

            foreach ($cells as $cell) {
                $rowData[] = $this->extractTextFromElement($cell);
            }

            $tableData['content'][] = $rowData;
        }

        return $tableData;
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
                $style = $element->getStyle();
                $result['images'][] = [
                    'index' => count($result['images']),
                    'isWatermark' => $element->isWatermark(),
                    'style' => [
                        'width' => $style->getWidth(),
                        'height' => $style->getHeight(),
                    ],
                ];
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
