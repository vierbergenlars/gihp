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

namespace gihp\Parser;

use gihp\Defer\Object as Defer;
use gihp\Defer\Reference as DReference;

use gihp\Object\Loader as OLoader;
use gihp\Object\Internal;
use gihp\Object\Blob;
use gihp\Object\Commit;
use gihp\Object\AnnotatedTag;
use gihp\Object\Tree;

use gihp\Ref\Loader as RLoader;
use gihp\Ref\Reference;
use gihp\Ref\Head;
use gihp\Ref\Tag;

use gihp\Symref\SymbolicReference;

use gihp\Metadata\Person;

/**
 * Object importing and exporting for file storage
 * @internal
 */
class File
{
    /**
     * Imports an object
     *
     * @param  \gihp\Object\Loader   $loader
     * @param  string                $string
     * @param  string                $sha1
     * @return \gihp\Object\Internal
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public static function importObject(OLoader $loader, $string, $sha1)
    {
        $parts = explode("\0", $string, 2);
        $header = $parts[0];
        $data = $parts[1];

        if (!preg_match('/^(commit|blob|tree|tag) ([0-9]+)$/', $header, $matches)) {
            throw new \RuntimeException('Bad object header');
        }
        $type = $matches[1];
        $length = (int) $matches[2];

        if (strlen($data) !== $length) {
            throw new \RuntimeException('Data length mismatch');
        }
        switch ($type) {
            case 'commit':
                return self::importCommit($loader, $data, $sha1);
            case 'blob':
                return self::importBlob($loader, $data, $sha1);
            case 'tree':
                return self::importTree($loader, $data, $sha1);
            case 'tag':
                return self::importAnnotatedTag($loader, $data, $sha1);
            default:
                throw new \LogicException('Bad object type. Should have been checked already');
        }
    }

    /**
     * Creates a commit
     * @param  \gihp\Object\Loader $loader
     * @param  string              $commit
     * @return \gihp\Object\Commit
     * @throws \RuntimeException
     */
    private static function importCommit(OLoader $loader, $commit, $sha1)
    {
        $parts = explode("\n\n", $commit, 2);
        $message = $parts[1];
        $header = $parts[0];

        if(!preg_match('/^tree ([0-9a-f]{40})\\n'.
        '((parent [0-9a-f]{40}\\n)*)'.
        'author (.*) <(.*)> ([0-9]+ [+-][0-9]{4})\\n'.
        'committer (.*) <(.*)> ([0-9]+ [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad commit object');
        }
        $tree = $matches[1];
        $tree = new DReference($loader, $tree);
        $parsed_parents = array();
        $parents = explode("\n", $matches[2]);
        foreach ($parents as $parent) {
            if(trim($parent) == '') continue;
            if (!preg_match('/^parent ([0-9a-f]{40})$/', $parent, $pmatches)) {
                throw new \RuntimeException('Bad commit object: parsing parents failed');
            }
            $parsed_parents[] = new DReference($loader, $pmatches[1]);
        }
        $parent = $parsed_parents;
        $author = new \gihp\Metadata\Person($matches[4], $matches[5]);
        $author_time = \DateTime::createFromFormat('U O', $matches[6]);
        $committer = new \gihp\Metadata\Person($matches[7], $matches[8]);
        $commit_time = \DateTime::createFromFormat('U O', $matches[9]);

        return Defer::defer(
            array(
                'sha1'=>$sha1,
                'message'=>$message,
                'tree'=>$tree,
                'parents'=>$parent,
                'author'=>$author,
                'author_time'=>$author_time,
                'committer'=>$committer,
                'commit_time'=>$commit_time
            ), 'gihp\\Object\\Commit');
    }

    /**
     * Creates a new blob
     * @param  \gihp\Object\Loader $loader
     * @param  string              $data
     * @return \gihp\Object\Blob
     */
    private static function importBlob(OLoader $loader, $data, $sha1)
    {
        return Defer::defer(array('sha1'=>$sha1, 'data'=>$data), 'gihp\\Object\\Blob');
    }

    /**
     * Creates a tree
     * @param  \gihp\Object\Loader $loader
     * @param  string              $tree
     * @return \gihp\Object\Tree
     */
    private static function importTree(OLoader $loader, $tree, $sha1_tree)
    {
        $l = strlen($tree);
        $objects = array();
        $names = array();
        for ($i=0; $i < $l;) {
            $mode = '';
            do {
                if($tree[$i] === chr(32)) break;
                $mode.=$tree[$i];
            } while (++$i);
            $i++;
            $filename = '';
            do {
                if($tree[$i] === "\0") break;
                $filename.=$tree[$i];
            } while (++$i);
            $i++;
            $bin_sha = substr($tree, $i, 20);
            $i+=20;
            $sha = unpack('H*', $bin_sha);
            $sha1 = $sha[1];
            $objects[$sha1] = array(new DReference($loader, $sha1), $mode, $filename);
            $names[$filename] = $sha1;
        }

        return Defer::defer(array('objects'=>$objects,'names'=>$names, 'sha1'=>$sha1_tree), 'gihp\\Object\\Tree');
    }

    /**
     * Creates an annotated tag
     * @param  \gihp\Object\Loader       $loader
     * @param  string                    $tag
     * @return \gihp\Object\AnnotatedTag
     * @throws \RuntimeException
     */
    private static function importAnnotatedTag(OLoader $loader, $tag, $sha1)
    {
        list($header, $message) = explode("\n\n", $tag, 2);

        if(!preg_match('/^object ([0-9a-f]{40})\\n'.
        'type (blob|commit|tree)\\n'.
        'tag (.*)\\n'.
        'tagger (.*) <(.*)> ([0-9]+ [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad annotated tag header');
        }

        $object = new DReference($loader, $matches[1]);
        $name = $matches[3];
        $tagger = new Person($matches[4], $matches[5]);
        $date = \DateTime::createFromFormat('U O', $matches[6]);

        return Defer::defer(array(
            'sha1'=>$sha1,
            'message'=>$message,
            'object'=>$object,
            'name'=>$name,
            'tagger'=>$tagger,
            'date'=>$date
        ), 'gihp\\Object\\AnnotatedTag');
    }

    /**
     * Exports an object
     * @param  \gihp\Object\Internal $object
     * @return string
     * @throws \LogicException
     */
    public static function exportObject(Internal $object)
    {
        $type = self::getObjectTypeString($object);
        switch ($type) {
            case 'commit':
                $data = self::exportCommit($object);
                break;
            case 'blob':
                $data = self::exportBlob($object);
                break;
            case 'tree':
                $data = self::exportTree($object);
                break;
            case 'tag':
                $data = self::exportAnnotatedTag($object);
                break;
            default:
                throw new \LogicException('Bad object type');
        }

        $header = $type.' '.strlen($data).chr(0);

        return $header.$data;
    }

    /**
     * Exports a commit
     * @param  \gihp\Object\Commit $commit
     * @return string
     */
    private static function exportCommit(Commit $commit)
    {
        $data = 'tree '.$commit->getTree()->getSHA1();
        foreach ($commit->getParents() as $parent) {
            $data.="\n".'parent '.$parent->getSHA1();
        }
        $data.="\n".'author '.$commit->getAuthor().' '.$commit->getAuthorTime()->format('U O');
        $data.="\n".'committer '.$commit->getCommitter().' '.$commit->getCommitTime()->format('U O');
        $data.="\n\n".$commit->getMessage();

        return $data;
    }

    /**
     * Exports a blob
     * @param  \gihp\Object\Blob $blob
     * @return string
     */
    private static function exportBlob(Blob $blob)
    {
        return $blob->getData();
    }

    /**
     * Exports a tree
     * @param  \gihp\Object\Tree $tree
     * @return string
     */
    private static function exportTree(Tree $tree)
    {
        $data = '';
        foreach ($tree->getObjects() as $object) {
            $data.=$object[1].' '.$object[2].chr(0).pack('H*', $object[0]->getSHA1());
        }

        return $data;
    }

    /**
     * Exports a tag
     * @param  \gihp\Object\AnnotatedTag $tag
     * @return string
     */
    private static function exportAnnotatedTag(AnnotatedTag $tag)
    {
        return 'object '.$tag->getObject()->getSHA1()
        ."\n". 'type '.self::getObjectTypeString($tag->getObject())
        ."\n". 'tag '.$tag->getName()
        ."\n". 'tagger '.$tag->getAuthor().' '.$tag->getDate()->format('U O')
        ."\n\n".$tag->getMessage();
    }

    /**
     * Gets the type of an object as a string
     *
     * @param  \gihp\Object\Internal $object
     * @return string
     */
    private static function getObjectTypeString(Internal $object)
    {
        if ($object instanceof Commit) {
            $type = 'commit';
        } elseif ($object instanceof Blob) {
            $type = 'blob';
        } elseif ($object instanceof Tree) {
            $type = 'tree';
        } elseif ($object instanceof AnnotatedTag) {
            $type = 'tag';
        } else {
            throw new \LogicException('Bad object type');
        }

        return $type;
    }

    /**
     * Creates a reference
     * @param  \gihp\Ref\Loader    $loader
     * @param  string              $contents
     * @param  string              $path
     * @return \gihp\Ref\Reference
     * @throws \LogicException
     */
    public static function importRef(OLoader $loader, $data, $path)
    {
        list($type, $name) = explode('/', $path, 2);
        $ref = substr($data, 0, 40);
        switch ($type) {
            case 'tags':
                $type = 'gihp\\Ref\\Tag';
                break;
            case 'heads':
                $type = 'gihp\\Ref\\Head';
                break;
            default:
                throw new \LogicException('Bad reference type');
        }

        return Defer::defer(array(
            'commit'=> new DReference($loader, $ref),
            'name'=>$name
        ), $type);
    }

    /**
     * Exports a reference
     * @param  \gihp\Ref\Reference $ref
     * @return array
     * @throws \LogicException
     */
    public static function exportRef(Reference $ref)
    {
        if ($ref instanceof Tag) {
            $type = 'tag';
        } elseif ($ref instanceof Head) {
            $type = 'head';
        } else {
            throw new \LogicException('Bad reference type');
        }
        $path =$ref->getName();
        $data = $ref->getObject()->getSHA1();

        return array($path, $type, $data);
    }

    /**
     * Creates a symbolic reference
     * @param  \gihp\IO\IOInterface          $io
     * @param  string                        $data
     * @return gihp\Symref\SymbolicReference
     */
    public static function importSymRef(\gihp\IO\IOInterface $io, $data)
    {
        if (substr($data, 0, 4) == 'ref:') {
            $head = str_replace('refs/heads/', 'heads/', substr($data, 5));

            return Defer::defer(
                    array(
                        'head'=>new DReference(new RLoader($io), trim($head))
                    ),
                    'gihp\\Symref\\SymbolicReference'
                    );
        } else {
            return Defer::defer(
                    array(
                        'head'=> new DReference(new OLoader($io), trim($data))
                    ),
                    'gihp\\Symref\\SymbolicReference'
                    );
        }
    }

    /**
     * Exports a symbolic reference
     * @param  \gihp\Symref\SymbolicReference $symref
     * @return string
     */
    public static function exportSymRef(SymbolicReference $symref)
    {
        if ($symref->isSymbolic()) {
            return 'ref: refs/heads/'.$symref->getHead()->getName();
        } else {
            return $symref->getSHA1();
        }
    }
}
