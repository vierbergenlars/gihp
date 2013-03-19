<?php
/**
 * Copyright (c) 2013 Lars Vierbergen
 * 
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace gihp\Metadata;

/**
 * A person that is attached to the metadata
 */
class Person
{
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
     * @param string $name  The real name of the person
     * @param string $email The email address of the person
     */
    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * The name of the person
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The email address of the person
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * Generates the identification string
     * @internal
     */
    public function __toString()
    {
        return $this->name.' <'.$this->email.'>';
    }

    /**
     * Parses an identification string to a person
     * @param  string            $str A string in the format {name} <{email}>
     * @return Person            A new Person constructed with these data
     * @throws \RuntimeException When an invalid identification string is given
     */
    public static function parse($str)
    {
        if(!preg_match('/(.*) <(.*)>/', $str, $matches))
                throw new \RuntimeException('Invalid person identification string');

        return new self($matches[1], $matches[2]);
    }
}
