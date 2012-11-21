<?php
namespace gihp\IO;

class RecursiveFileIterator extends \RecursiveDirectoryIterator
{
    public function getChildren()
    {
        try {
            return parent::getChildren();
        } catch (\UnexpectedValueException $e) {
            return new \RecursiveArrayIterator(array());
        }
    }
}
