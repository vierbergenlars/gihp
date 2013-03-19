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

class tree extends \UnitTestCase
{
    private $io;
    private $tree_sha;
    public function __construct()
    {
        $this->io = new File(__DIR__.'/../repo');
    }

    public function testTreeCreation()
    {
        $tree = new \gihp\Object\Tree;

        // Empty tree
        $this->assertEqual($tree->getNamesAndHashes(), array());

        $ex = false;
        try {
            $tree->getObject(sha1('random string'));
        } catch (\RuntimeException $e) {
            $ex = true;
        }

        $this->assertTrue($ex, 'Expected RuntimeException from Tree::getObject()');

        $ex = false;
        try {
            $tree->getObjectMode(sha1('random string'));
        } catch (\RuntimeException $e) {
            $ex = true;
        }

        $this->assertTrue($ex, 'Expected RuntimeException from Tree::getObjectMode()');

        $this->assertNull($tree->getObjectSHA1ByName('no file'));

        // Tree with one blob
        $blob = new \gihp\Object\Blob('a nice file...');
        $blob_sha = $blob->getSHA1();
        $tree->addObject('file', $blob);

        $this->assertEqual($tree->getNamesAndHashes(), array('file'=>$blob_sha));
        $this->assertEqual($tree->getObjectSHA1ByName('file'), $blob_sha);
        $this->assertIdentical($tree->getObject($blob_sha), $blob);
        $this->assertEqual($tree->getObjectMode($blob_sha), \gihp\Object\Tree::FILE_NOEXEC);

        // Empty tree
        $tree->removeObject($blob_sha);

        $this->assertEqual($tree->getNamesAndHashes(), array());
        $this->assertNull($tree->getObjectSHA1ByName('file'));

        // Tree with one subtree
        $subtree = new \gihp\Object\Tree;
        $subtree->addObject('file', $blob);
        $tree->addObject('dir', $subtree);
        $subtree_sha = $subtree->getSHA1();

        $this->assertEqual($tree->getNamesAndHashes(), array('dir'=>$subtree_sha));
        $this->assertEqual($tree->getObjectSHA1ByName('dir'), $subtree_sha);
        $this->assertIdentical($tree->getObject($subtree_sha), $subtree);
        $this->assertEqual($tree->getObjectMode($subtree_sha), \gihp\Object\Tree::DIR);

        $this->tree_sha = $tree->getSHA1();

        $tree->write($this->io);

    }

    public function testTreeLoading()
    {
        $this->io->clearCache();
        $tree = $this->io->readObject($this->tree_sha);

        $subtree_sha = $tree->getObjectSHA1ByName('dir');
        $this->assertNotNull($subtree_sha);

        $subtree = $tree->getObject($subtree_sha);
        $this->assertEqual($tree->getObjectMode($subtree_sha    ), \gihp\Object\Tree::DIR);
        $this->assertIsA($subtree, 'gihp\\Object\\Tree');

        $this->assertNotNull($subtree->getObjectSHA1ByName('file'));
    }
}
