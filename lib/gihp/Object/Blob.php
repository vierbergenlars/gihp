<?php

namespace gihp\Object;

use gihp\Defer\Loader;

/**
 * A casual blob object.
 *
 * Usually holds file data, but can contain anything you want.
 * @internal Store all strings that you want in here. Just a wrapper around
 * Internal, but with the right data-type
 */
class Blob extends Internal {
    function __construct($data) {
        parent::__construct(parent::BLOB, $data);
    }

    static function import(Loader $loader, $data) {
        return new self($data);
    }
}
