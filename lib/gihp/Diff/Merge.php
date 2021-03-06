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

use gihp\Object\Blob;

use gihp\Diff\Line\Line;
use gihp\Diff\Line\AddLine;
use gihp\Diff\Line\RemoveLine;
use gihp\Diff\Line\ConflictLine;

/**
 * 3-way blob merge
 *
 * Note: some portions of this file are licensed under the BSD3 license
 */
class Merge
{
    const USE_BASE = 0x1;
    const USE_HEAD = 0x2;
    const USE_NONE = 0x0;
    protected $merge_data;
    /**
     * Creates a new merge object
     * @param \gihp\Object\Blob $original    The original blob
     * @param \gihp\Diff\Diff   $base        The diff to the original blob from the base branch
     * @param \gihp\Diff\Diff   $head        The diff to the original blob from the head branch
     * @param string            $branch_name The name of the head branch
     */
    public function __construct(Blob $original, Diff $base, Diff $head, $branch_name = '')
    {
        $orig_arr = explode("\n", $original->getData());
        $base_diff_arr = $base->getArray();
        $head_diff_arr = $head->getArray();
        $this->merge_data = self::mergeArrays($orig_arr, $base_diff_arr, $head_diff_arr, $branch_name);
    }

    /**
     * Merges the diffs into the original file
     * @param  int             $mode Which file to use when a conflict occurs
     * @return array
     * @throws \LogicException When an unknown merge mode is set
     */
    public function mergeDiff($mode = self::USE_NONE)
    {
        switch ($mode) {
            case self::USE_NONE:
                $merge = $this->merge_data;
                break;
            case self::USE_BASE:
                $merge = array();
                foreach ($this->merge_data as $line) {
                    if ($line instanceof ConflictLine) {
                        $merge[] = new AddLine($line->getBase(), Line::BOTH);
                    } else {
                        $merge[] = $line;
                    }
                }
                break;
            case self::USE_HEAD:
                $merge = array();
                foreach ($this->merge_data as $line) {
                    if ($line instanceof ConflictLine) {
                        $merge[] = new Addline($line->getHead(), Line::BOTH);
                    } else {
                        $merge[] = $line;
                    }
                }
                break;
            default:
                throw new \LogicException('Unknown merge mode');
        }

        return $merge;
    }

    /**
     * Merges the blobs
     * @param  int               $mode {@see mergeDiff()}
     * @return \gihp\Object\Blob
     */
    public function merge($mode = self::USE_NONE)
    {
        $filtered_merge = array_filter($this->mergeDiff($mode), function($line) {
            return !($line instanceof RemoveLine);
        });
        $merge =  array_map(function($line) {
            return $line->getLine();
        }, $filtered_merge);

        return new Blob(implode("\n", $merge));
    }

    public function __toString()
    {
        return $this->merge();
    }

    /**
     * Merges two diffs
     * @author Luke Palmer <lrpalmer@gmail.com>
     * @license https://github.com/luqui/merge3/blob/master/LICENSE BSD3
     * @param  array           $orig
     * @param  array           $ldiff
     * @param  array           $rdiff
     * @param  string          $branch_name
     * @return array
     * @throws \LogicException
     */
    private static function mergeArrays($orig, $ldiff, $rdiff, $branch_name)
    {
        $oi = $li = $ri = $zi = 0;
        while ($oi < count($orig) || $li < count($ldiff) || $ri < count($rdiff)) {
            if ($li < count($ldiff)) {
                $lstat = $ldiff[$li][0];
                $ltext = $ldiff[$li][1];
            } else {
                $lstat = " ";
                $ltext = null;
            }

            if ($ri < count($rdiff)) {
                $rstat = $rdiff[$ri][0];
                $rtext = $rdiff[$ri][1];
            } else {
                $rstat = " ";
                $rtext = null;
            }

            switch ($lstat.$rstat) {
                case "  ":
                    $result[$zi] = new Line($orig[$oi]);
                    $zi++; $oi++; $li++; $ri++;
                    break;
                case " -":
                    $result[$zi] = new RemoveLine($rtext, Line::HEAD);
                    $zi++; $oi++; $li++; $ri++;
                    break;
                case "- ":
                    $result[$zi] = new RemoveLine($ltext, Line::BASE);
                    $zi++; $oi++; $li++; $ri++;
                    break;
                case "--":
                    $result[$zi] = new RemoveLine($ltext, Line::BOTH);
                    $zi++; $oi++; $li++; $ri++;
                    break;
                case "+-":
                    $result[$zi] = new RemoveLine($rtext, Line::HEAD);
                    $zi++; $oi++; $ri++;
                    break;
                case "-+":
                    $result[$zi] = new RemoveLine($ltext, Line::BASE);
                    $zi++; $oi++; $li++;
                    break;
                case "+ ":
                    $result[$zi] = new AddLine($ltext, Line::BASE);
                    $zi++; $li++;
                    break;
                case " +":
                    $result[$zi] = new AddLine($rtext, Line::HEAD);
                    $zi++; $ri++;
                    break;
                case "++":
                    if (self::compare($ltext, $rtext)) {
                        $result[$zi] = new AddLine($ltext, Line::BOTH);
                    } else {
                        $result[$zi] = new ConflictLine($ltext, $rtext, $branch_name);
                    }
                    $zi++; $li++; $ri++;
                    break;
                default:
                    throw new \LogicException("Missed something: [$lstat][$rstat]");
            }
        }

        return $result;
    }

    private static function compare($a, $b)
    {
        return $a == $b;
    }
}
