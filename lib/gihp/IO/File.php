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
        $file = $this->path.'/.git/refs/'.$ref->getPath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (is_file($file)) {
            throw new \RuntimeException('Ref already exists');
        }
        file_put_contents($file, $ref);
    }

    public function removeRef(\gihp\Ref\Reference $ref)
    {
        $file = $this->path.'/.git/refs/'.$ref->getPath();
        if (is_file($file)) {
            unlink($file);
        } else {
            throw new \RuntimeException('Ref not found');
        }
    }

    public function readRefs()
    {
        $fsit = new RecursiveFileIterator($this->path.'/.git/refs', \FilesystemIterator::UNIX_PATHS|\FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($fsit);
        $refs = array();
        foreach ($it as $file) {
            if(!is_file($file)) continue;
            $refs[] = str_replace($this->path.'/.git/refs/', '', $file);
        }

        return $refs;
    }

    public function readRef($path)
    {
        $file = $this->path.'/.git/refs/'.$path;

        if (!is_file($file)) {
            throw new \RuntimeException('Ref not found');
        }
        $contents = file_get_contents($file);
        $loader = new \gihp\Ref\Loader($this);

        return \gihp\Ref\Reference::import($loader, $path."\0".$contents);
    }

    public function addObject(\gihp\Object\Internal $object)
    {
        $hash = $object->getSHA1();
        $dir = $this->path.'/.git/objects/'.substr($hash,0,2);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $dir.'/'.substr($hash,2);
        if(file_exists($path)) return true;
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
        $dir = $this->path.'/.git/objects/'.substr($sha1,0,2);
        $path = $dir.'/'.substr($sha1,2);
        if (!is_file($path)) {
            throw new \RuntimeException('Object not found');
        }
        $decoded = gzuncompress(file_get_contents($path));
        $loader = new \gihp\Object\Loader($this);

        return \gihp\Object\Internal::import($loader, $decoded);
    }

    public function moveHead(\gihp\Symref\SymbolicReference $ref)
    {
        $file = $this->path.'/.git/HEAD';
        file_put_contents($file, $ref);
    }

    public function readHead()
    {
        $file = $this->path.'/.git/HEAD';
        if (!is_file($file)) {
            throw new \RuntimeException('HEAD not found');
        }
        $data = file_get_contents($file);
        $loader = new \gihp\Symref\Loader($this);

        return \gihp\Symref\SymbolicReference::import($loader, $data);
    }

    public function gc()
    {
    }
}
