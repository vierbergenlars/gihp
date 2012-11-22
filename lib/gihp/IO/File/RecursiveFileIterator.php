<?php
namespace gihp\IO\File;

/**
 * @internal
 */
class RecursiveFileIterator extends \RecursiveDirectoryIterator {
    function getChildren() {
        try {
            return parent::getChildren();
        }
        catch(\UnexpectedValueException $e) {
            return new \RecursiveArrayIterator(array());
        }
    }
}
