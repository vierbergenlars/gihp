<?php

namespace gihp\IO\File;

/**
 * Packfile version 1
 * @internal
 */
class Packfile1
{
    private function __construct() {}
    /**
     * Tries to find an object hash in the index
     * @param  resource $index The opened index file
     * @param  string   $sha1  The binary SHA of the object
     * @return int      The offset in the packfile
     */
    public static function findHashInIndex($index, $sha1)
    {
        list($cur, $after) = Packfile::readFanOut($index, $sha1, 0);
        $n = $after-$cur;
        if($n == 0)

            return false;

        fseek($index, 4*256 + 24*$cur);

        for ($i = 0; $i < $n; $i++) {
            $off = Packfile::bin_fuint32($index);
            $name = fread($index, 20);
            if ($name == $sha1) {
                return $off;
            }
        }
    }
}
