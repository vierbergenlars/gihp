<?php
/**
 * Copyright (c) 2013 Lars Vierbergen
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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
