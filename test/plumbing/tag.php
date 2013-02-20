<?php

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
