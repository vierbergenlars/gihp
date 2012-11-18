<?php

namespace gihp\Object;

class Internal {
    private $type;
    private $data;
    const COMMIT=1;
    const BLOB=2;
    const TREE=3;
    function __construct($type, $data=null) {
        $this->type = $type;
        $this->data = $data;
    }

    function updateData($data) {
        $this->data = $data;
    }

    function getSHA1() {
        return sha1($this->__toString());
    }

    private function getTypeString() {
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

    function __toString() {
        $header = $this->getTypeString().' '.strlen($this->data).chr(0);
        $store = $header.$this->data;
        return $store;
    }
}