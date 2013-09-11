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
