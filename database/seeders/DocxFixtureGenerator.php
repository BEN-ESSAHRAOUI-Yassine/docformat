<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class DocxFixtureGenerator extends Seeder
{
    private string $outputDir;

    public function __construct()
    {
        $this->outputDir = base_path('tests/fixtures/docx');
    }

    public function run(): void
    {
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        $this->generateSimple();
        $this->generateComplex();
        $this->generateMultilingual();

        $this->command?->info('DOCX fixtures generated successfully.');
    }

    private function generateSimple(): void
    {
        $phpWord = new PhpWord;
        $phpWord->addTitleStyle(1, ['size' => 24, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(2, ['size' => 20, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(3, ['size' => 16, 'bold' => true], ['basedOn' => 'Normal']);
        $section = $phpWord->addSection();

        $section->addTitle('Simple Document', 1);
        $section->addTitle('Introduction', 2);
        $section->addTitle('Background', 3);

        $section->addText('This is a simple paragraph with basic text content.');
        $section->addText('This is another paragraph to test multiple paragraph handling.');
        $section->addText('Lorem ipsum dolor sit amet, consectetur adipiscing elit.');

        $this->saveFixture($phpWord, 'simple.docx');
        $this->generateManifest('simple', [
            'headings' => 3,
            'tables' => 0,
            'images' => 0,
            'pageBreaks' => 0,
            'paragraphs' => 3,
            'sections' => 1,
        ]);
    }

    private function generateComplex(): void
    {
        $phpWord = new PhpWord;
        $phpWord->addTitleStyle(1, ['size' => 24, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(2, ['size' => 20, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(3, ['size' => 16, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(4, ['size' => 14, 'bold' => true], ['basedOn' => 'Normal']);
        $section = $phpWord->addSection();

        $section->addTitle('Complex Document', 1);
        $section->addTitle('Chapter 1: Analysis', 2);
        $section->addTitle('Section 1.1: Methods', 3);
        $section->addTitle('Section 1.1.1: Data Collection', 4);
        $section->addTitle('Section 1.1.2: Data Processing', 4);
        $section->addTitle('Section 1.2: Results', 3);
        $section->addTitle('Chapter 2: Discussion', 2);
        $section->addTitle('Section 2.1: Interpretation', 3);
        $section->addTitle('Section 2.2: Limitations', 3);
        $section->addTitle('Chapter 3: Conclusion', 2);
        $section->addTitle('References', 2);
        $section->addTitle('Appendix A', 2);

        $section->addText('This paragraph contains some introductory text for the complex document.');

        $tableStyle = [
            'borderSize' => 1,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];

        $table = $section->addTable($tableStyle);
        $table->addRow();
        $table->addCell(2000)->addText('Header 1');
        $table->addCell(2000)->addText('Header 2');
        $table->addCell(2000)->addText('Header 3');
        $table->addRow();
        $table->addCell(2000)->addText('Row 1, Col 1');
        $table->addCell(2000)->addText('Row 1, Col 2');
        $table->addCell(2000)->addText('Row 1, Col 3');
        $table->addRow();
        $table->addCell(2000)->addText('Row 2, Col 1');
        $table->addCell(2000)->addText('Row 2, Col 2');
        $table->addCell(2000)->addText('Row 2, Col 3');

        $section->addText('Figure 1: This is a caption for a figure that does not exist in this test fixture.');
        $section->addText('Source: Author Name, 2024');

        $section->addPageBreak();

        $section->addText('This text appears after a page break.');

        $table2 = $section->addTable($tableStyle);
        $table2->addRow();
        $table2->addCell(3000)->addText('Data A');
        $table2->addCell(3000)->addText('Data B');
        $table2->addRow();
        $table2->addCell(3000)->addText('Value 1');
        $table2->addCell(3000)->addText('Value 2');
        $table2->addRow();
        $table2->addCell(3000)->addText('Value 3');
        $table2->addCell(3000)->addText('Value 4');
        $table2->addRow();
        $table2->addCell(3000)->addText('Value 5');
        $table2->addCell(3000)->addText('Value 6');

        $section->addText('Table 1: Data comparison table with multiple rows.');

        $this->saveFixture($phpWord, 'complex.docx');
        $this->generateManifest('complex', [
            'headings' => 12,
            'tables' => 2,
            'images' => 0,
            'pageBreaks' => 1,
            'paragraphs' => 6,
            'sections' => 1,
            'tableDetails' => [
                ['rows' => 3, 'cells' => 9],
                ['rows' => 4, 'cells' => 8],
            ],
        ]);
    }

    private function generateMultilingual(): void
    {
        $phpWord = new PhpWord;
        $phpWord->addTitleStyle(1, ['size' => 24, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(2, ['size' => 20, 'bold' => true], ['basedOn' => 'Normal']);
        $phpWord->addTitleStyle(3, ['size' => 16, 'bold' => true], ['basedOn' => 'Normal']);
        $section = $phpWord->addSection();

        $section->addTitle('Document Multilingue', 1);
        $section->addTitle('Chapitre 1 : Introduction', 2);
        $section->addTitle('Section 1.1 : Contexte', 3);

        $section->addText('Ce document contient du contenu en francais et en anglais.');
        $section->addText('Il est utilise pour tester la gestion multi-langue.');
        $section->addText('La détection de la langue est un aspect important du systeme.');

        $section->addPageBreak();

        $section->addTitle('Chapter 1: Introduction (English)', 2);
        $section->addTitle('Section 1.1: Background', 3);

        $section->addText('This document contains content in French and English.');
        $section->addText('It is used to test multi-language handling.');
        $section->addText('Language detection is an important aspect of the system.');

        $table = $section->addTable(['borderSize' => 1]);
        $table->addRow();
        $table->addCell(2000)->addText('Francais');
        $table->addCell(2000)->addText('English');
        $table->addRow();
        $table->addCell(2000)->addText('Bonjour');
        $table->addCell(2000)->addText('Hello');
        $table->addRow();
        $table->addCell(2000)->addText('Merci');
        $table->addCell(2000)->addText('Thank you');

        $this->saveFixture($phpWord, 'multilingual.docx');
        $this->generateManifest('multilingual', [
            'headings' => 5,
            'tables' => 1,
            'images' => 0,
            'pageBreaks' => 1,
            'paragraphs' => 7,
            'sections' => 1,
            'languages' => ['fr', 'en'],
        ]);
    }

    private function saveFixture(PhpWord $phpWord, string $filename): void
    {
        $filePath = $this->outputDir.'/'.$filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);
    }

    private function generateManifest(string $name, array $expected): void
    {
        $manifestPath = $this->outputDir.'/'.$name.'.manifest.json';
        file_put_contents($manifestPath, json_encode($expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
