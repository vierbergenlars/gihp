<?php

namespace gihp\Ref;

use gihp\Object\Internal;

/**
 * Head reference
 *
 * Points to the tip of a branch
 */
class Head extends Reference
{
    /**
     * Updates the commit the reference points to
     * @param Internal $commit
     */
    public function setCommit(Internal $commit)
    {
        $this->commit = $commit;
    }
}
