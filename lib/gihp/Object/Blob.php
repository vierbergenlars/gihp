<?php

namespace gihp\Object;

class Blob extends Internal
{
    public function __construct($data)
    {
        parent::__construct(parent::BLOB, $data);
    }
}
