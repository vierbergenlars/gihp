<?php
namespace gihp\IO;

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
     * @return array A list of reference names
     */
    public function readRefs();

    /**
     * Reads a reference
     * @return \gihp\Ref\Reference
     */
    public function readRef($path);
    /**
     * Adds a new object
     * @param  \gihp\Object\Internal $object The object to add
     * @return bool
     */
    public function addObject(\gihp\Object\Internal $object);
    /**
     * Removes an object
     * @param  \gihp\Object\Internal $object The object to remove
     * @return bool
     */
    public function removeObject(\gihp\Object\Internal $object);
    /**
     * Reads an object
     * @param  string                $sha1 The hash of the object
     * @return \gihp\Object\Internal
     */
    public function readObject($sha1);
    /**
     * Moves the HEAD symbolic reference
     * @param  \gihp\Symref\SymbolicReference $ref The symbolic reference to move HEAD to
     * @return bool
     */
    public function moveHead(\gihp\Symref\SymbolicReference $ref);
    /**
     * Reads the symbolic reference in HEAD
     * @return \gihp\Symref\SymbolicReference
     */
    public function readHead();
    /**
     * Executes a garbage collect cycle
     */
    public function gc();
}
