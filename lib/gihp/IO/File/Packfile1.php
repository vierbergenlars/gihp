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
