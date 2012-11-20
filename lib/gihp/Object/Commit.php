<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Reference;
use gihp\Defer\Object as Defer;

class Commit extends Internal {
    protected $message;
    protected $tree;
    protected $author;
    protected $author_time;
    protected $committer;
    protected $commit_time;
    protected $parent;
    function __construct($message, Tree $root_tree, \gihp\Metadata\Person $author, \DateTime $date=null, Commit $parent=null) {
        $this->message = $message;
        $this->tree = $root_tree;
        $this->author = $this->committer = $author;
        if($date === null) $date = new \DateTime;
        $this->author_time = $this->commit_time = $date;
        $this->{'parent'} = $parent;
        parent::__construct(parent::COMMIT);
    }

    function setCommitter(\gihp\Metadata\Person $committer, \DateTime $date=null) {
        $this->committer = $committer;
        if($date === null) $date = new \DateTime;
        $this->commit_time = $date;
    }

    function getMessage() {
        return $this->message;
    }

    function getTree() {
        return $this->tree;
    }

    function getAuthor() {
        return $this->author;
    }

    function getAuthorTime() {
        return $this->author_time;
    }

    function getCommitter() {
        return $this->committer;
    }

    function getCommitTime() {
        return $this->commit_time;
    }

    function getParent() {
        return $this->{'parent'};
    }

    function __toString() {
        $data = 'tree '.$this->tree->getSHA1();
        if($this->{'parent'})
            $data.= "\n".'parent '.$this->{'parent'}->getSHA1();
        $data.="\n".'author '.$this->author.' '.$this->author_time->format('U O');
        $data.="\n".'committer '.$this->committer.' '.$this->commit_time->format('U O');
        $data.="\n\n".$this->message;
        $this->setData($data);
        return parent::__toString();
    }

    /**
     * Imports the commit object
     * @param Loader $loader The object loader
     * @param string $commit The raw commit data
     * @return Commit An instanciated commit that was represented by the raw data
     */
    static function import(DLoader $loader, $commit) {
        $parts = explode("\n\n", $commit, 2);
        $message = $parts[1];
        $header = $parts[0];


        if(!preg_match('/^tree ([0-9a-f]{40})\\n'.
        '(parent ([0-9a-f]{40})\\n)?'.
        'author (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})\\n'.
        'committer (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad commit object');
        }

        $tree = $matches[1];
        $tree = new Reference($loader, $tree);
        $parent = $matches[3];
        if($parent === '') $parent = null;
        else $parent = new Reference($loader, $parent);
        $author = new \gihp\Metadata\Person($matches[4], $matches[5]);
        $author_time = \DateTime::createFromFormat('U O', $matches[6]);
        $committer = new \gihp\Metadata\Person($matches[7], $matches[8]);
        $commit_time = \DateTime::createFromFormat('U O', $matches[9]);
        return Defer::defer(
            array(
                'message'=>$message,
                'tree'=>$tree,
                'parent'=>$parent,
                'author'=>$author,
                'author_time'=>$author_time,
                'committer'=>$committer,
                'commit_time'=>$commit_time
            ), __CLASS__);
    }

}
