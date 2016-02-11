<?php

require_once(__DIR__."/vendor/autoload.php");

spl_autoload_register(function ($class) {
    $filename = preg_replace("#\\\\#", "/", $class).".php";
    include __DIR__."/src/$filename";
});

