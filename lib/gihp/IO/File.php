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
    }

    function removeRef(\gihp\Ref\Reference $ref) {
    }

    function readRefs() {
    }

    function addObject(\gihp\Internal\Object $object) {
        $hash = $object->getSHA1();
        $dir = $this->path.'/.git/objects/'.substr($hash,0,2);
        if(!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $dir.'/'.substr($hash,2);
        $encoded = gzcompress($object);
        return file_put_contents($path, $encoded);
    }

    function removeObject(\gihp\Internal\Object $object) {
    }

    function readObjects() {
    }

    function readObject($sha1) {
    }

    function moveHead(\gihp\Ref\SymbolicReference $ref) {
    }

    function readHead() {
    }

    function gc() {
    }
}
