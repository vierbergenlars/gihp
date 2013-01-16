<?php

namespace gihp\Diff\Line;

/**
 * A line that was conflicted in the merge
 */
class ConflictLine extends Line
{
    private $base;
    private $head;
    /**
     * Creates a new conflicted line
     * @param string $base        The line as present in the base branch
     * @param string $head        The line as present in the head branch
     * @param type   $branch_name The name of the head branch
     */
    public function __construct($base, $head, $branch_name = '')
    {
        $this->base = $base;
        $this->head = $head;
        $line = '<<<<<<< HEAD'."\n";
        $line.= $base."\n";
        $line.= '======='."\n";
        $line.= $head."\n";
        $line.= '>>>>>>> '.$branch_name;
        parent::__construct($line, parent::BOTH);
    }

    /**
     * The line as present in the base branch
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * The line as present in the head branch
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Converts the conflicted line to an internal representation
     * @internal
     * @return array
     */
    public function getArray()
    {
        return array('conflict', $this->base, $this->head);
    }
}
