<?php

error_reporting(E_ALL);

require __DIR__.'/autoload.php';

Session::init();
UOJTime::init();
DB::init();

$myUser = null;
Auth::init();
UOJLocale::init();
