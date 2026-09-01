<?php

use App\Models\StyleProfile;
use Database\Seeders\DefaultStyleProfileSeeder;

test('creates the default academic style profile', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();

    expect($profile)->not->toBeNull();
    expect($profile->is_system)->toBeTrue();
    expect($profile->type)->toBe('university');
    expect($profile->language)->toBe('fr-FR');
    expect($profile->version)->toBe(1);
});

test('includes all required style rules', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $rules = $profile->rules;

    expect($rules)->toHaveKeys([
        'body', 'heading_1', 'heading_2', 'heading_3',
        'heading_4', 'heading_5', 'heading_6', 'captions', 'sources',
    ]);
});

test('body rules are correct', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $body = $profile->rules['body'];

    expect($body['font_family'])->toBe('Times New Roman');
    expect($body['font_size'])->toBe(11);
    expect($body['color'])->toBe('#000000');
    expect($body['alignment'])->toBe('justify');
});

test('heading_1 rules are correct', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $h1 = $profile->rules['heading_1'];

    expect($h1['font_size'])->toBe(18);
    expect($h1['all_caps'])->toBeTrue();
    expect($h1['alignment'])->toBe('center');
    expect($h1['bold'])->toBeTrue();
});

test('heading_2 has small caps and indentation', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $h2 = $profile->rules['heading_2'];

    expect($h2['font_size'])->toBe(16);
    expect($h2['small_caps'])->toBeTrue();
    expect($h2['indentation'])->toBe(0.25);
});

test('captions rules are correct', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $captions = $profile->rules['captions'];

    expect($captions['font_size'])->toBe(10);
    expect($captions['color'])->toBe('#808080');
    expect($captions['alignment'])->toBe('center');
});

test('sources rules are correct', function () {
    $seeder = new DefaultStyleProfileSeeder;
    $seeder->run();

    $profile = StyleProfile::where('name', 'Academic Default')->first();
    $sources = $profile->rules['sources'];

    expect($sources['font_size'])->toBe(10);
    expect($sources['color'])->toBe('#808080');
    expect($sources['italic'])->toBeTrue();
    expect($sources['underline'])->toBeTrue();
    expect($sources['alignment'])->toBe('right');
});

test('seeder is idempotent', function () {
    $seeder = new DefaultStyleProfileSeeder;

    $seeder->run();
    $seeder->run();

    $count = StyleProfile::where('name', 'Academic Default')->count();
    expect($count)->toBe(1);
});
