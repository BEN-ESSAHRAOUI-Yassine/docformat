<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\PhpWord;

class HeadingDetectionService
{
    private const SIGNAL_WEIGHTS = [
        'style' => 0.4,
        'font_size' => 0.15,
        'bold' => 0.1,
        'capitalization' => 0.1,
        'numbering' => 0.1,
        'spacing' => 0.05,
        'indentation' => 0.05,
        'text_pattern' => 0.05,
    ];

    private const HEADING_STYLES = [
        'heading1', 'heading 1', 'title',
        'heading2', 'heading 2',
        'heading3', 'heading 3',
        'heading4', 'heading 4',
        'heading5', 'heading 5',
        'heading6', 'heading 6',
    ];

    /**
     * @return array<int, array{index: int, text: string, level: int, confidence: float, signals: string[]}>
     */
    public function detectHeadings(PhpWord $phpWord): array
    {
        $headings = [];
        $index = 0;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $result = $this->analyzeElement($element, $index);

                if ($result) {
                    $headings[] = $result;
                    $index++;
                }
            }
        }

        return $headings;
    }

    /**
     * @param  array<int, array{index: int, text: string, level: int, confidence: float, signals: string[]}>  $headings
     * @return array<int, string>
     */
    public function validateHierarchy(array $headings): array
    {
        $warnings = [];
        $lastLevel = 0;

        foreach ($headings as $heading) {
            $level = $heading['level'];

            if ($lastLevel > 0 && $level > $lastLevel + 1) {
                for ($i = $lastLevel + 1; $i < $level; $i++) {
                    $warnings[] = "Heading Level {$level} appears before any Heading Level {$i}";
                }
            }

            $lastLevel = $level;
        }

        return $warnings;
    }

    private function analyzeElement(AbstractElement $element, int $index): ?array
    {
        if ($element instanceof Title) {
            return [
                'index' => $index,
                'text' => $element->getText(),
                'level' => (int) $element->getDepth(),
                'confidence' => 1.0,
                'signals' => ['style'],
            ];
        }

        $text = $this->extractText($element);
        if ($text === '' || mb_strlen($text) < 2) {
            return null;
        }

        $signals = [];
        $level = 1;

        if ($this->hasHeadingStyle($element)) {
            $signals[] = 'style';
            $level = $this->extractLevelFromStyle($element);
        }

        if ($this->hasLargeFont($element)) {
            $signals[] = 'font_size';
        }

        if ($this->isBold($element)) {
            $signals[] = 'bold';
        }

        if ($this->isCapitalized($text)) {
            $signals[] = 'capitalization';
        }

        if ($this->hasNumberingPattern($text)) {
            $signals[] = 'numbering';
            $level = $this->extractLevelFromNumbering($text, $level);
        }

        if ($this->hasSpacing($element)) {
            $signals[] = 'spacing';
        }

        if ($this->hasIndentation($element)) {
            $signals[] = 'indentation';
        }

        if ($this->hasTextPattern($text)) {
            $signals[] = 'text_pattern';
        }

        if (count($signals) === 0) {
            return null;
        }

        $confidence = $this->calculateConfidence($signals);

        if ($confidence < 0.1) {
            return null;
        }

        return [
            'index' => $index,
            'text' => $text,
            'level' => $level,
            'confidence' => round($confidence, 2),
            'signals' => $signals,
        ];
    }

    private function extractText(AbstractElement $element): string
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

    private function hasHeadingStyle(AbstractElement $element): bool
    {
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if (is_string($style)) {
                return in_array(strtolower($style), self::HEADING_STYLES);
            }
            if ($style && method_exists($style, 'getName')) {
                $name = strtolower($style->getName() ?? '');

                return in_array($name, self::HEADING_STYLES);
            }
        }

        return false;
    }

    private function extractLevelFromStyle(AbstractElement $element): int
    {
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if (is_string($style)) {
                if (preg_match('/heading\s*(\d+)/', strtolower($style), $matches)) {
                    return (int) $matches[1];
                }
                if (strtolower($style) === 'title') {
                    return 1;
                }
            }
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

        return 1;
    }

    private function hasLargeFont(AbstractElement $element): bool
    {
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getFontSize')) {
                $size = $style->getFontSize();
                if ($size && $size >= 14) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isBold(AbstractElement $element): bool
    {
        if ($element instanceof TextRun) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'isBold')) {
                return (bool) $style->isBold();
            }
        }

        return false;
    }

    private function isCapitalized(string $text): bool
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) < 3) {
            return false;
        }

        return $trimmed === mb_strtoupper($trimmed) && preg_match('/[A-Z]/', $trimmed);
    }

    private function hasNumberingPattern(string $text): bool
    {
        return (bool) preg_match('/^\d+(\.\d+)*\s/', $text)
            || (bool) preg_match('/^(chapter|section|chapitre|section)\s+\d+/i', $text);
    }

    private function extractLevelFromNumbering(string $text, int $currentLevel): int
    {
        if (preg_match('/^(\d+(?:\.\d+)*)\s/', $text, $matches)) {
            $parts = explode('.', $matches[1]);

            return min(count($parts), 6);
        }

        return $currentLevel;
    }

    private function hasSpacing(AbstractElement $element): bool
    {
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getSpacingBefore')) {
                $spacing = $style->getSpacingBefore();
                if ($spacing && $spacing >= 200) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasIndentation(AbstractElement $element): bool
    {
        if (method_exists($element, 'getStyle')) {
            $style = $element->getStyle();
            if ($style && method_exists($style, 'getIndentationLeft')) {
                $indent = $style->getIndentationLeft();
                if ($indent && $indent > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasTextPattern(string $text): bool
    {
        return (bool) preg_match('/^(chapter|section|chapitre|introduction|conclusion|abstract|r[ée]sum[ée])\s/i', $text);
    }

    private function calculateConfidence(array $signals): float
    {
        $confidence = 0.0;

        foreach ($signals as $signal) {
            $confidence += self::SIGNAL_WEIGHTS[$signal] ?? 0;
        }

        return min($confidence, 1.0);
    }
}
