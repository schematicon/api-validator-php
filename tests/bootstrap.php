<?php

namespace SchematiconTests;

use Tester\Environment;


require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();

header('Content-type: text/plain');
putenv('ANSICON=TRUE');
