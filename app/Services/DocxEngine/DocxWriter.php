<?php

namespace App\Services\DocxEngine;

use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class DocxWriter
{
    private ?PhpWord $phpWord = null;

    public function loadPhpWord(PhpWord $phpWord): void
    {
        $this->phpWord = $phpWord;
    }

    public function loadFromFile(string $filePath): void
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

    /**
     * Modify a heading's text and style.
     */
    public function modifyHeading(int $headingIndex, string $newText, ?string $newStyle = null): bool
    {
        if (! $this->phpWord) {
            return false;
        }

        $currentIndex = 0;

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Title || $this->isHeadingElement($element)) {
                    if ($currentIndex === $headingIndex) {
                        if ($element instanceof Title) {
                            $ref = new \ReflectionProperty($element, 'text');
                            $ref->setValue($element, $newText);
                        } else {
                            $this->setTextRunText($element, $newText);
                        }

                        return true;
                    }
                    $currentIndex++;
                }
            }
        }

        return false;
    }

    /**
     * Insert a page break before a specific heading index.
     */
    public function insertPageBreakBefore(int $headingIndex): bool
    {
        if (! $this->phpWord) {
            return false;
        }

        $elements = [];

        foreach ($this->phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $elements[] = $element;
            }
        }

        $currentIndex = 0;
        foreach ($elements as $index => $element) {
            if ($element instanceof Title) {
                if ($currentIndex === $headingIndex) {
                    $section = $this->phpWord->getSections()[0];
                    $section->addPageBreakBefore();

                    return true;
                }
                $currentIndex++;
            }
        }

        return false;
    }

    /**
     * Add a page break element to the document.
     */
    public function addPageBreak(): bool
    {
        if (! $this->phpWord) {
            return false;
        }

        $sections = $this->phpWord->getSections();
        if (empty($sections)) {
            return false;
        }

        $sections[0]->addPageBreak();

        return true;
    }

    /**
     * Save the document to a file.
     */
    public function save(string $filePath): bool
    {
        if (! $this->phpWord) {
            return false;
        }

        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');
        $writer->save($filePath);

        return file_exists($filePath);
    }

    /**
     * Create a new empty document.
     */
    public function createNew(): void
    {
        $this->phpWord = new PhpWord;
        $this->phpWord->addSection();
    }

    /**
     * Add a heading to the document.
     */
    public function addHeading(string $text, int $level = 1): void
    {
        if (! $this->phpWord) {
            $this->createNew();
        }

        $sections = $this->phpWord->getSections();
        if (! empty($sections)) {
            $sections[0]->addTitle($text, $level);
        }
    }

    private function isHeadingElement(AbstractElement $element): bool
    {
        if ($element instanceof TextRun) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getName')) {
                $name = strtolower($style->getName() ?? '');

                return str_starts_with($name, 'heading') || $name === 'title';
            }
        }

        return false;
    }

    private function setTextRunText(TextRun $textRun, string $newText): void
    {
        $elements = $textRun->getElements();
        if (! empty($elements) && $elements[0] instanceof Text) {
            $elements[0]->setText($newText);
        }
    }

    /**
     * Add a paragraph to the document.
     */
    public function addParagraph(string $text): void
    {
        if (! $this->phpWord) {
            $this->createNew();
        }

        $sections = $this->phpWord->getSections();
        if (! empty($sections)) {
            $sections[0]->addText($text);
        }
    }
}
