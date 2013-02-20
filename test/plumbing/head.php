<?php

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
