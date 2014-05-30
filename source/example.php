<?php

require_once 'IniFetcher.php';

/**
 * This example demonstrates how you can use IniParser and IniFetcher classes. Below examples are only
 * for the testing purpose, please use it your own way.
 */

$fileGlobal   = '../tests/ini_files/global.ini';
$fileSimple   = '../tests/ini_files/simple.ini';
$fileComments = '../tests/ini_files/comments.ini';
$fileArray    = '../tests/ini_files/array.ini';
$fileBad      = '../tests/ini_files/bad.ini';
$fileEscape   = '../tests/ini_files/escape.ini';
$fileJson     = '../tests/ini_files/json.ini';

// IniParser way
$parser = new IniParser($fileJson);
$parser->setFormat($parser::OUTPUT_FORMAT_ARRAY); // default
$data = $parser->parse();
echo "Using IniParser: " . $data['json']['list']['creditcards']['amex']['prefix'] . '<br/>';

// IniFetcher way (preferable)
$fetcher = IniFetcher::getInstance($fileJson);
echo "Using IniFetcher: " . $fetcher::get('json.list.creditcards.amex.prefix');