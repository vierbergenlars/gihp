<?php

namespace gihp\Object;

use gihp\Defer\Object as Defer;
use gihp\Defer\Reference;
use gihp\Defer\Loader as DLoader;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;

/**
 * A git tree
 *
 * A tree contains references to blobs and other trees.
 * It also records file modes.
 */
class Tree extends Internal implements WritableInterface
{
    /**
     * Directory type
     */
    const DIR = '040000';
    /**
     * File type not executable
     */
    const FILE_NOEXEC = '100644';
    /**
     * File type not executable, writable by group
     */
    const FILE_NOEXEC_GROUPW = '100664';
    /**
     * File type executable
     */
    const FILE_EXEC = '100755';
    /**
     * Symbolic link (not implemented)
     */
    const SYMLINK = '120000';
    /**
     * Git link (not implemented)
     */
    const GITLINK = '160000';

    /**
     * A hashmap of all objects, ordered by their sha
     * @var array
     */
    protected $objects = array();
    /**
     * A hashmap of all object names, ordered by their name
     * @var array
     */
    protected $names = array();
    /**
     * Creates a new, empty tree
     */
    public function __construct()
    {
        parent::__construct(parent::TREE);
    }

    /**
     * Adds an object to the tree
     * @param string   $name   The name of the object
     * @param Internal $object A tree object or a Blob
     * @param string   $mode   When the object is a blob, the mode of the file as a string
     */
    public function addObject($name, Internal $object, $mode = '644')
    {
        if ($object instanceof self) {
            $mode = self::DIR;
        } elseif ($object instanceof Blob) {
            switch ($mode) {
                case '644':
                case '664':
                case '755':
                    $mode = '100'.$mode;
                    // no break
                case '100644':
                case '100664':
                case '700755':
                    break;
                default:
                    throw new \LogicException('Invalid file mode');
            }
        }
        $this->objects[$object->getSHA1()] = array($object, $mode, $name);
        $this->names[$name] = $object->getSHA1();
    }

    /**
     * Removes an object from the tree
     * @param string $sha1 The SHA of the object
     */
    public function removeObject($sha1)
    {
        $name = $this->objects[$sha1][2];
        unset($this->names[$name]);
        unset($this->objects[$sha1]);
    }

    /**
     * Gets the SHA of an object
     * @param  string      $name The name of the object
     * @return string|null The SHA of the object with that name or null if the name is not found.
     */
    public function getObjectSHA1ByName($name)
    {
        return (isset($this->names[$name])?$this->names[$name]:null);
    }

    /**
     * Gets the mode of an object
     * @param  string $sha1 The SHA of the object
     * @return string The mode of the object
     */
    public function getObjectMode($sha1)
    {
        if (!isset($this->objects[$sha1][1])) {
            throw new \RuntimeException('SHA not found in this tree');
        }

        return $this->objects[$sha1][1];
    }

    /**
     * Gets the object
     * @param  string    $sha1 The SHA of the object
     * @return Tree|Blob The tree or the blob belonging to that SHA
     */
    public function getObject($sha1)
    {
        if (!isset($this->objects[$sha1][0])) {
            throw new \RuntimeException('SHA not found in this tree');
        }

        return $this->objects[$sha1][0];
    }

    /**
     * Converts the object to a raw string
     * @return string The raw data-stream that represents the tree
     */
    public function __toString()
    {
        $this->setData('');
        foreach ($this->objects as $object) {
            $this->appendData($object[1].' '.$object[2].chr(0).pack('H*', $object[0]->getSHA1()));
        }

        return parent::__toString();
    }

    public function write(IOInterface $io)
    {
        $io->addObject($this);
        foreach ($this->objects as $object) {
            $object[0]->write($io);
        }
    }

    /**
     * Imports the tree object
     * @param  Loader $loader The object to load embedded references
     * @param  string $tree   The raw tree data
     * @return Tree   The tree represented by the raw object
     */
    public static function import(DLoader $loader, $tree)
    {
        $l = strlen($tree);
        $objects = array();
        $names = array();
        for ($i=0; $i < $l;) {
            $mode = '';
            do {
                if($tree[$i] === chr(32)) break;
                $mode.=$tree[$i];
            } while (++$i);
            $i++;
            $filename = '';
            do {
                if($tree[$i] === "\0") break;
                $filename.=$tree[$i];
            } while (++$i);
            $i++;
            $bin_sha = substr($tree, $i, 20);
            $i+=20;
            $sha = unpack('H*', $bin_sha);
            $sha1 = $sha[1];
            $objects[$sha1] = array(new Reference($loader, $sha1), $mode, $filename);
            $names[$filename] = $sha1;
        }

        return Defer::defer(array('objects'=>$objects,'names'=>$names), __CLASS__);
    }
}
