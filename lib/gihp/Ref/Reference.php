<?php

namespace gihp\Ref;

use gihp\Defer\Deferrable;
use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Object\Commit;

/**
 * The base of all references
 * @internal
 */
class Reference implements Deferrable {
    /**
     * Reference is a tag
     */
    const TAG = 'tags';
    /**
     * Reference is a head
     */
    const HEAD = 'heads';

    /**
     * The path to the reference
     * @var string
     */
    private $path;
    /**
     * The data the reference contains
     * @var string
     */
    private $data;
    /**
     * Creates a new reference
     * @param string $path The path to the reference
     * @param string $data The data the reference contains
     */
    function __construct($path, $data=null) {
        $this->setData($path."\0".$data);
    }

    /**
     * Sets the internal data of the reference
     * @internal Actually takes a serialized data stream and gets data and path from them
     */
    function setData($data) {
        list($path, $data) = explode("\0", $data, 2);
        $this->path = $this->getTypeAsString().'/'.$path;
        $this->data = $data;
    }

    /**
     * Gets the reference type as a string
     * @return string
     */
    private function getTypeAsString() {
        if($this instanceof Head) {
            return self::HEAD;
        }
        if($this instanceof Tag) {
            return self::TAG;
        }
        throw new \RuntimeException('Bad type');

    }

    /**
     * Gets the data
     * @internal it's just the SHA
     */
    function getData() {
        return $this->data;
    }

    /**
     * Gets the path
     * @internal The whole path
     */
    function getPath() {
        return $this->path;
    }

    /**
     * Converts the reference to a raw data stream
     * @return string
     */
    function __toString() {
        return $this->getData();
    }

    /**
     * Imports a raw reference data stream
     * @param gihp\Defer\Loader $loader The loader class
     * @param string $data The raw data
     * @return Tag|Head One of reference's subclasses. Depending on the datatype
     */
    static function import(DLoader $loader, $data) {
        list($type, $data) = explode("/", $data, 2);
        switch($type) {
            case self::TAG:
                return Tag::import($loader, $data);
            case self::HEAD:
                return Head::import($loader, $data);
            default:
                throw new \LogicException('Bad reference type');
        }
    }
}
