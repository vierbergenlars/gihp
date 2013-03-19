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

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\IO\IOInterface;

/**
 * A loader for objects.
 * Always invokes load on Internal, but that will load the right object.
 * @internal
 */
class Loader implements DLoader
{
    /**
     * The IO class
     * @var IOInterface
     */
    private $io;

    /**
     * Creates a new loader
     * @param IOInterface $io An IO module
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * Loads the object
     * @param  string   $sha1 The hash of the object to load
     * @return Internal
     */
    public function load($sha1)
    {
        return $this->io->readObject($sha1);
    }

}
