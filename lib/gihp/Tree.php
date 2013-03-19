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

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Tree as OTree;
use gihp\Object\Blob;
use gihp\Object\Internal;

/**
 * Helps to build trees
 */
class Tree implements WritableInterface
{
    /**
     * The root tree
     * @var OTree
     */
    protected $tree;
    /**
     * Creates a new tree helper
     * @param OTree $previous The tree to base all edit operations on
     */
    public function __construct(OTree $previous=null)
    {
        if ($previous) {
            $this->tree = clone $previous;
        } else {
            $this->tree = new OTree;
        }
    }

    /**
     * Add a new file to the tree
     * @param  string            $filename The full filename to add to the tree
     * @param  string            $data     The contents for the file
     * @param  int               $mode     The permissions for the file
     * @throws \RuntimeException When its parent is not a tree
     */
    public function addFile($filename, $data, $mode=0644)
    {
        $mode = decoct($mode);
        $tree = $this->getFileObject(dirname($filename), true);
        if(!($tree instanceof OTree))
            throw new \RuntimeException('Parent is not a tree. Cannot make file');
        $tree->addObject(basename($filename), new Blob($data), $mode);
    }

    /**
     * Removes a file from the tree
     * @param string $filename The full filename to remove
     */
    public function rmFile($filename)
    {
        $file = basename($filename);
        $current_tree = $this->getFileObject(dirname($filename));
        $sha = $current_tree->getObjectSHA1ByName($file);
        if(!$sha)
            throw new \RuntimeException('File does not exist. Cannot remove file');
        $current_tree->removeObject($sha);
    }

    /**
     * Updates a file
     * @param string $filename The full filename
     * @param string $data     If set, the data of the file will be updated to this string
     * @param int    $mode     If set, the mode of the file will be updated
     */
    public function updateFile($filename, $data = null, $mode = null)
    {
        $file = basename($filename);
        $current_tree = $this->getFileObject(dirname($filename));

        $sha = $current_tree->getObjectSHA1ByName($file);

        if ($mode !== null) {
            $mode = decoct($mode);
        } else {
            $mode = $current_tree->getObjectMode($sha);
        }
        if ($data !== null) {
            $blob = new Blob($data);
        } else {
            $blob = $current_tree->getObject($sha);
        }

        $current_tree->removeObject($sha);
        $current_tree->addObject($file, $blob, $mode);
    }

    /**
     * Moves a file
     * @param string $origin      The full filename of where the file is located
     * @param string $destination The full filename of the place where the file will be moved to
     */
    public function moveFile($origin, $destination)
    {
        $filedata = $this->getFileObject($origin);
        $filemode = $this->getFileMode($origin);
        $this->rmFile($origin);
        $this->setFileObject($destination, $filedata, $filemode);

    }

    /**
     * Gets the data in a file
     * @param  string $filename The full filename of where the file is located
     * @return string The contents of the file
     */
    public function getFile($filename)
    {
        $object = $this->getFileObject($filename);
        if (!($object instanceof Blob)) {
            throw new \RuntimeException('File is not a file. Cannot read file');
        }

        return $object->getData();
    }

    /**
     * Lists all files in a directory
     * @param  string $dir The directory to list
     * @return array  An array containing all filenames and folders in that directory
     */
    public function dirList($dir = '/')
    {
        $object = $this->getFileObject($dir);
        if (!($object instanceof OTree)) {
            throw new \RuntimeException('File is not a tree. Cannot list files');
        }
        $files = $object->getObjects();
        $ret = array();
        foreach ($files as $file) {
            $ret[]=$file[2];
        }

        return $ret;
    }

    /**
     * Gets the modified tree
     * @return \gihp\Objects\Tree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Gets the Blob object containing the file or the Tree containing the directory
     * @param  string            $filename
     * @param  boolean           $create   Create trees if they do not exist?
     * @return OTree|Blob
     * @throws \RuntimeException
     */
    protected function getFileObject($filename, $create = false)
    {
        $parts = explode('/', $filename);
        $obj = $this->tree;
        foreach ($parts as $chunk) {
            if($chunk == '' || $chunk == '.') continue;
            if ($objectsha = $obj->getObjectSHA1ByName($chunk)) {
                $obj= $obj->getObject($objectsha);
            } elseif ($create) {
                $new_obj = new OTree;
                $obj->addObject($chunk, $new_obj);
                $obj = $new_obj;
            } else {
                throw new \RuntimeException('File does not exist. Cannot read file');
            }
        }

        return $obj;
    }

    /**
     * Sets a file on a path to a new object
     * @param string     $filename
     * @param OTree|Blob $object
     * @param int        $mode
     */
    protected function setFileObject($filename, Internal $object, $mode)
    {
        $tree = $this->getFileObject(dirname($filename), true);
        $tree->addObject(basename($filename), $object, $mode);
    }

    /**
     * Gets the file mode
     * @param  string $filename
     * @return int
     */
    protected function getFileMode($filename)
    {
        $tree = $this->getFileObject(dirname($filename));
        $objectsha = $tree->getObjectSHA1ByName(basename($filename));

        return $tree->getObjectMode($objectsha);
    }

    /**
     * Writes the modified tree to disk
     * @param IOInterface $io The IO to write to
     */
    public function write(IOInterface $io)
    {
        $this->tree->write($io);
    }
}
