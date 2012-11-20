<?php

namespace gihp\Object;

/**
 * A casual blob object.
 *
 * Usually holds file data, but can contain anything you want.
 * @internal Store all strings that you want in here. Just a wrapper around
 * Internal, but with the right data-type
 */
class Blob extends Internal
{
    public function __construct($data)
    {
        parent::__construct(parent::BLOB, $data);
    }

    public static function import($data)
    {
        return new self($data);
    }
}
