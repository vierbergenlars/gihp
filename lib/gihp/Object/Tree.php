<?php

namespace gihp\Object;

class Tree extends Internal {
    const DIR = '040000';
    const FILE_NOEXEC = '100644';
    const FILE_NOEXEC_GROUPW = '100664';
    const FILE_EXEC = '100755';
    const SYMLINK = '120000';
    const GITLINK = '160000';
    function __construct() {
        parent::__construct(parent::TREE);
    }

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
