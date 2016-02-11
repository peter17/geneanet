<?php

namespace Geneanet;

require_once(__DIR__."/autoload.php");

error_reporting(E_ALL);

if (isset($argv[1])) {
    $file = $argv[1];
} else {
    usage();
    exit(0);
}

$writer = new GedcomWriter($cnf = null);

echo $writer->unpretty(file_get_contents($file));
