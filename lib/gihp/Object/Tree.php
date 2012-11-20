<?php

namespace gihp\Object;

/**
 * A git tree
 *
 * A tree contains references to blobs and other trees.
 * It also records file modes.
 */
class Tree extends Internal {
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
        $this->appendData($mode.' '.$name.chr(0).pack('H*', $object->getSHA1()));
    }
}
