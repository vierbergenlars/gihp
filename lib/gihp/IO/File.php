<?php

namespace gihp\IO;

class File implements IOInterface
{
    private $path;
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function addBranch(\gihp\Branch $branch)
    {
    }

    public function removeBranch(\gihp\Branch $branch)
    {
    }

    public function readBranches()
    {
    }

    public function addRef(\gihp\Ref\Reference $ref)
    {
    }

    public function removeRef(\gihp\Ref\Reference $ref)
    {
    }

    public function readRefs()
    {
    }

    public function addObject(\gihp\Object\Internal $object)
    {
        $hash = $object->getSHA1();
        $dir = $this->path.'/.git/objects/'.substr($hash,0,2);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $dir.'/'.substr($hash,2);
        $encoded = gzcompress($object);

        return file_put_contents($path, $encoded);
    }

    public function removeObject(\gihp\Object\Internal $object)
    {
    }

    public function readObjects()
    {
    }

    public function readObject($sha1)
    {
    }

    public function moveHead(\gihp\Ref\SymbolicReference $ref)
    {
    }

    public function readHead()
    {
    }

    public function gc()
    {
    }
}
