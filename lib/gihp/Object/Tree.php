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
class Tree extends Internal implements WritableInterface {
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
    function __construct() {
        parent::__construct(parent::TREE);
    }

    /**
     * Adds an object to the tree
     * @param string $name The name of the object
     * @param Internal $object A tree object or a Blob
     * @param string $mode When the object is a blob, the mode of the file as a string
     */
    function addObject($name, Internal $object, $mode = '644') {
        if($object instanceof self) {
            $mode = self::DIR;
        }
        else if($object instanceof Blob) {
            switch($mode) {
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
     * @param string $name The name of the object
     * @return string|null The SHA of the object with that name or null if the name is not found.
     */
    function getObjectSHA1ByName($name) {
        return (($result = array_search($name, $this->names))?$result:null);
    }

    /**
     * Gets the mode of an object
     * @param string $sha1 The SHA of the object
     * @return string The mode of the object
     */
    function getObjectMode($sha1) {
        if(!isset($this->modes[$sha1])) {
            throw new \RuntimeException('SHA not found in this tree');
        }
        return $this->modes[$sha1];
    }

    /**
     * Gets the object
     * @param string $sha1 The SHA of the object
     * @return Tree|Blob The tree or the blob belonging to that SHA
     */
    function getObject($sha1) {
        if(!isset($this->objects[$sha1])) {
            throw new \RuntimeException('SHA not found in this tree');
        }
        return $this->objects[$sha1];
    }

    /**
     * Converts the object to a raw string
     * @return string The raw data-stream that represents the tree
     */
    function __toString() {
        $this->setData('');
        foreach($this->objects as $sha=>$object) {
            $this->appendData($this->modes[$sha].' '.$this->names[$sha].chr(0).pack('H*', $sha));
        }
        return parent::__toString();
    }

    function write(IOInterface $io) {
        $io->addObject($this);
        foreach($this->objects as $object) {
            $object->write($io);
        }
    }

    /**
     * Imports the tree object
     * @param Loader $loader The object to load embedded references
     * @param string $tree The raw tree data
     * @return Tree The tree represented by the raw object
     */
    static function import(DLoader $loader, $tree) {
        $parts = explode("\0", $tree);

        $map = array();
        $length = count($parts)-1;

        foreach($parts as $i=>$part) {
            if($i === 0) { //First object, partitial. Only mode and name
                list($next_mode, $next_name) = explode(' ', $part, 2);
                $map[$next_name]['mode'] = $next_mode;
            }
            elseif($length == $i) { //Last object, partitial. Only hash
                $map[$next_name]['hash'] = $part;
            }
            else { // Normal objects, current hash and next mode and name
                $map[$next_name]['hash'] = substr($part, 0, 20);
                $next_mode_name = substr($part, 20);
                list($next_mode, $next_name) = explode(' ', $next_mode_name, 2);
                $map[$next_name]['mode'] = $next_mode;
            }
        }

        $objects = array();
        $modes = array();
        $names = array();

        foreach($map as $name=>$props) {
            $mode = $props['mode'];
            $decode = unpack('H*', $props['hash']);
            $sha1 = $decode[1];

            $objects[$sha1] = new Reference($loader, $sha1);
            $modes[$sha1] = $mode;
            $names[$sha1] = $name;
        }
        return Defer::defer(array('objects'=>$objects, 'modes'=>$modes, 'names'=>$names), __CLASS__);
    }
}
