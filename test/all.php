<?php

namespace test;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../vendor/vierbergenlars/simpletest/autorun.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->add('test', __DIR__.'/..');
$loader->register();

class TraceReporter extends \TextReporter
{
    public function paintException($exception)
    {
        parent::paintException($exception);
        print $exception->getTraceAsString();
        print "\n";
    }
}

\SimpleTest::prefer(new TraceReporter);

class gihpPlumbingTests extends \TestSuite
{
    public function __construct()
    {
        parent::__construct('Gihp plumbing tests');
        $this->add(new plumbing\io);
    }
}
