<?php

namespace Geneanet;

require_once(__DIR__."/autoload.php");

error_reporting(E_ALL);

/* geany_encoding=ISO-8859-15 */

$config = new Config();
$geneanet = new GeneanetServer();

if (!$geneanet->login($config->get('connexion/user'), $config->get('connexion/passwd'))) {
    printf($geneanet->lastError() . "\n");
    exit(0);
}

if (isset($argv[1])) {
    $url = $argv[1];
} else {
    $url = $config->get('geneanet/default-url');
}

$grabber = new Grabber($geneanet);
$grabber->setDelay($config->get('grabber/delay'));
if ($config->get('connexion/proxy') != '') {
    $grabber->setProxy($config->get('connexion/proxy'));
}

$test = 'ascendants';

switch($test) {

    case 'single':
        $p = $grabber->grabSingle($url);
        echo utf8_decode($p);
        break;

    case 'ascendants':
        $p = $grabber->grabSingle($url);
        $grabber->grabAscendants($p, $level = 15);
        break;

    case 'descendants':
        $p = $grabber->grabSingle($url);
        $grabber->grabDescendants($p, $level = 2);
        break;

}

# print_r($p);

$writer = new GedcomWriter($config);

# for debug
# echo utf8_decode($writer->pretty($writer->write($p)));

# it seems that geneanet do not support UTF8
switch($config->get('gedcom/charset')) {
    case 'UTF-8':
    case 'UTF8':
        echo $writer->write($p);
        break;
    default:
        # default charset to ISO8859-15 (ok on Linux)
        echo utf8_decode($writer->write($p));
        break;
}

# echo $writer->write($p);
