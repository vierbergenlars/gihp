<?php

namespace test\porcelain;

class branch extends \UnitTestCase
{
        private function ex(\Closure $fn, $ex)
    {
        $code = <<<'END'
$thrown = false;
try {
    $fn();
} catch (%s $e) {
    $thrown = true;
}
return $thrown;
END;
        try {
            return eval(sprintf($code, $ex));
        } catch (\Exception $e) {
            throw $e;
        }

    }

    private function expectExceptionInClosure(\Closure $fn, $ex, $msg ='%s')
    {
        if($this->ex($fn, $ex))
            $this->pass(sprintf($msg,'Exception '.$ex.' was thrown'));
        else
            $this->fail(sprintf($msg, 'Expected exception '.$ex.' got none'));
    }

    public function testCreateBranchFromCommit()
    {
        $io = new \gihp\IO\File(__DIR__.'/../repo');
        $latest_commit = $io->readHead()->getCommit();

        $branch = new \gihp\Branch('new_branch', $latest_commit);

        $this->assertEqual($branch->getName(), 'new_branch');
        $this->assertEqual($branch->getHeadCommit(), $latest_commit);
        // The getTree() method from branch wraps the gihp\Object\Tree in a gihp\Tree
        $t =  $latest_commit->getTree();
        $t->getSHA1();
        $this->assertEqual($branch->getTree()->getTree(),$t);

        $tree = $branch->getTree();
        $tree->addFile('blah', 'blah blah!');

        $new_commit = $branch->commit('New commit', $tree, new \gihp\Metadata\Person('gihp-committer', 'commit@gihp'));

        $this->assertEqual($branch->getHeadCommit(), $new_commit);
        $this->assertEqual($branch->getHeadCommit()->getParents(), array($latest_commit));
        $this->assertEqual($branch->getTree(), $tree);

        $branch->write($io);

    }

    public function testCreateEmptyBranch()
    {
        $branch = new \gihp\Branch('clean_branch');
        $this->assertEqual($branch->getName(), 'clean_branch');
        $this->assertNull($branch->getTree());
        $this->assertNull($branch->getHeadCommit());
        $this->assertIdentical($branch->getHistory(), array());

        $this->expectExceptionInClosure(function() use ($branch) {
            $branch->write(new \gihp\IO\File(__DIR__.'/../repo'));
        }, '\\LogicException');

        $commit = $branch->commit('First commit', new \gihp\Tree, new \gihp\Metadata\Person('gihp-committer', 'committer@gihp'));

        $this->assertEqual($branch->getTree(), new \gihp\Tree);
        $this->assertEqual($branch->getHistory(), array($commit));
        $this->assertEqual($branch->getHeadCommit(), $commit);
        $this->assertEqual($commit->getParents(), array());

        $branch->write(new \gihp\IO\File(__DIR__.'/../repo'));
    }
}
