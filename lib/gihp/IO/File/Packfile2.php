<?php
/**
 * Copyright (c) 2013 Lars Vierbergen, Patrik Fimml
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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
