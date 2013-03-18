<?php

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
