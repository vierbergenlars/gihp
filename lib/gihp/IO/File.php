<?php

namespace gihp\IO;

use gihp\IO\File\Packfile;
use gihp\IO\File\Packref;
use gihp\IO\File\RecursiveFileIterator;

/**
 * Disk IO. Works well with real git repositories
 */
class File implements IOInterface
{
    private $path;
    private $bare;
    private $packfile;
    public function __construct($path, $bare = null)
    {
        if ($bare === null && is_dir($path.'/.git')) {
            $bare = false;
        } elseif ($bare === null) {
            $bare = true;
        }
        $this->bare = $bare;
        if ($this->bare) {
            $this->path = $path;
        } else {
            $this->path = $path.'/.git';
        }
        new Packref($this->path);
    }

    public function addRef(\gihp\Ref\Reference $ref)
    {
        list($name, $type, $ref) = \gihp\Parser\File::exportRef($ref);
        $file = $this->path.'/refs/'.$type.'s/'.$name;
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
        list($name, $type, $ref) = \gihp\Parser\File::exportRef($ref);
        $file = $this->path.'/refs/'.$type.'s/'.$name;
        if (is_file($file)) {
            unlink($file);
        } else {
            throw new \RuntimeException('Ref not found');
        }
    }

    public function readRefs()
    {
        $fsit = new RecursiveFileIterator($this->path.'/refs', \FilesystemIterator::UNIX_PATHS|\FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($fsit);
        $refs = array();
        foreach ($it as $file) {
            if(!is_file($file)) continue;
            $refs[] = str_replace($this->path.'/refs/', '', $file);
        }

        return $refs;
    }

    public function readRef($path)
    {
        $file = $this->path.'/refs/'.$path;

        if (!is_file($file)) {
            throw new \RuntimeException('Ref not found');
        }
        $contents = file_get_contents($file);
        $loader = new \gihp\Object\Loader($this);

        return \gihp\Parser\File::importRef($loader, $contents, $path);
    }

    public function addObject(\gihp\Object\Internal $object)
    {
        $hash = $object->getSHA1();
        $dir = $this->path.'/objects/'.substr($hash,0,2);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $path = $dir.'/'.substr($hash,2);
        if(file_exists($path)) return true;
        $object = \gihp\Parser\File::exportObject($object);
        $encoded = gzcompress($object);

        return file_put_contents($path, $encoded);
    }

    public function removeObject(\gihp\Object\Internal $object)
    {
        $hash = $object->getSHA1();
        $file = $this->path.'/objects/'.substr($hash,0,2).'/'.substr($hash, 2);
        if(!is_file($file)) return;

        return unlink($file);
    }

    public function readObject($sha1)
    {
        $dir = $this->path.'/objects/';
        if(!$this->packfile)
            $this->packfile = new Packfile($dir);
        $decoded = $this->packfile->getObject($sha1);
        $loader = new \gihp\Object\Loader($this);

        return \gihp\Parser\File::importObject($loader, $decoded, $sha1);
    }

    public function moveHead(\gihp\Symref\SymbolicReference $ref)
    {
        $file = $this->path.'/HEAD';
        $ref = \gihp\Parser\File::exportSymRef($ref);
        file_put_contents($file, $ref);
    }

    public function readHead()
    {
        $file = $this->path.'/HEAD';
        if (!is_file($file)) {
            throw new \RuntimeException('HEAD not found');
        }
        $data = file_get_contents($file);

        return \gihp\Parser\File::importSymRef($this, $data);
    }

    /**
     * Clears all caches on IO level.
     *
     * Only useful for testing, don't use this in production code!
     */
    public function clearCache()
    {
        if($this->packfile)
            $this->packfile->clearCache();
    }

    public function gc()
    {
    }

    public function init()
    {
        self::rrmdir($this->path); // Reinitialize the repo if necessary
        mkdir($this->path);
        file_put_contents($this->path.'/HEAD', 'ref: refs/heads/master');
        $config = <<<CONF
[core]
    repositoryformatversion = 0
    filemode = false
    bare = false
    logallrefupdates = true
    symlinks = false
    ignorecase = true
CONF;
        $config_bare = <<<CONF
[core]
    repositoryformatversion = 0
    filemode = false
    bare = true
    symlinks = false
    ignorecase = true
CONF;
        file_put_contents($this->path.'/config', $this->bare?$config_bare:$config);
        file_put_contents($this->path.'/description', 'Unnamed repository; edit this file \'description\' to name the repository.');
        mkdir($this->path.'/branches');
        mkdir($this->path.'/hooks');
        mkdir($this->path.'/info');
        file_put_contents($this->path.'/info/exclude', '');
        mkdir($this->path.'/objects');
        mkdir($this->path.'/objects/info');
        mkdir($this->path.'/objects/pack');
        mkdir($this->path.'/refs');
        mkdir($this->path.'/refs/heads');
        mkdir($this->path.'/refs/tags');
    }

    /**
     * Recursively removes the contents of the directory
     * @param  string  $dir
     * @return boolean
     */
    private static function rrmdir($dir)
    {
        if(!is_dir($dir))

            return false;
        $files = scandir($dir);
        foreach ($files as $file) {
            if($file == '.' || $file == '..') continue;
            if (is_dir($dir.'/'.$file)) {
                self::rrmdir($dir.'/'.$file);
            } else {
                unlink($dir.'/'.$file);
            }
        }

        return rmdir($dir);
    }
}
