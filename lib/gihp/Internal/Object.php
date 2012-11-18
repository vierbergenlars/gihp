<?php

namespace gihp\Internal;

class Object
{
    private $type;
    private $data;
    const COMMIT=1;
    const BLOB=2;
    const TREE=3;
    public function __construct($type, $data=null)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function updateData($data)
    {
        $this->data = $data;
    }

    public function getSHA1()
    {
        return sha1($this->__toString());
    }

    private function getTypeString()
    {
        switch ($this->type) {
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

    public function __toString()
    {
        $header = $this->getTypeString().' '.strlen($this->data).chr(0);
        $store = $header.$this->data;

        return $store;
    }
}
