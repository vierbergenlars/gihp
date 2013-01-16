<?php

namespace gihp;

use gihp\Object\Tree as OTree;
use gihp\Object\Blob;
use gihp\Object\Commit;

use gihp\Diff\Diff;
use gihp\Diff\Merge as DMerge;
/**
 * Merges two branches
 */
class Merge {
    const OURS = DMerge::USE_BASE;
    const THEIRS = DMerge::USE_HEAD;
    const MANUAL = DMerge::USE_NONE;
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
     * Latest common commit of the two branches
     * @var Commit 
     */
    protected $common_commit;
    
    /**
     * Merge method to use
     * @var int
     */
    protected $merge_method;
    
    /**
     * Creates a new merge
     * @param \gihp\Branch $base Base branch for the merge
     * @param \gihp\Branch $head Head branch for the merge
     */
    function __construct(Branch $base, Branch $head) {
        $this->base = $base;
        $this->head = $head;
    }
    
    /**
     * Merges the two branches
     * @param bool $ff Fast-forward the base branch if possible
     * @param int $method Merge method to use
     * ({@link self::OURS} to solve conflicts by picking from the base branch,
     * {@link self::THEIRS} to solve conflicts by picking from the head branch,
     * {@link self::MANUAL} to write a conflicted file to the tree)
     * @return boolean|\gihp\Tree true when fast-forwarded, false when the base branch is up-to-date,
     * {@link \gihp\Tree} The merged tree in all other cases
     * @throws \RuntimeException When there is no common ancestor
     */
    function merge($ff=true, $method = self::MANUAL) {
        $this->merge_method = $method;
        $base_history = $this->base->getHistory();
        $head_history = $this->head->getHistory();
        $base_history_sha = array();
        foreach($base_history as $commit) {
            $base_history_sha[] = $commit->getSHA1();
        }
        $head_history_sha = array();
        foreach($head_history as $commit) {
            $head_history_sha[] = $commit->getSHA1();
        }
        $head_diff = array_diff($head_history_sha, $base_history_sha);
        $base_diff = array_diff($base_history_sha, $head_history_sha);
        
        var_dump($head_diff, $base_diff);
        var_dump($head_history_sha, $base_history_sha);
        
        if(count($base_diff) == 0 && count($head_diff) > 0) {
            // Base has no modifications since head branched off
            if($ff) { // Fast-forward the base branch
                $this->base->advanceHead($this->head->getHeadCommit());
                return true;
            }
            else {
                return $this->head->getTree();
            }
        }
        if(count($head_diff) == 0) {
            // Base is up-to-date, do nothing
            return false;
        }
        
        // Find last common commit
        $last_common_idx_head = array_search($head_diff[0], $head_history_sha)+1;
        $last_common_idx_base = array_search($base_diff[0], $base_history_sha)+1;
        
        $last_common_commit_head_sha = $head_history_sha[$last_common_idx_head];
        $last_common_commit_base_sha = $base_history_sha[$last_common_idx_base];
        
        if($last_common_commit_base_sha != $last_common_commit_head_sha) {
            throw new \RuntimeException('Cannot find the last common commit.');
        }
        
        $last_common_commit = $base_history[$last_common_idx_base];
        $last_common_tree = $last_common_commit->getTree();
        
        // Load latest trees
        $base_latest_tree = $this->base->getTree();
        $head_latest_tree = $this->head->getTree();
        
        $final_tree = $this->treeMerge($last_common_tree, $base_latest_tree, $head_latest_tree);
    
        return new Tree($final_tree);
    }
    
    /**
     * Merges two trees originating from a common tree
     * @param \gihp\Object\Tree $common_tree
     * @param \gihp\Object\Tree $base_tree
     * @param \gihp\Object\Tree $head_tree
     * @return \gihp\Object\Tree
     * @throws \RuntimeException when trying to merge a tree and a blob
     */
    function treeMerge(OTree $common_tree, OTree $base_tree, OTree $head_tree) {       
        if($base_tree->getSHA1() == $head_tree->getSHA1()) {
            // Trees are identical, return that one
            return $base_tree;
        }
        $base_objects = $base_tree->getNamesAndHashes();
        $head_objects = $head_tree->getNamesAndHashes();
        $common_objects = $common_tree->getNamesAndHashes();
        
        $merged_tree = new OTree;
        $identical_objects = array_intersect_assoc($base_objects, $head_objects);
        
        foreach($identical_objects as $name => $sha1) {
            // Merge identical objects
            $merged_tree->addObject($name, $base_tree->getObject($sha1), $base_tree->getObjectMode($sha1));
        }
        
        $diff_objects = array_diff_assoc($head_objects, $base_objects);
        
        foreach($diff_objects as $name=>$_) {
            $head_sha1 = $head_objects[$name];
            $base_sha1 = $base_objects[$name];
            $common_sha1 = $common_objects[$name];
            
            $head_object = $head_tree->getObject($head_sha1);
            $base_object = $base_tree->getObject($base_sha1);
            $common_object = $common_tree->getObject($common_sha1);
            
            if($head_object instanceof OTree && $base_object instanceof OTree && $common_object instanceof OTree) {
                // Both are trees, merge them recursively
                $merged_object = $this->treeMerge($common_object, $base_object, $head_object);
            }
            elseif($head_object instanceof Blob && $base_object instanceof Blob && $common_object instanceof Blob) {
                // Both are objects, merge them
                $merged_object = $this->blobMerge($common_object, $base_object, $head_object);
            }
            else {
                throw new \RuntimeException('Cannot merge a blob and a tree');
            }
            // Add the object to the merged tree
            $merged_tree->addObject($name, $merged_object);
        }
    }
    
    /**
     * Merges two blobs from a base blob
     * @param \gihp\Object\Blob $common_blob
     * @param \gihp\Object\Blob $base_blob
     * @param \gihp\Object\Blob $head_blob
     * @return \gihp\Object\Blob The merged blob
     */
    function blobMerge(Blob $common_blob, Blob $base_blob, Blob $head_blob) {
        $base_diff = new Diff($common_blob, $base_blob);
        $head_diff = new Diff($common_blob, $head_blob);
        
        $merge = new DMerge($common_blob, $base_diff, $head_diff, $this->head->getName());
        
        return $merge->merge($this->merge_mode);
    }
}
