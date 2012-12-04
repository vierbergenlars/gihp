<?php

namespace gihp\Object;

use gihp\Defer\Loader as DLoader;
use gihp\Defer\Reference;
use gihp\Defer\Object as Defer;

use gihp\IO\IOInterface;
use gihp\IO\WritableInterface;
use gihp\Metadata\Person;
use gihp\Ref\Tag;

/**
 * An annotated tag
 *
 */
class AnnotatedTag extends Internal implements WritableInterface {
    /**
     * Tag name
     * @var string
     */
    protected $name;
    /**
     * Associated tag message
     * @var string
     */
    protected $message;
    /**
     * The person who tagged it
     * @var Person
     */
    protected $tagger;
    /**
     * The time of tagging
     * @var \DateTime
     */
    protected $date;
    /**
     * The object that was tagged
     * @var Internal
     */
    protected $object;

    /**
     * Creates a new annotated tag
     * @param string $name The name of the tag
     * @param string $message The message to associate with the tag
     * @param Person $tagger The person who tagged the object
     * @param \DateTime $date The time of tagging
     * @param Internan $object The object being tagged, usually a {@link Commit}
     */
    function __construct($name, $message, Person $tagger, \DateTime $date, Internal $object) {
        $this->name = $name;
        $this->message = $message;
        $this->tagger = $tagger;
        $this->date = $date;
        $this->object = $object;
    }

    /**
     * Gets the tag message
     * @return string
     */
    function getMessage() {
        return $this->message;
    }

    /**
     * Gets the person who tagged it
     * @return Person
     */
    function getAuthor() {
        return $this->tagger;
    }

    /**
     * Gets the time of tagging
     * @return \DateTime
     */
    function getDate() {
        return $this->date;
    }

    /**
     * Gets the object that is being tagged, usually a {@ link Commit}
     * @return Internal
     */
    function getObject() {
        return $this->object;
    }

    /**
     * Converts the annotated tag to a raw object
     * @return string
     * @internal
     */
    function __toString() {
        $data = 'object '.$this->object->getSHA1()
            ."\n". 'type '.$this->object->getTypeString()
            ."\n". 'tag '.$this->name
            ."\n". 'tagger '.$this->tagger.' '.$this->date->format('U O')
            ."\n\n".$this->message;
        $this->setData($data);
        return parent::__toString();
    }

    /**
     * Writes the annotated tag to IO
     * @param IOInterface $io The IO to write to
     * @internal
     */
    function write(IOInterface $io) {
        $tag = new Tag($this->name, $this->getSHA1());
        $io->addRef($tag);
        $io->addObject($this);
        $this->object->write($io);
    }

    /**
     * Creates an annotated tag object from raw data
     * @return AnnotatedTag
     * @internal
     */
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
