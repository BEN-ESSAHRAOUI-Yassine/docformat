<?php

use App\Services\DocxEngine\DocxWriter;

it('validates ZIP structure of simple fixture', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('simple.docx'));
    $tempPath = $this->tempPath('integrity-simple.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $zip->open($tempPath);

    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse();
    expect($zip->locateName('word/document.xml'))->not->toBeFalse();
    expect($zip->locateName('word/_rels/document.xml.rels'))->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('integrity-simple.docx');
});

it('validates ZIP structure of complex fixture', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('integrity-complex.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $zip->open($tempPath);

    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse();
    expect($zip->locateName('word/document.xml'))->not->toBeFalse();
    expect($zip->locateName('word/styles.xml'))->not->toBeFalse();
    expect($zip->locateName('word/_rels/document.xml.rels'))->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('integrity-complex.docx');
});

it('validates ZIP structure of multilingual fixture', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('multilingual.docx'));
    $tempPath = $this->tempPath('integrity-multilingual.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $zip->open($tempPath);

    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse();
    expect($zip->locateName('word/document.xml'))->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('integrity-multilingual.docx');
});

it('document.xml contains valid XML', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('integrity-xml.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $zip->open($tempPath);

    $xmlContent = $zip->getFromName('word/document.xml');
    expect($xmlContent)->not->toBeFalse();

    $xml = simplexml_load_string($xmlContent);
    expect($xml)->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('integrity-xml.docx');
});

it('Content_Types.xml is valid XML', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('simple.docx'));
    $tempPath = $this->tempPath('integrity-content-types.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $zip->open($tempPath);

    $xmlContent = $zip->getFromName('[Content_Types].xml');
    expect($xmlContent)->not->toBeFalse();

    $xml = simplexml_load_string($xmlContent);
    expect($xml)->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('integrity-content-types.docx');
});
