<?php
namespace gihp/IO;

/**
 * Abstracts all IO operations.
 *
 * Allows this library to be ported to other transport protocols
 */
interface IOInterface
{
    /**
     * Creates a new IO object
     * @param string $path The path to initialize the IO object to
     */
    public function __construct($path);
    /**
     * Adds a new branch
     * @param  \gihp\Branch $branch The branch to add
     * @return bool
     */
    public function addBranch(\gihp\Branch $branch);
    /**
     * Removes a branch
     * @param  \gihp\Branch $branch The branch to remove
     * @return bool
     */
    public function removeBranch(\gihp\Branch $branch);
    /**
     * Lists all branches
     * @return array
     */
    public function readBranches();
    /**
     * Adds a new commit
     * @param  \gihp\Commit $commit The commit to add
     * @return bool
     */
    public function addCommit(\gihp\Commit $commit);
    /**
     * Removes a commit
     * @param  \gihp\Commit $commit The commit to remove
     * @return bool
     */
    public function removeCommit(\gihp\Commit $commit);
    /**
     * Lists all commits
     * @return array
     */
    public function readCommits();
    /**
     * Adds a new tree
     * @param  \gihp\Tree $tree The tree to add
     * @return bool
     */
    public function addTree(\gihp\Tree $tree);
    /**
     * Remove a tree
     * @param  \gihp\Tree $tree The tree to remove
     * @return bool
     */
    public function removeTree(\gihp\Tree $tree);
    /**
     * Lists all trees
     * @return array
     */
    public function readTrees();
    /**
     * Adds a new reference
     * @param  \gihp\Ref\Reference $ref The reference to add
     * @return bool
     */
    public function addRef(\gihp\Ref\Reference $ref);
    /**
     * Removes a reference
     * @param  \gihp\Ref\Reference $ref The reference to remove
     * @return bool
     */
    public function removeRef(\gihp\Ref\Reference $ref);
    /**
     * Lists all references
     * @return array
     */
    public function readRefs();
    /**
     * Adds a new object
     * @param  \gihp\Internal\Object $object The object to add
     * @return bool
     */
    public function addObject(\gihp\Internal\Object $object);
    /**
     * Removes an object
     * @param  \gihp\Internal\Object $object The object to remove
     * @return bool
     */
    public function removeObject(\gihp\Interal\Object $object);
    /**
     * Lists all objects
     * @return array
     */
    public function readObjects();
    /**
     * Moves the HEAD symbolic reference
     * @param  \gihp\Ref\SymbolicReference $ref The symbolic reference to move HEAD to
     * @return bool
     */
    public function moveHead(\gihp\Ref\SymbolicReference $ref);
    /**
     * Reads the symbolic reference in HEAD
     * @return \gihp\Ref\SymbolicReference
     */
    public function readHead();
    /**
     * Executes a garbage collect cycle
     */
    public function gc();
}
