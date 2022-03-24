<?php

require __DIR__.'/../vendor/autoload.php';

spl_autoload_register(function($class_name) {
    require_once __DIR__ . '/models/' . $class_name . '.php';
});

$REQUIRE_LIB = [];
$REQUIRE_BUNDLE = [];

function requireLib($name) { // html lib
    global $REQUIRE_LIB;
    $REQUIRE_LIB[$name] = '';
}
function requireBundle($name) { // bundle
    global $REQUIRE_BUNDLE;
    $REQUIRE_BUNDLE[$name] = '';
}
function requirePHPLib($name) { // uoj php lib
    require __DIR__.'/uoj-'.$name.'-lib.php';
}

requirePHPLib('exception');
requirePHPLib('validate');
requirePHPLib('rand');
requirePHPLib('utility');
requirePHPLib('security');
requirePHPLib('contest');
requirePHPLib('html');