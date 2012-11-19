<?php

namespace gihp\Metadata;

class Person {
    protected $name;
    protected $email;
    function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }

    function __toString() {
        return $this->name.' <'.$this->email.'>';
    }
}
