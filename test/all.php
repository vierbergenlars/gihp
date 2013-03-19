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
        $this->add(new plumbing\blob);
        $this->add(new plumbing\tree);
        $this->add(new plumbing\commit);
        $this->add(new plumbing\annotatedtag);
        $this->add(new plumbing\head);
        $this->add(new plumbing\tag);
    }
}

class gihpPorcelainTests extends \TestSuite
{
    public function __construct()
    {
        parent::__construct('Gihp porcelain tests');
        $this->add(new porcelain\tree);
        $this->add(new porcelain\tag);
        $this->add(new porcelain\branch);
    }
}
