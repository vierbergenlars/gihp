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

namespace test\porcelain;

class tag extends \UnitTestCase
{
    private $io;

    public function __construct()
    {
        $this->io = new \gihp\IO\File(__DIR__.'/../repo');
    }

    public function testCreatePlainTag()
    {
        $commit = $this->io->readHead()->getCommit();
        $tag = new \gihp\Tag('a-tag', $commit);

        $this->assertFalse($tag->isAnnotated());
        $this->assertEqual($tag->getName(), 'a-tag');
        $this->assertEqual($tag->getAuthor(), $commit->getAuthor());
        $this->assertEqual($tag->getDate(), $commit->getAuthorTime());
        $this->assertEqual($tag->getMessage(), $commit->getMessage());
        $this->assertEqual($tag->getCommit(), $commit);
        $this->assertIsA($tag->getTag(), '\\gihp\\Ref\\Tag');
        $this->assertEqual($tag->getTag()->getObject(), $commit);
    }

    public function testCreateAnnotatedTag()
    {
        $commit = $this->io->readHead()->getCommit();
        $tagger = new \gihp\Metadata\Person('gihp-tagger', 'tagger@gihp');
        $tag_date = new \DateTime('2001-01-01');
        $tag = new \gihp\Tag('b-tag', $commit, 'bbb', $tagger, $tag_date);

        $this->assertTrue($tag->isAnnotated());
        $this->assertEqual($tag->getName(), 'b-tag');
        $this->assertEqual($tag->getAuthor(), $tagger);
        $this->assertEqual($tag->getDate(), $tag_date);
        $this->assertEqual($tag->getMessage(), 'bbb');
        $this->assertEqual($tag->getCommit(), $commit);
        $this->assertIsA($tag->getTag(), '\\gihp\\Ref\\Tag');
        $this->assertIsA($tag->getTag()->getObject(), '\\gihp\\Object\\AnnotatedTag');
        $this->assertEqual($tag->getTag()->getObject()->getObject(), $commit);
    }

    public function testCreateBadAnnotatedTag()
    {
        $commit = $this->io->readHead()->getCommit();
        $this->expectError(new \PatternExpectation('/must be an instance of .*, null given/'));
        new \gihp\Tag('b-tag', $commit, 'bbb');

    }

    public function testCreateAnnotatedTagInferDate()
    {
        $commit = $this->io->readHead()->getCommit();
        $tagger = new \gihp\Metadata\Person('gihp-tagger', 'tagger@gihp');
        $tag_date = new \DateTime('now');
        $tag = new \gihp\Tag('b-tag', $commit, 'bbb', $tagger);

        $this->assertTrue($tag->isAnnotated());
        $this->assertEqual($tag->getName(), 'b-tag');
        $this->assertEqual($tag->getAuthor(), $tagger);
        $this->assertEqual($tag->getDate(), $tag_date);
        $this->assertEqual($tag->getMessage(), 'bbb');
        $this->assertEqual($tag->getCommit(), $commit);
        $this->assertIsA($tag->getTag(), '\\gihp\\Ref\\Tag');
        $this->assertIsA($tag->getTag()->getObject(), '\\gihp\\Object\\AnnotatedTag');
        $this->assertEqual($tag->getTag()->getObject()->getObject(), $commit);
    }

}
