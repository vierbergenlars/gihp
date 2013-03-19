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

namespace gihp;

use gihp\Object\Tree as OTree;
use gihp\Object\Blob;
use gihp\Object\Commit;

use gihp\Diff\Diff;
use gihp\Diff\Merge as DMerge;
/**
 * Merges two branches
 */
class Merge
{
    /**
     * Use our branch to resolve conflicts
     * @var int
     */
    const OURS = Diff\Merge::USE_HEAD;
    /**
     * Use their branch to resolve conflicts
     * @var int
     */
    const THEIRS = Diff\Merge::USE_BASE;
    /**
     * Don't resolve conflicts
     * @var int
     */
    const MANUAL = Diff\Merge::USE_NONE;
    /**
     * Base branch to merge against
     * @var Branch
     */
    protected $base;
    /**
     * Head branch to merge
     * @var Branch
     */
    protected $head;

    /**
     * Creates a new merge
     * @param \gihp\Branch $base Base branch for the merge
     * @param \gihp\Branch $head Head branch for the merge
     */
    public function __construct(Branch $base, Branch $head)
    {
        $this->base = $base;
        $this->head = $head;
    }

    /**
     * Merges the two branches
     * @param bool $ff   Fast-forward the base branch if possible
     * @param int  $mode Merge method to use
     * ({@link self::THEIRS} to solve conflicts by picking from the base branch,
     * {@link self::OURS} to solve conflicts by picking from the head branch,
     * {@link self::MANUAL} to write a conflicted file to the tree)
     * @return boolean|\gihp\Tree true when fast-forwarded, false when the base branch is up-to-date,
     * {@link \gihp\Tree} The merged tree in all other cases
     * @throws \RuntimeException When there is no common ancestor
     */
    public function merge($ff=true, $mode = self::MANUAL)
    {
        $base_history = $this->base->getHistory();
        $head_history = $this->head->getHistory();
        $base_history_sha = array();
        foreach ($base_history as $commit) {
            $base_history_sha[] = $commit->getSHA1();
        }
        $head_history_sha = array();
        foreach ($head_history as $commit) {
            $head_history_sha[] = $commit->getSHA1();
        }
        $head_diff = array_diff($head_history_sha, $base_history_sha);
        $base_diff = array_diff($base_history_sha, $head_history_sha);

        if (count($base_diff) == 0 && count($head_diff) > 0) {
            // Base has no modifications since head branched off
            if ($ff) { // Fast-forward the base branch
                $this->base->advanceHead($this->head->getHeadCommit());

                return true;
            } else {
                return $this->head->getTree();
            }
        }
        if (count($head_diff) == 0) {
            // Base is up-to-date, do nothing
            return false;
        }

        // Find last common commit
        $last_common_idx_head = array_search($head_diff[0], $head_history_sha)+1;
        $last_common_idx_base = array_search($base_diff[0], $base_history_sha)+1;

        $last_common_commit_head_sha = $head_history_sha[$last_common_idx_head];
        $last_common_commit_base_sha = $base_history_sha[$last_common_idx_base];

        if ($last_common_commit_base_sha != $last_common_commit_head_sha) {
            throw new \RuntimeException('Cannot find the last common commit.');
        }

        $last_common_commit = $base_history[$last_common_idx_base];
        $last_common_tree = $last_common_commit->getTree();

        // Load latest trees
        $base_latest_tree = $this->base->getTree();
        $head_latest_tree = $this->head->getTree();

        $final_tree = self::treeMerge($last_common_tree, $base_latest_tree, $head_latest_tree, $mode);

        return new Tree($final_tree);
    }

    /**
     * Merges two trees originating from a common tree
     * @param \gihp\Object\Tree $common_tree The common tree for both sides
     * @param \gihp\Object\Tree $base_tree   Left side (their side)
     * @param \gihp\Object\Tree $head_tree   Right side (our side)
     * @param int               $mode        Merge method to use
     * ({@link self::THEIRS} to solve conflicts by picking from the base branch,
     * {@link self::OURS} to solve conflicts by picking from the head branch,
     * {@link self::MANUAL} to write a conflicted file to the tree)
     * @return \gihp\Object\Tree
     * @throws \RuntimeException when trying to merge a tree and a blob
     */
    public static function treeMerge(OTree $common_tree, OTree $base_tree, OTree $head_tree, $mode = self::MANUAL)
    {
        if ($base_tree->getSHA1() == $head_tree->getSHA1()) {
            // Trees are identical, return that one
            return $base_tree;
        }
        $base_objects = $base_tree->getNamesAndHashes();
        $head_objects = $head_tree->getNamesAndHashes();
        $common_objects = $common_tree->getNamesAndHashes();

        $merged_tree = new OTree;
        $identical_objects = array_intersect_assoc($base_objects, $head_objects);

        foreach ($identical_objects as $name => $sha1) {
            // Merge identical objects
            $merged_tree->addObject($name, $base_tree->getObject($sha1), $base_tree->getObjectMode($sha1));
        }

        $diff_objects = array_diff_assoc($head_objects, $base_objects);

        foreach ($diff_objects as $name=>$_) {
            $head_sha1 = $head_objects[$name];
            $base_sha1 = $base_objects[$name];
            $common_sha1 = $common_objects[$name];

            $head_object = $head_tree->getObject($head_sha1);
            $base_object = $base_tree->getObject($base_sha1);
            $common_object = $common_tree->getObject($common_sha1);

            if ($head_object instanceof OTree && $base_object instanceof OTree && $common_object instanceof OTree) {
                // Both are trees, merge them recursively
                $merged_object = self::treeMerge($common_object, $base_object, $head_object, $mode);
            } elseif ($head_object instanceof Blob && $base_object instanceof Blob && $common_object instanceof Blob) {
                // Both are objects, merge them
                $merged_object = self::blobMerge($common_object, $base_object, $head_object, $mode);
            } elseif ($mode == self::OURS) {
                $merged_object = $head_object;
            } elseif ($mode == self::THEIRS) {
                $merged_object = $base_object;
            } else {
                throw new \RuntimeException('Cannot merge a blob and a tree (and no side chosen)');
            }
            // Add the object to the merged tree
            $merged_tree->addObject($name, $merged_object);
        }
    }

    /**
     * Merges two blobs from a common blob
     * @param \gihp\Object\Blob $common_blob
     * @param \gihp\Object\Blob $base_blob
     * @param \gihp\Object\Blob $head_blob
     * @param int               $mode        Merge method to use
     * ({@link self::THEIRS} to solve conflicts by picking from the base branch,
     * {@link self::OURS} to solve conflicts by picking from the head branch,
     * {@link self::MANUAL} to write a conflicted file to the tree)
     * @return \gihp\Object\Blob The merged blob
     */
    public static function blobMerge(Blob $common_blob, Blob $base_blob, Blob $head_blob, $mode = self::MANUAL)
    {
        $base_diff = new Diff($common_blob, $base_blob);
        $head_diff = new Diff($common_blob, $head_blob);

        $merge = new DMerge($common_blob, $base_diff, $head_diff);

        return $merge->merge($mode);
    }
}
