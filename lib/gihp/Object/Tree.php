<?php

namespace gihp\Object;

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
     * @internal
     * @var array
     */
    protected $objects = array();
    /**
     * A hashmap of all object names, ordered by their name
     * @internal
     * @var array
     */
    protected $names = array();
    /**
     * Creates a new, empty tree
     */
    public function __construct()
    {
    }

    /**
     * Adds an object to the tree
     * @param string   $name   The name of the object
     * @param Internal $object A {@link Tree} or a {@link Blob}
     * @param string   $mode   When the object is a {@link Blob}, the mode of the file as a string
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
        $this->clearSHA1();
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
     * Gets the objects array
     * @internal
     * @return array
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Ensures cloning the tree also clones its subtrees
     * @internal
     */
    public function __clone()
    {
        foreach ($this->objects as &$object) {
            $object[0] = clone $object[0];
        }
    }

    /**
     * Writes the tree and all its linked objects to IO
     * @internal
     */
    public function write(IOInterface $io)
    {
        $io->addObject($this);
        foreach ($this->objects as $object) {
            $object[0]->write($io);
        }
    }
}
