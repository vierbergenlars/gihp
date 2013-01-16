<?php

namespace gihp\Diff\Line;

/**
 * A line that was removed in the diff
 */
class RemoveLine extends Line {
    protected $prefix = '-';
}
