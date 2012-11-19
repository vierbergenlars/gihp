<?php

namespace gihp\Object;

class Blob extends Internal {
    function __construct($data) {
        parent::__construct(parent::BLOB, $data);
    }

    static function import($data) {
        return new self($data);
    }
}
