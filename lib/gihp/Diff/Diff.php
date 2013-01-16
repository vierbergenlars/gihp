<?php

namespace gihp\Diff;

use gihp\Diff\Line\Line;
use gihp\Diff\Line\AddLine;
use gihp\Diff\Line\RemoveLine;

use gihp\Object\Blob;

/**
 * Creates a diff of two blobs
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
