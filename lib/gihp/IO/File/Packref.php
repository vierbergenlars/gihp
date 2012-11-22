<?php

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
            if(substr(trim($line),0,1)=="#"||$line == "") continue; // Ignore the comment on top
            list($ref, $name) = explode(" ", $line, 2);
            if(!file_exists($this->dir.'/'.$name))
                file_put_contents($this->dir.'/'.$name, $ref);
        }
    }

}
