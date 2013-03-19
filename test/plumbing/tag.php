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

class tag extends \UnitTestCase
{
    private $io;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }

    public function testHeadCreation()
    {
        $commit = new \gihp\Object\Commit('Msg', new \gihp\Object\Tree, new \gihp\Metadata\Person('gihp', 'git@gihp'));
        $atag = new \gihp\Object\AnnotatedTag('tag', 'Annotated tag', new \gihp\Metadata\Person('gihp-tagger', 'tags@gihp'), new \DateTime, $commit);
        $tag = new \gihp\Ref\Tag('tag', $atag);
        $ctag = new \gihp\Ref\Tag('ctag', $commit);

        $this->assertEqual($tag->getName(), 'tag');
        $this->assertIsA($tag->getCommit(), 'gihp\\Object\\Commit');
        $this->assertIdentical($tag->getCommit(), $commit);
        $this->assertIsA($tag->getObject(), 'gihp\\Object\\AnnotatedTag');
        $this->assertIdentical($tag->getObject(), $atag);

        $this->assertEqual($ctag->getName(), 'ctag');
        $this->assertIsA($ctag->getCommit(), 'gihp\\Object\\Commit');
        $this->assertIdentical($ctag->getCommit(), $commit);
        $this->assertIsA($ctag->getObject(), 'gihp\\Object\\Commit');
        $this->assertIdentical($ctag->getObject(), $commit);

        $tag->write($this->io);
        $ctag->write($this->io);
    }

    public function testHeadLoad()
    {
        $tag = $this->io->readRef('tags/tag');
        $ctag = $this->io->readRef('tags/ctag');

        $this->assertEqual($tag->getName(), 'tag');
        $this->assertIsA($tag->getCommit(), 'gihp\\Object\\Commit');
        $this->assertIdentical($tag->getCommit()->getMessage(), 'Msg');
        $this->assertIsA($tag->getObject(), 'gihp\\Object\\AnnotatedTag');
        $this->assertIdentical($tag->getObject()->getMessage(), 'Annotated tag');

        $this->assertEqual($ctag->getName(), 'ctag');
        $this->assertIsA($ctag->getCommit(), 'gihp\\Object\\Commit');
        $this->assertIdentical($ctag->getCommit()->getMessage(), 'Msg');
        $this->assertIsA($ctag->getObject(), 'gihp\\Object\\Commit');
        $this->assertIdentical($ctag->getObject()->getMessage(), 'Msg');
    }
}
