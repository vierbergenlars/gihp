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
namespace gihp\IO;

/**
 * Abstracts all IO operations.
 *
 * Allows this library to be ported to other transport protocols
 */
interface IOInterface
{
    /**
     * (Re)initializes a repository
     */
    public function init();

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
     * @return \gihp\Ref\Head|\gihp\Ref\Tag
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
     * @param  string                                                                            $sha1 The hash of the object
     * @return \gihp\Object\Commit|\gihp\Object\Blob|\gihp\Object\Tree|\gihp\Object\AnnotatedTag
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
