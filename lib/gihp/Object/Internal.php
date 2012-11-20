<?php

namespace gihp\Object;

use gihp\Defer\Deferrable;
use gihp\Defer\Loader;

/**
 * Base class for all sha1-based objects
 *
 * Parses the basic git structure for these objects and verifies them
 * @internal
 */
class Internal implements Deferrable {
    /**
     * Type of the object
     * @var int
     */
    private $type;
    /**
     * Data in the object
     * @var string
     */
    private $data;
    /**
     * Object is a commit
     */
    const COMMIT=1;
    /**
     * Object is a blob
     */
    const BLOB=2;
    /**
     * Object is a tree
     */
    const TREE=3;

    /**
     * Creates a new Internal object
     * @param int $type The type of the object
     * @param string|null $data The data in the object
     */
    function __construct($type, $data=null) {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Overwrites the data in the object
     * @param string $data
     */
    protected function setData($data) {
        $this->data = $data;
    }

    /**
     * Appends data. Does not overwrite it.
     * @param string $data
     */
    protected function appendData($data) {
        $this->data.=$data;
    }

    /**
     * Gets the data stored in here
     *
     * Note: only the data, not the checksums and so around it.
     * @return string
     */
    function getData() {
        return $this->data;
    }

    /**
     * Gets the SHA1 hash of the object
     * @return sting
     */
    function getSHA1() {
        return sha1($this->__toString());
    }

    /**
     * Gets the type as a string
     * @return string
     */
    protected function getTypeString() {
        switch($this->type) {
            case self::COMMIT:
                return 'commit';
            case self::BLOB:
                return 'blob';
            case self::TREE:
                return 'tree';
            default:
                return $this->type;
        }
    }

    /**
     * The object as it should be written to disk, with all padding
     *
     * @internal ALWAYS call this function after adding data with setData() or appendData()
     * @return string
     */
    function __toString() {
        $header = $this->getTypeString().' '.strlen($this->data).chr(0);
        $store = $header.$this->data;
        return $store;
    }

    /**
     * Imports a raw object from disk
     * @param Loader $loader The loader to load embedded references
     * @param string $string The raw data
     * @return Internal A subclass of this class, Commit, Blob or Tree
     */
    static function import(Loader $loader, $string) {
        $parts = explode("\0", $string, 3);
        $header = $parts[0];
        $data = $parts[1];

        if(!preg_match('/^(commit|blob|tree) ([0-9]+)$/', $header, $matches)) {
            throw new \RuntimeException('Bad object header');
        }
        $type = $matches[1];
        $length = (int)$matches[2];

        if(strlen($data) !== $length) {
            throw new \RuntimeException('Data length mismatch');
        }
        switch($type) {
            case 'commit':
                return Commit::import($loader, $data);
            case 'blob':
                return Blob::import($loader, $data);
            case 'tree':
                return Tree::import($loader, $data);
            default:
                throw \LogicException('Bad object type. Should have been checked already');
        }
    }
}
