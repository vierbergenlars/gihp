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

    protected function setData($data) {
        $this->data = $data;
    }

    protected function appendData($data) {
        $this->data.=$data;
    }

    function getSHA1() {
        return sha1($this->__toString());
    }

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

    function __toString() {
        $header = $this->getTypeString().' '.strlen($this->data).chr(0);
        $store = $header.$this->data;
        return $store;
    }

    static function import($string) {
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
                return Commit::import($data);
            case 'blob':
                return Blob::import($data);
            case 'tree':
                return Tree::import($data);
            default:
                throw \LogicException('Bad object type. Should have been checked already');
        }
    }
}
