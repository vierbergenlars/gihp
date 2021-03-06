<?php
/**
 * Copyright (c) 2013 Lars Vierbergen
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

/**
 * The following license only applies to the function directly below comments containing
 * the text @license https://github.com/luqui/merge3/blob/master/LICENSE BSD3
 * 
 * --
 * Copyright (c) 2013, Luke Palmer
 * 
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 * 
 *     * Redistributions in binary form must reproduce the above
 *       copyright notice, this list of conditions and the following
 *       disclaimer in the documentation and/or other materials provided
 *       with the distribution.
 * 
 *     * Neither the name of Luke Palmer nor the names of other
 *       contributors may be used to endorse or promote products derived
 *       from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace gihp\Diff;

use gihp\Diff\Line\Line;
use gihp\Diff\Line\AddLine;
use gihp\Diff\Line\RemoveLine;

use gihp\Object\Blob;

/**
 * Creates a diff of two blobs
 *
 * Note: some portions of this file are licensed under the BSD3 license
 */
class Diff
{
    private $diff = array();
    /**
     * Creates a new diff object
     * @param \gihp\Object\Blob $original The base of the diff
     * @param \gihp\Object\Blob $modified The modified version of the base
     */
    public function __construct(Blob $original, Blob $modified)
    {
        $orig_arr = explode("\n", $original->getData());
        $mod_arr  = explode("\n", $modified->getData());
        $this->diff = self::diff($orig_arr, $mod_arr);
    }

    /**
     * Diffs two arrays
     * @author Luke Palmer <lrpalmer@gmail.com>
     * @license https://github.com/luqui/merge3/blob/master/LICENSE BSD3
     * @param  array $R
     * @param  array $C
     * @return array
     */
    private static function diff($R, $C)
    {
        $LENGTH = 0;
        $DIFFLENGTH = 1;
        $DIRECTION = 2;

        // Direction is:
        $UPLEFT = 0;  //  (keep)
        $LEFT   = 1;  //  (dec col)
        $UP     = 2;  //  (dec row)

        for ($i = 0; $i < count($R); $i++) {
            $table[$i][-1] = array(0, $i+1, $UP);
        }
        for ($j = 0; $j < count($C); $j++) {
            $table[-1][$j] = array(0, $i+1, $LEFT);
        }
        $table[-1][-1] = array(0,0,0);

        // Fill in the table.
        for ($i = 0; $i < count($R); $i++) {
            for ($j = 0; $j < count($C); $j++) {
                if (self::compare($R[$i], $C[$j])) {
                    $table[$i][$j] = array(1 + $table[$i-1][$j-1][$LENGTH], $table[$i-1][$j-1][$DIFFLENGTH], $UPLEFT);
                } else {
                    $left = $table[$i][$j-1];
                    $up   = $table[$i-1][$j];
                    if ($left[$LENGTH] > $up[$LENGTH]) {
                        $table[$i][$j] = array($left[$LENGTH], $left[$DIFFLENGTH]+1, $LEFT);
                    } elseif ($up[$LENGTH] > $left[$LENGTH]) {
                        $table[$i][$j] = array($up[$LENGTH], $up[$DIFFLENGTH]+1, $UP);
                    } elseif ($left[$DIFFLENGTH] < $up[$DIFFLENGTH]) {
                        $table[$i][$j] = array($left[$LENGTH], $left[$DIFFLENGTH]+1, $LEFT);
                    } elseif ($up[$DIFFLENGTH] < $left[$DIFFLENGTH]) {
                        $table[$i][$j] = array($up[$LENGTH], $up[$DIFFLENGTH]+1, $UP);
                    } else {
                        // just pick up, I guess
                        $table[$i][$j] = array($up[$LENGTH], $up[$DIFFLENGTH]+1, $UP);
                    }
                }
            }
        }

        // Reconstruct.
        $i = count($R)-1;
        $j = count($C)-1;
        $rix = 0;
        while ($i != -1 || $j != -1) {
            $elem = $table[$i][$j];
            $dir = $elem[$DIRECTION];
            if ($dir == $UPLEFT) {
                $result[$rix++] = new Line($R[$i]); // == $C[$j]
                $i--; $j--;
            } elseif ($dir == $LEFT) {
                $result[$rix++] = new AddLine($C[$j]);
                $j--;
            } elseif ($dir == $UP) {
                $result[$rix++] = new RemoveLine($R[$i]);
                $i--;
            }
        }

        $a = $result;
        for ($i = count($a)-1, $j=0; $i >= 0; $i--, $j++) {
            $b[$j] = $a[$i];
        }

        return $b;
    }

    /**
     * Compare mode for the diff
     * @param  string $a
     * @param  string $b
     * @return bool
     */
    private static function compare($a, $b)
    {
        return $a == $b;
    }

    /**
     * Converts the diff to an internal representation for the merger
     * @internal
     * @return array
     */
    public function getArray()
    {
        return array_map(function($line) {
            return $line->getArray();
        }, $this->diff);
    }

    /**
     * A string representation of the fiff
     * @return string
     */
    public function __toString()
    {
        return implode("\n", $this->diff);
    }
}
