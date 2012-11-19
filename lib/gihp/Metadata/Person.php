<?php

namespace gihp\Metadata;

class Person
{
    protected $name;
    protected $email;
    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function __toString()
    {
        return $this->name.' <'.$this->email.'>';
    }
}
