<?php

namespace gihp\Ref;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Object as Defer;
use gihp\Defer\Reference as DReference;
use gihp\Object\Commit;

/**
 * Head reference
 *
 * Points to the tip of a branch
 */
class Head extends Reference
{
    /**
     * The commit that is referenced
     * @var Commit
     */
    protected $commit;
    /**
     * The name of the head
     * @internal the branch name
     * @var string
     */
    protected $name;
    /**
     * Creates a new head reference
     * @internal creates a new branch
     * @param string $name   The name of the head reference
     * @param Commit $commit The commit the reference points to
     */
    public function __construct($name, Commit $commit)
    {
        $this->name = $name;
        $this->commit = $commit;
        parent::__construct($name);
    }

    /**
     * Updates the commit the reference points to
     * @param Commit $commmit
     */
    public function setCommit(Commit $commit)
    {
        $this->commit = $commit;
    }

    /**
     * Gets the commit the reference points to
     * @return Commit
     */
    public function getCommit()
    {
        return $this->commit;
    }
    /**
     * Gets the name of the head reference
     * @internal the branche's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @internal
     */
    public function getPath()
    {
        $this->setData($this->name."\0".$this->commit->getSHA1());

        return parent::getPath();
    }
    /**
     * @internal
     */
    public function getData()
    {
        $this->setData($this->name."\0".$this->commit->getSHA1());

        return parent::getData();
    }

    /**
     * Loads the head reference from raw data
     */
    public static function import(Dloader $loader, $data)
    {
        list($name, $ref) = explode("\0", $data);
        $ref = substr($ref, 0, 40);

        return Defer::defer(array('commit'=>new DReference($loader->getObjectLoader(), $ref), 'name'=>$name), __CLASS__);
    }
}
