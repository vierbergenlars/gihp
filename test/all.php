<?php

namespace test;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../vendor/vierbergenlars/simpletest/autorun.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->add('test', __DIR__.'/..');
$loader->register();

class gihpPlumbingTests extends \TestSuite {
    function __construct() {
        parent::__construct('Gihp plumbing tests');
        $this->add(new plumbing\io);
    }
}

