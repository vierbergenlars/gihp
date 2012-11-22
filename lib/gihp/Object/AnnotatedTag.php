<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Reference;
use gihp\Defer\Object as Defer;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Metadata\Person;
use gihp\Ref\Tag;

class AnnotatedTag extends Internal implements WritableInterface {
    protected $name;
    protected $message;
    protected $tagger;
    protected $date;
    protected $object;

    function __construct($name, $message, Person $tagger, \DateTime $date, Internal $object) {
        $this->name = $name;
        $this->message = $message;
        $this->tagger = $tagger;
        $this->date = $date;
        $this->object = $object;
    }

    function getMessage() {
        return $this->message;
    }

    function getTagger() {
        return $this->tagger;
    }

    function getDate() {
        return $this->date;
    }

    function getObject() {
        return $this->object;
    }

    function __toString() {
        $data = 'object '.$this->object->getSHA1()
            ."\n". 'type '.$this->object->getTypeString()
            ."\n". 'tag '.$this->name
            ."\n". 'tagger '.$this->tagger.' '.$this->date->format('U O')
            ."\n\n".$this->message;
        $this->setData($data);
        return parent::__toString();
    }

    function write(IOInterface $io) {
        $tag = new Tag($this->name, $this->getSHA1());
        $io->addRef($tag);
        $io->addObject($this);
        $this->object->write($io);
    }

    static function import(DLoader $loader, $tag) {
        list($header, $message) = explode("\n\n", $tag, 2);

        if(!preg_match('/^object ([0-9a-f]{40})\\n'.
        'type (blob|commit|tree)\\n'.
        'tag (.*)\\n'.
        'tagger (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad annotated tag header');
        }

        $object = new Reference($loader, $matches[1]);
        $name = $matches[3];
        $tagger = new Person($matches[4], $matches[5]);
        $date = \DateTime::createFromFormat('U O', $matches[6]);

        return Defer::defer(array(
            'message'=>$message,
            'object'=>$object,
            'name'=>$name,
            'tagger'=>$tagger,
            'date'=>$date
        ), __CLASS__);
    }
}
