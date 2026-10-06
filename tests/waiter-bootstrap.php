<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$loader = require $root . '/vendor/autoload.php';
$loader->addPsr4('ControleOnline\\Orders\\Tests\\', $root . '/vendor/controleonline/orders/tests', true);
$loader->addPsr4('ControleOnline\\Tests\\', $root . '/vendor/controleonline/financial/tests', true);
$_SERVER['CONTROLEONLINE_TEST_APP_ROOT'] = $root;
