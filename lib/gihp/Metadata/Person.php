<?php

namespace gihp\Metadata;

/**
 * A person that is attached to the metadata
 */
class Person {
    /**
     * The name
     */
    protected $name;
    /**
     * The email address
     */
    protected $email;
    /**
     * Creates a new person
     * @param string $name The real name of the person
     * @param string $email The email address of the person
     */
    function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * The name of the person
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * The email address of the person
     * @return string
     */
    function getEmail() {
        return $this->email;
    }
    /**
     * Generates the identification string
     * @internal
     */
    function __toString() {
        return $this->name.' <'.$this->email.'>';
    }
}
