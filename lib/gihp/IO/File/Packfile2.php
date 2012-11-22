<?php

namespace gihp\IO\File;

/**
 * Packfile version 2
 * @internal
 */
class Packfile2
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
        list($cur, $after) = Packfile::readFanOut($index, $sha1, 8);
        if($cur == $after)

            return false;
        fseek($index, 8+4*255);
        $total_obj = Packfile::bin_fuint32($index);

        fseek($index, 8 + 4*256 + 20*$cur);
        for ($i = $cur; $i < $after; $i++) {
            $name = fread($index, 20);
            if($name == $sha1)
                break;
        }
        if($i == $after)

            return false;

        fseek($index, 8 + 4*256 + 24*$total_obj + 4*$i);
        $offset = Packfile::bin_fuint32($index);

        if ($offset & 0x80000000) {
            // PHP cannot handle packfiles > 2GB
            throw new \RangeException('64-bit packfile offsets not implemented');
        }

        return $offset;
    }

}
