<?php

namespace test\plumbing;

use gihp\IO\File;

class blob extends \UnitTestCase
{
    private $io;
    private $blob_sha;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }

    public function testBlobCreation()
    {
        $blob = new \gihp\Object\Blob('blah blah blah');
        $this->blob_sha = $blob->getSHA1();
        $blob->write($this->io);

        $this->assertEqual($blob->getData(), 'blah blah blah');
    }

    public function testBlobLoading()
    {
        $this->io->clearCache();
        $blob = $this->io->readObject($this->blob_sha);

        $this->assertEqual($blob->getData(), 'blah blah blah');
    }

}
