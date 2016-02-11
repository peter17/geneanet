<?php

namespace Geneanet;

require_once(__DIR__."/autoload.php");

error_reporting(E_ALL);

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

$test = 'single';

switch($test) {

    case 'single':
        $p = $grabber->grabSingle($url);
        # print_r($p);
        echo utf8_decode($p);
        break;

    case 'ascendants':
        $p = $grabber->grabSingle($url);
        $list = $grabber->grabAscendants($p, $level = 15);
        break;

    case 'descendants':
        $p = $grabber->grabSingle($url);
        $grabber->grabDescendants($p, $level = 14);
        break;

    case 'siblings':
        $p = $grabber->grabSingle($url);
        printf("siblings of : %s\n", utf8_decode($p->quickDisplay()));
        $list = $grabber->grabSiblings($p);
        foreach ($list as $p) {
            printf(" - %s\n", utf8_decode($p->quickDisplay()));
        }
        break;
        
    case 'half-siblings':
        $p = $grabber->grabSingle($url);
        printf("half-siblings of : %s\n", utf8_decode($p->quickDisplay()));
        $list = $grabber->grabHalfSiblings($p);
        foreach ($list as $p) {
            printf(" - %s\n", utf8_decode($p->quickDisplay()));
        }
        break;
        
    case 'unions':

        $p = $grabber->grabSingle($url);

        $unions = $grabber->grabUnionsAndChilds($p);
        printf("Unions with %s\n", utf8_decode($p->quickDisplay()));
        foreach ($unions as $u) {
            printf("- union : %s\n", utf8_decode($u['spouse']->quickDisplay()));
            foreach ($u['childs'] as $c) {
                printf("  - %s\n", utf8_decode($c->quickDisplay()));
            }
        }
        break;

}
