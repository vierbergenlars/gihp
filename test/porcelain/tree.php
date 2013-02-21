<?php

namespace test\porcelain;

class tree extends \UnitTestCase
{
    private $io;
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

    public function __construct()
    {
        $this->io = new \gihp\IO\File(__DIR__.'/../repo');
    }
    public function testNewTree()
    {
        $tree = new \gihp\Tree();
        // Adding a file
        $tree->addFile('test', 'This is a test file...');
        $this->assertEqual($tree->getFile('test'), 'This is a test file...');
        // Can't make a dir in a file
        $this->expectExceptionInClosure(function() use ($tree) {
           $tree->addFile('test/abc', 'Should fail!');
           }, '\\RuntimeException');
        // And can't list its files
        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->dirList('test');
            }, '\\RuntimeException');

        // Adding a dir with a file
        $tree->addFile('dir/file', 'File in a dir :D');
        $this->assertEqual($tree->getFile('dir/file'), 'File in a dir :D');
        $this->assertEqual($tree->dirList(), array('test', 'dir'));
        $this->assertEqual($tree->dirList('dir'), array('file'));

        // Moving a file to a dir
        $tree->moveFile('test', 'dir/test2');
        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->getFile('test');
        }, '\\RuntimeException');

        $this->assertEqual($tree->getFile('dir/test2'), 'This is a test file...');

        // Moving a dir
        $tree->moveFile('dir', 'dir2');
        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->getFile('dir/file');
        }, '\\RuntimeException');
        $this->assertEqual($tree->getFile('dir2/file'), 'File in a dir :D');
        $this->assertEqual($tree->getFile('dir2/test2'), 'This is a test file...');

        // Moving a dir to its own subdir (works for now)
        /*$this->expectExceptionInClosure(function() use ($tree) {
            var_dump($tree);
            $tree->moveFile('dir2', 'dir2/dir');
            var_dump($tree);
        }, '\\RuntimeException');*/

        // Removing a dir
        $tree->rmFile('dir2');
        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->getFile('dir2/file');
            }, '\\RuntimeException');

        // Various tests

        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->rmFile('nofile');
            }, '\\RuntimeException');

        $this->expectExceptionInClosure(function() use ($tree) {
            $tree->dirList('blah');
        }, '\\RuntimeException');

        $this->assertEqual($tree->dirList(), array());
    }

    public function testWriteAndReloadTree()
    {
        $tree = new \gihp\Tree;

        $tree->addFile('a random regexp', '^.*$');
        $tree->addFile('dir/file', 'Just a file. What ya lookin\' at?');

        $tree->write($this->io);

        $sha1 = $tree->getTree()->getSHA1();
        $this->io->clearCache();

        $bare_tree = $this->io->readObject($sha1);
        $decorated_tree = new \gihp\Tree($bare_tree);

        $this->assertEqual($decorated_tree->dirList(), array('a random regexp', 'dir'));
        $this->assertEqual($decorated_tree->dirList('dir'), array('file'));
        $this->assertEqual($decorated_tree->getFile('a random regexp'), '^.*$');
        $this->assertEqual($decorated_tree->getFile('dir/file'), 'Just a file. What ya lookin\' at?');

    }
}
