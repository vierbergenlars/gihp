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

namespace gihp\IO\File;

/**
 * Expands packed references
 * @internal
 */
class Packref
{
    protected $dir;
    public function __construct($git_dir)
    {
        $this->dir = $git_dir;
        if (file_exists($this->dir.'/packed-refs')) {
            $this->unpack_refs();
        }
    }

    private function unpack_refs()
    {
        $data = file_get_contents($this->dir.'/packed-refs');
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            $char1 = substr(trim($line),0,1);
            if($char1=="#"||$char1=='^'||$line == "") continue; // Ignore the comment on top and annotated tags.
            list($ref, $name) = explode(" ", $line, 2);
            if(!file_exists($this->dir.'/'.$name))
                file_put_contents($this->dir.'/'.$name, $ref);
        }
    }

}
