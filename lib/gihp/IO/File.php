<?php

namespace gihp\IO;

class File implements IOInterface {
    private $path;
    function __construct($path) {
        $this->path = $path;
    }

    function addBranch(\gihp\Branch $branch) {

    }

    function removeBranch(\gihp\Branch $branch) {

    }

    function readBranches() {

    }

    function addRef(\gihp\Ref\Reference $ref) {
        $file = $this->path.'/.git/refs/'.$ref->getPath();
        if(is_file($file)) {
            throw new \RuntimeException('Ref already exists');
        }
        file_put_contents($file, $ref);
    }

    function removeRef(\gihp\Ref\Reference $ref) {
    }

    function readRefs() {
    }

    function readRef($path) {
        $file = $this->path.'/.git/refs/'.$path;
        if(!is_file($file)) {
            throw new \RuntimeException('Ref not found');
        }
        $contents = file_get_contents($file);
        $loader = new \gihp\Ref\Loader($this);
        return \gihp\Ref\Reference::import($loader, $path."\0".$contents);
    }

    function addObject(\gihp\Object\Internal $object) {
        $hash = $object->getSHA1();
        $dir = $this->path.'/.git/objects/'.substr($hash,0,2);
        if(!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $dir.'/'.substr($hash,2);
        $encoded = gzcompress($object);
        return file_put_contents($path, $encoded);
    }

    function removeObject(\gihp\Object\Internal $object) {
    }

    function readObjects() {
    }

    function readObject($sha1) {
        $dir = $this->path.'/.git/objects/'.substr($sha1,0,2);
        $path = $dir.'/'.substr($sha1,2);
        if(!is_file($path)) {
            throw new \RuntimeException('Object not found');
        }
        $decoded = gzuncompress(file_get_contents($path));
        $loader = new \gihp\Object\Loader($this);
        return \gihp\Object\Internal::import($loader, $decoded);
    }

    function moveHead(\gihp\Ref\SymbolicReference $ref) {
    }

    function readHead() {
    }

    function gc() {
    }
}
