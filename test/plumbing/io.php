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

class io extends \UnitTestCase
{
    private $io;
    public function __construct()
    {
        system('bash "'.__DIR__.'/../setup.sh"');
        $this->io = new File(__DIR__.'/../repo');
    }

    /**
     * Checks if an array contains specific values
     * @param  array        $arr
     * @param  array|string $val
     * @return boolean
     */
    public function contains($arr, $val)
    {
        if (is_array($val)) {
            foreach ($val as $v) {
                if(array_search($v, $arr) === false) return false;
            }

            return true;
        } else {
            return (array_search($val, $arr) !== false);
        }
    }

    /**
     * Checks if an array contains only specific values
     * @param  array        $arr
     * @param  array|string $val
     * @return bool
     */
    public function containsOnly($arr, $val)
    {
        if(!is_array($val)) $val = array($val);

        return count(array_diff($arr, $val)) == 0;
    }

    public function testContainsFunctions()
    {
        $this->assertTrue($this->contains(array('a'), 'a'));
        $this->assertTrue($this->contains(array('a', 'b'), 'b'));
        $this->assertTrue($this->contains(array('a', 'b', 'c'), array('c','a')));
        $this->assertFalse($this->contains(array('a'), 'b'));
        $this->assertFalse($this->contains(array('a', 'n'), array('a', 'b')));
        $this->assertFalse($this->contains(array('a', 'b', 'c'), array('b', 'c', 'a', 'd')));

        $this->assertTrue($this->containsOnly(array('a'), 'a'));
        $this->assertTrue($this->containsOnly(array('a', 'b'), array('b', 'a')));
        $this->assertFalse($this->containsOnly(array('a', 'b'), 'b'));
        $this->assertFalse($this->containsOnly(array('a', 'b','c'), array('c', 'a')));
    }

    public function testRefIO()
    {
        $io = $this->io;
        $refs = $io->readRefs();
        $expected_refs = array('heads/master', 'heads/tests', 'tags/v0.0.1', 'tags/v0.0.2', 'tags/test-2');
        $this->assertTrue($this->containsOnly($refs, $expected_refs));

        $tag = $io->readRef('tags/v0.0.1');
        $io->removeRef($tag);
        $io->clearCache();
        unset($expected_refs[2]);
        $refs = $io->readRefs();
        $this->assertTrue($this->containsOnly($refs, $expected_refs));

        $io->addRef($tag);
        $io->clearCache();
        $expected_refs[] = 'tags/v0.0.1';
        $refs = $io->readRefs();
        $this->assertTrue($this->containsOnly($refs, $expected_refs));
    }

    public function testObjectsIO()
    {
        $io = $this->io;
        $master = $io->readRef('heads/master');

        $sha = $master->getCommit()->getSHA1();

        $commit = $io->readObject($sha);
        $commit->getSHA1();

        $this->assertIdentical($master->getCommit(), $commit);

        $tree = $commit->getTree();

        $tree_sha = $tree->getSHA1();
        $io->removeObject($tree);

        $io->clearCache();
        $ex = false;
        try {
            $io->readObject($tree_sha);
        } catch (\RuntimeException $e) {
            $ex=true;
        }

        $this->assertTrue($ex, 'Expected RuntimeException');

        $io->addObject($tree);
        $io->clearCache();
        $io->readObject($tree_sha);
    }

    public function testHeadIO()
    {
        $io = $this->io;

        $head = $io->readHead();

        $commit = $head->getCommit();

        $parents = $commit->getParents();

        $io->moveHead(new \gihp\Symref\SymbolicReference($parents[0]));

        $io->clearCache();

        $new_head = $io->readHead();

        $new_commit = $new_head->getCommit();
        $new_commit->getSHA1();

        $this->assertIdentical($parents[0], $new_commit);

        $io->moveHead($head);
    }
}
