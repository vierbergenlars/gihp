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

namespace test\plumbing;

use gihp\IO\File;

class head extends \UnitTestCase
{
    private $io;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }

    public function testHeadCreation()
    {
        $commit = new \gihp\Object\Commit('Msg', new \gihp\Object\Tree, new \gihp\Metadata\Person('gihp', 'git@gihp'));
        $master = new \gihp\Ref\Head('master', $commit);

        $this->assertEqual($master->getName(), 'master');
        $this->assertIdentical($master->getCommit(), $commit);
        $this->assertIdentical($master->getObject(), $commit);

        $commit2 = new \gihp\Object\Commit('Msg2', new \gihp\Object\Tree, new \gihp\Metadata\Person('gihp', 'git@gihp'), new \DateTime, $commit);
        $master->setCommit($commit2);

        $this->assertIdentical($master->getCommit(), $commit2);
        $this->assertIdentical($master->getObject(), $commit2);

        try {
            $master->write($this->io);
        } catch (\RuntimeException $e) {
            if($e->getMessage() != "Ref already exists")
                throw $e;
            $this->io->removeRef($master);
            $master->write($this->io);
        }
    }

    public function testHeadLoad()
    {
        $master = $this->io->readRef('heads/master');

        $this->assertIsA($master, 'gihp\\Ref\\Head');
        $this->assertIdentical($master->getCommit(), $master->getObject());
        $this->assertEqual($master->getCommit()->getMessage(), 'Msg2');
    }
}
