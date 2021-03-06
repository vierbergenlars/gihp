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

class commit extends \UnitTestCase
{
    private $io;
    private $commit_sha;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }
    public function testCreateCommit()
    {
        $root_tree = new \gihp\Object\Tree;
        $person = new \gihp\Metadata\Person('gihp', 'git@gihp');
        $commit = new \gihp\Object\Commit('New commit', $root_tree, $person);

        $this->verifyBase($commit);
        $this->assertEqual($commit->getAuthorTime(), new \DateTime);
        $this->assertIdentical($commit->getCommitter(), $commit->getAuthor());
        $this->assertIdentical($commit->getAuthorTime(), $commit->getCommitTime());
        $this->assertIdentical($commit->getTree(), $root_tree);

        $commit2 = new \gihp\Object\Commit('New commit', $root_tree, $person, new \DateTime('@0'), $commit);

        $this->verifyBase($commit2);
        $this->assertEqual($commit2->getAuthorTime()->getTimestamp(), 0);
        $this->assertEqual($commit2->getParents(), array($commit));
        $this->assertIdentical($commit2->getCommitter(), $commit2->getAuthor());
        $this->assertIdentical($commit2->getAuthorTime(), $commit2->getCommitTime());
        $this->assertIdentical($commit2->getTree(), $root_tree);

        $commit2->setCommitter(new \gihp\Metadata\Person('gihp-commit', 'commit@gihp'));

        $this->verifyBase($commit2);

        $this->assertEqual($commit2->getCommitter(), new \gihp\Metadata\Person('gihp-commit', 'commit@gihp'));
        $this->assertNotEqual($commit2->getAuthor(), $commit2->getCommitter());
        $this->assertIdentical($commit2->getCommitTime(), $commit2->getAuthorTime());

        $commit2->setCommitter(new \gihp\Metadata\Person('gihp-commit', 'commit@gihp'), new \DateTime('@10'));

        $this->assertEqual($commit2->getCommitter(), new \gihp\Metadata\Person('gihp-commit', 'commit@gihp'));
        $this->assertNotEqual($commit2->getAuthor(), $commit2->getCommitter());
        $this->assertEqual($commit2->getCommitTime()->getTimestamp(), 10);
        $this->assertNotEqual($commit2->getAuthorTime(), $commit2->getCommitTime());

        $commit2->write($this->io);
        $this->commit_sha = $commit2->getSHA1();
    }

    public function verifyBase(\gihp\Object\Commit $commit)
    {
        $this->assertEqual($commit->getMessage(), 'New commit');
        $this->assertIsA($commit->getAuthorTime(), '\\DateTime');
        $this->assertIsA($commit->getAuthor(), '\\gihp\\Metadata\\Person');
        $this->assertEqual($commit->getAuthor(), new \gihp\Metadata\Person('gihp', 'git@gihp'));
        $this->assertIsA($commit->getCommitter(), '\\gihp\\Metadata\\Person');
        $this->assertIsA($commit->getCommitTime(), '\\DateTime');
        $this->assertIsA($commit->getTree(), '\\gihp\\Object\\Tree');
    }

    public function testCommitLoading()
    {
        $commit2 = $this->io->readObject($this->commit_sha);

        $this->verifyBase($commit2);
        $this->assertEqual($commit2->getSHA1(), $this->commit_sha);
        $this->assertNotEqual($commit2->getAuthor(), $commit2->getCommitter());
        $this->assertEqual($commit2->getAuthorTime()->getTimestamp(), 0);
        $this->assertEqual($commit2->getCommitTime()->getTimestamp(), 10);

        $parents = $commit2->getParents();

        $commit = $parents[0];
        $this->verifyBase($commit);
        $this->assertEqual($commit->getAuthor(), $commit2->getAuthor());
    }

}
