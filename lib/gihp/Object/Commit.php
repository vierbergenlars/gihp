<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Reference;
use gihp\Defer\Object as Defer;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Metadata\Person;

/**
 * Represents a commit
 */
class Commit extends Internal implements WritableInterface {
    /**
     * Commit message
     * @var string
     */
    protected $message;
    /**
     * Root tree
     * @var Tree
     */
    protected $tree;
    /**
     * Commit author
     * @var Person
     */
    protected $author;
    /**
     * Time the commit was authored
     * @var \DateTime
     */
    protected $author_time;
    /**
     * Committer
     * @var Person
     */
    protected $committer;
    /**
     * Time the commit was made
     * @var \DateTime
     */
    protected $commit_time;
    /**
     * Parent commits, if any
     * @var array
     */
    protected $parents = array();
    /**
     * Creates a new commit object
     * @param string $message Commit message
     * @param Tree $root_tree The root tree describing the state of the working tree
     * @param Person $author The commit author
     * @param \DateTime $date The time the commit was made. If not set, assume now
     * @param Commit $parent The parent commit. If not set, this is the first commit
     */
    function __construct($message, Tree $root_tree, Person $author, \DateTime $date=null, Commit $parent=null) {
        $this->message = $message;
        $this->tree = $root_tree;
        $this->author = $this->committer = $author;
        if($date === null) $date = new \DateTime;
        $this->author_time = $this->commit_time = $date;
        $this->{'parents'}[] = $parent;
        parent::__construct(parent::COMMIT);
    }

    /**
     * Sets the committer.
     *
     * It is assumed the author also is the committer and the time the commit was made is the commit time.
     * If this is incorrect, set the committer and the commit time.
     *
     * @param Person $committer The actual committer
     * @param \DateTime $date The date the commit was made. If null, assume now.
     */
    function setCommitter(Person $committer, \DateTime $date=null) {
        $this->committer = $committer;
        if($date === null) $date = new \DateTime;
        $this->commit_time = $date;
    }

    /**
     * Gets the commit message
     * @return string
     */
    function getMessage() {
        return $this->message;
    }

    /**
     * Get the commit's root tree
     * @return Tree
     */
    function getTree() {
        return $this->tree;
    }

    /**
     * Get the commit author
     * @return Person
     */
    function getAuthor() {
        return $this->author;
    }

    /**
     * Gets the time the commit was authored
     * @return \DateTime
     */
    function getAuthorTime() {
        return $this->author_time;
    }

    /**
     * Gets the committer
     * @return Person
     */
    function getCommitter() {
        return $this->committer;
    }

    /**
     * Gets the commit time
     * @return \DateTime
     */
    function getCommitTime() {
        return $this->commit_time;
    }

    /**
     * Gets the parent commit
     * @return array
     */
    function getParents() {
        return $this->{'parents'};
    }

    /**
     * Converts the commit to raw data.
     * @return string
     */
    function __toString() {
        $data = 'tree '.$this->tree->getSHA1();
        foreach($this->parents as $parent) {
            $data.="\n".'parent '.$parent->getSHA1();
        }
        $data.="\n".'author '.$this->author.' '.$this->author_time->format('U O');
        $data.="\n".'committer '.$this->committer.' '.$this->commit_time->format('U O');
        $data.="\n\n".$this->message;
        $this->setData($data);
        return parent::__toString();
    }

    /**
     * Writes the commit and its dependencies to IO
     */
    function write(IOInterface $io) {
        $io->addObject($this);
        foreach($this->parents as $parent) {
            $parent->write($io);
        }
        $this->tree->write($io);
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
        '((parent [0-9a-f]{40}\\n)*)'.
        'author (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})\\n'.
        'committer (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad commit object');
        }
        $tree = $matches[1];
        $tree = new Reference($loader, $tree);
        $parsed_parents = array();
        $parents = explode("\n", $matches[2]);
        foreach($parents as $parent) {
            if(trim($parent) == '') continue;
            if(!preg_match('/^parent ([0-9a-f]{40})$/', $parent, $pmatches)) {
                throw new \RuntimeException('Bad commit object: parsing parents failed');
            }
            $parsed_parents[] = new Reference($loader, $pmatches[1]);
        }
        $parent = $parsed_parents;
        $author = new \gihp\Metadata\Person($matches[4], $matches[5]);
        $author_time = \DateTime::createFromFormat('U O', $matches[6]);
        $committer = new \gihp\Metadata\Person($matches[7], $matches[8]);
        $commit_time = \DateTime::createFromFormat('U O', $matches[9]);
        return Defer::defer(
            array(
                'message'=>$message,
                'tree'=>$tree,
                'parents'=>$parent,
                'author'=>$author,
                'author_time'=>$author_time,
                'committer'=>$committer,
                'commit_time'=>$commit_time
            ), __CLASS__);
    }

}
