<?php

namespace gihp;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Object\Tree as OTree;
use gihp\Object\Blob;

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
    public function __construct(OTree $previous)
    {
        $this->tree = clone $previous;
    }

    /**
     * Add a new file to the tree
     * @param string $filename The full filename to add to the tree
     * @param string $data     The contents for the file
     * @param int    $mode     The permissions for the file
     */
    public function addFile($filename, $data, $mode=0644)
    {
        $mode = decoct($mode);
        $parts = explode('/', $filename);
        $file = array_pop($parts);
        $current_tree = $this->tree;
        foreach ($parts as $chunk) {
            if ($objectsha = $current_tree->getObjectSHA1ByName($chunk)) {
                $subtree = $current_tree->getObject($objectsha);
                if (!($subtree instanceof OTree)) {
                    throw new \RuntimeException($objectsha .' is not a directory. Cannot add file');
                }
                $current_tree = $subtree;
            } else {
                $new_trees = array();
                $new_trees[]= array(new OTree, $chunk);
                while (($dir = next($parts)) !== false) {
                    $new_trees[] = array(new Tree, $dir);
                }
                $this_tree = $current_tree;
                foreach ($new_trees as $info) {
                    $this_tree->addObject($info[1], $info[0]);
                    $this_tree = $info[0];
                }
                $current_tree = $this_tree;
                break;
            }
        }
        $blob = new Blob($data);
        $current_tree->addObject($file, $blob, $mode);
    }

    /**
     * Removes a file from the tree
     * @param string $filename The full filename to remove
     */
    public function rmFile($filename)
    {
        $parts = explode('/', $filename);
        $file = array_pop($parts);
        $current_tree = $this->tree;
        foreach ($parts as $chunk) {
            if ($objectsha = $current_tree->getObjectSHA1ByName($chunk)) {
                $current_tree= $current_tree->getObject($objectsha);
            } else {
                throw new \RuntimeException('File does not exist. Cannot remove file');
            }
        }
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
        $parts = explode('/', $filename);
        $file = array_pop($parts);
        $current_tree = $this->tree;
        foreach ($parts as $chunk) {
            if ($objectsha = $current_tree->getObjectSHA1ByName($chunk)) {
                $current_tree= $current_tree->getObject($objectsha);
            } else {
                throw new \RuntimeException('File does not exist. Cannot update file');
            }
        }
        $sha = $current_tree->getObjectSHA1ByName($file);
        if (!$sha) {
            throw new \RuntimeException('File does not exist. Cannot update file');
        }

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
        $this->rmFile($origin);
        $this->addFile($destination, $filedata);

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
     * Gets the modified tree
     * @return OTree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Gets the Blob object containing the file
     * @param  string $filename The full filename
     * @return Blob
     */
    protected function getFileObject($filename)
    {
        $parts = explode('/', $filename);
        $file = array_pop($parts);
        $current_tree = $this->tree;
        foreach ($parts as $chunk) {
            if ($objectsha = $current_tree->getObjectSHA1ByName($chunk)) {
                $current_tree= $current_tree->getObject($objectsha);
            } else {
                throw new \RuntimeException('File does not exist. Cannot read file');
            }
        }
        $sha = $current_tree->getObjectSHA1ByName($file);
        if (!$sha) {
            throw new \RuntimeException('File does not exist. Cannot read file');
        }
        $object = $current_tree->getObject($sha);

        return $object;

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