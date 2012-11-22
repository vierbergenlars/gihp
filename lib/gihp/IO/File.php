<?php

namespace gihp\IO;

use gihp\IO\File\Packfile;
use gihp\IO\File\Packref;
use gihp\IO\File\RecursiveFileIterator;

/**
 * Disk IO. Works well with real git repositories
 */
class File implements IOInterface {
    private $path;
    function __construct($path) {
        $this->path = $path;
        new Packref($this->path.'/.git');
    }

    function addRef(\gihp\Ref\Reference $ref) {
        $file = $this->path.'/.git/refs/'.$ref->getPath();
        $dir = dirname($file);
        if(!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if(is_file($file)) {
            throw new \RuntimeException('Ref already exists');
        }
        file_put_contents($file, $ref);
    }

    function removeRef(\gihp\Ref\Reference $ref) {
        $file = $this->path.'/.git/refs/'.$ref->getPath();
        if(is_file($file)) {
            unlink($file);
        }
        else {
            throw new \RuntimeException('Ref not found');
        }
    }

    function readRefs() {
        $fsit = new RecursiveFileIterator($this->path.'/.git/refs', \FilesystemIterator::UNIX_PATHS|\FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($fsit);
        $refs = array();
        foreach($it as $file) {
            if(!is_file($file)) continue;
            $refs[] = str_replace($this->path.'/.git/refs/', '', $file);
        }
        return $refs;
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
        if(file_exists($path)) return true;
        $encoded = gzcompress($object);
        return file_put_contents($path, $encoded);
    }

    function removeObject(\gihp\Object\Internal $object) {
        $hash = $object->getSHA1();
        $file = $this->path.'/.git/objects/'.substr($hash,0,2).'/'.substr($hash, 2);
        if(!is_file($file)) return;
        return unlink($file);
    }

    function readObject($sha1) {
        $dir = $this->path.'/.git/objects/';
        static $packfile=null;
        if(!$packfile)
            $packfile = new Packfile($dir);
        $decoded = $packfile->getObject($sha1);
        $loader = new \gihp\Object\Loader($this);
        return \gihp\Object\Internal::import($loader, $decoded);
    }

    function moveHead(\gihp\Symref\SymbolicReference $ref) {
        $file = $this->path.'/.git/HEAD';
        file_put_contents($file, $ref);
    }

    function readHead() {
        $file = $this->path.'/.git/HEAD';
        if(!is_file($file)) {
            throw new \RuntimeException('HEAD not found');
        }
        $data = file_get_contents($file);
        $loader = new \gihp\Symref\Loader($this);
        return \gihp\Symref\SymbolicReference::import($loader, $data);
    }

    function gc() {
    }
}
