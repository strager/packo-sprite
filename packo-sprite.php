<?php

error_reporting(E_ALL | E_STRICT);

require_once 'SpritePacker.php';

function error($message) {
    fwrite(STDERR, 'ERROR: ');
    fwrite(STDERR, $message);
    fwrite(STDERR, "\n");
    exit(1);
}

function readCommandLineArgs($argv) {
    $trim = false;

    while (substr($argv[1], 0, 2) === '--') {
        switch($argv[1]) {
            case '--trim':
                $trim = true;
                break;
        }

        array_shift($argv);
    }

    if (count($argv) < 3) {
        error('Give me an output directory and some input files');
    }

    $outputDirectory = $argv[1];
    $inputFiles = array_slice($argv, 2);

    if (!file_exists($outputDirectory)) {
        mkdir($outputDirectory);
    }

    if (!is_dir($outputDirectory)) {
        error('Output must be a directory: ' . $outputDirectory);
    }

    foreach ($inputFiles as $inputFile) {
        if (!file_exists($inputFile)) {
            error('File does not exist: ' . $inputFile);
        }
    }

    return array(
        'outputDirectory' => $outputDirectory,
        'inputFiles' => $inputFiles,
        'width' => 1024,
        'height' => 1024,
        'trim' => $trim
    );
}

function process($options) {
    $spritePacker = new SpritePacker($options['width'], $options['height'], $options['trim']);
    $spritePacker->insertFiles($options['inputFiles']);

    $definitions = $spritePacker->getSpriteSheetDefinitions();
    $spritePacker->writeSpriteSheets($options['outputDirectory']);

    echo json_encode($definitions) . "\n";
}

if (!extension_loaded('gd')) {
    error('gd extension not loaded');
}

process(readCommandLineArgs($argv));
