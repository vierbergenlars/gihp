<?php

namespace test\plumbing;

use gihp\IO\File;

class annotatedtag extends \UnitTestCase
{
    private $io;
    private $tag_sha;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }

    public function testTagCreation()
    {
        $person = new \gihp\Metadata\Person('gihp', 'git@gihp');
        $tagger = new \gihp\Metadata\Person('gihp-tagger', 'tags@gihp');
        $now = new \DateTime;
        $commit = new \gihp\Object\Commit('New commit', new \gihp\Object\Tree, $person);
        $tag = new \gihp\Object\AnnotatedTag('tag', 'An annotated tag', $tagger, $now , $commit);

        $this->assertEqual($tag->getName(), 'tag');
        $this->assertIsA($tag->getAuthor(), 'gihp\\Metadata\\Person');
        $this->assertEqual($tag->getAuthor(), $tagger);
        $this->assertEqual($tag->getMessage(), 'An annotated tag');
        $this->assertIsA($tag->getDate(), '\\DateTime');
        $this->assertEqual($tag->getDate(), $now);
        $this->assertIsA($tag->getObject(), 'gihp\\Object\\Commit');
        $this->assertEqual($tag->getObject(), $commit);

        $tag->write($this->io);
        $this->tag_sha[0] = $tag->getSHA1();

        $tag2 = new \gihp\Object\AnnotatedTag('tag2', 'Annotated tag to a tree', $tagger, $now, new \gihp\Object\Blob('Hi! I\'m an annotated blob'));

        $this->assertIsA($tag2->getObject(), 'gihp\\Object\\Blob');
        $this->assertEqual($tag2->getObject()->getData(), 'Hi! I\'m an annotated blob');

        $tag2->write($this->io);
        $this->tag_sha[1] = $tag2->getSHA1();
    }

    public function testTagLoading()
    {
        $tag = $this->io->readObject($this->tag_sha[0]);

        $this->assertEqual($tag->getSHA1(), $this->tag_sha[0]);

        $this->assertEqual($tag->getName(), 'tag');
        $this->assertIsA($tag->getAuthor(), 'gihp\\Metadata\\Person');
        $this->assertEqual($tag->getAuthor(), new \gihp\Metadata\Person('gihp-tagger', 'tags@gihp'));
        $this->assertEqual($tag->getMessage(), 'An annotated tag');
        $this->assertIsA($tag->getDate(), '\\DateTime');
        $this->assertIsA($tag->getObject(), 'gihp\\Object\\Commit');

        $tag2 = $this->io->readObject($this->tag_sha[1]);

        $this->assertIsA($tag2->getObject(), 'gihp\\Object\\Blob');
        $this->assertEqual($tag2->getObject()->getData(), 'Hi! I\'m an annotated blob');
    }
}
