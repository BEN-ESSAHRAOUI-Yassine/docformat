<?php

namespace Database\Seeders;

use App\Models\StyleProfile;
use Illuminate\Database\Seeder;

class DefaultStyleProfileSeeder extends Seeder
{
    public function run(): void
    {
        StyleProfile::updateOrCreate(
            ['name' => 'Academic Default', 'is_system' => true],
            [
                'description' => 'Default academic style profile for French-language documents',
                'type' => 'university',
                'language' => 'fr-FR',
                'version' => 1,
                'is_system' => true,
                'rules' => [
                    'body' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 11,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'justify',
                        'line_spacing' => 1.5,
                    ],
                    'heading_1' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 18,
                        'color' => '#000000',
                        'bold' => true,
                        'italic' => false,
                        'underline' => false,
                        'all_caps' => true,
                        'alignment' => 'center',
                        'spacing_before' => 24,
                        'spacing_after' => 12,
                    ],
                    'heading_2' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 16,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'small_caps' => true,
                        'alignment' => 'left',
                        'indentation' => 0.25,
                        'spacing_before' => 18,
                        'spacing_after' => 6,
                    ],
                    'heading_3' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 14,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'left',
                        'indentation' => 0.5,
                        'numbering' => true,
                        'numbering_format' => '1./2./3.',
                        'spacing_before' => 12,
                        'spacing_after' => 6,
                    ],
                    'heading_4' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 12,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'left',
                        'indentation' => 0.75,
                        'numbering' => true,
                        'numbering_format' => '1.1/1.2',
                        'spacing_before' => 12,
                        'spacing_after' => 6,
                    ],
                    'heading_5' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 12,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'left',
                        'indentation' => 1.0,
                        'numbering' => true,
                        'numbering_format' => '1.1.1/1.1.2',
                        'spacing_before' => 12,
                        'spacing_after' => 6,
                    ],
                    'heading_6' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 12,
                        'color' => '#000000',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'left',
                        'indentation' => 1.0,
                        'numbering' => true,
                        'numbering_format' => '1.1.1.1',
                        'spacing_before' => 12,
                        'spacing_after' => 6,
                    ],
                    'captions' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 10,
                        'color' => '#808080',
                        'bold' => false,
                        'italic' => false,
                        'underline' => false,
                        'alignment' => 'center',
                    ],
                    'sources' => [
                        'font_family' => 'Times New Roman',
                        'font_size' => 10,
                        'color' => '#808080',
                        'bold' => false,
                        'italic' => true,
                        'underline' => true,
                        'alignment' => 'right',
                    ],
                ],
            ]
        );
    }
}
