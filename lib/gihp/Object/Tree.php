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
     * A hashmap of all object modes, ordered by their sha
     * @var array
     */
    protected $modes = array();
    /**
     * A hashmap of all object names, ordered by their sha
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
                break;
                default:
                    throw new \LogicException('Invalid file mode');
            }
        }
        $this->objects[$object->getSHA1()] = $object;
        $this->modes[$object->getSHA1()] = $mode;
        $this->names[$object->getSHA1()] = $name;
    }

    /**
     * Gets the SHA of an object
     * @param  string      $name The name of the object
     * @return string|null The SHA of the object with that name or null if the name is not found.
     */
    public function getObjectSHA1ByName($name)
    {
        return (($result = array_search($name, $this->names))?$result:null);
    }

    /**
     * Gets the mode of an object
     * @param  string $sha1 The SHA of the object
     * @return string The mode of the object
     */
    public function getObjectMode($sha1)
    {
        if (!isset($this->modes[$sha1])) {
            throw new \RuntimeException('SHA not found in this tree');
        }

        return $this->modes[$sha1];
    }

    /**
     * Gets the object
     * @param  string    $sha1 The SHA of the object
     * @return Tree|Blob The tree or the blob belonging to that SHA
     */
    public function getObject($sha1)
    {
        if (!isset($this->objects[$sha1])) {
            throw new \RuntimeException('SHA not found in this tree');
        }

        return $this->objects[$sha1];
    }

    /**
     * Converts the object to a raw string
     * @return string The raw data-stream that represents the tree
     */
    public function __toString()
    {
        $this->setData('');
        foreach ($this->objects as $sha=>$object) {
            $this->appendData($this->modes[$sha].' '.$this->names[$sha].chr(0).pack('H*', $sha));
        }

        return parent::__toString();
    }

    public function write(IOInterface $io)
    {
        $io->addObject($this);
        foreach ($this->objects as $object) {
            $object->write($io);
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
        $modes = array();
        $names = array();
        for ($i=0; $i < $l;$i++) {
            $mode = substr($tree, $i, 6);
            $i+=6; //Also a space after it
            $filename = '';
            while ($i++) {
                if($tree[$i] === "\0") break;
                $filename.=$tree[$i];
            }
            $i++;
            $bin_sha = substr($tree, $i, 20);
            $i+=19;
            $sha = unpack('H*', $bin_sha);
            $sha1 = $sha[1];

            $objects[$sha1] = new Reference($loader, $sha1);
            $modes[$sha1] = $mode;
            $names[$sha1] = $filename;
        }

        return Defer::defer(array('objects'=>$objects, 'modes'=>$modes, 'names'=>$names), __CLASS__);
    }
}
