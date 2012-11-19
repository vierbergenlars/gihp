<?php

namespace gihp\Object;

class Commit extends Internal
{
    protected $message;
    protected $tree;
    protected $author;
    protected $author_time;
    protected $committer;
    protected $commit_time;
    protected $parent;
    public function __construct($message, Tree $root_tree, \gihp\Metadata\Person $author, \DateTime $date=null, Commit $parent=null)
    {
        $this->message = $message;
        $this->tree = $root_tree;
        $this->author = $this->committer = $author;
        if($date === null) $date = new \DateTime;
        $this->author_time = $this->commit_time = $date;
        $this->{'parent'} = $parent;
        parent::__construct(parent::COMMIT);
    }

    public function setCommitter(\gihp\Metadata\Person $committer, \DateTime $date=null)
    {
        $this->committer = $committer;
        if($date === null) $date = new \DateTime;
        $this->commit_time = $date;
    }

    public function __toString()
    {
        $data = 'tree '.$this->tree->getSHA1();
        if($this->{'parent'})
            $data.= "\n".'parent '.$this->{'parent'}->getSHA1();
        $data.="\n".'author '.$this->author.' '.$this->author_time->format('U O');
        $data.="\n".'committer '.$this->committer.' '.$this->commit_time->format('U O');
        $data.="\n\n".$this->message;
        $this->setData($data);

        return parent::__toString();
    }

}
