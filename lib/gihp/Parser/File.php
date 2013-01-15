<?php

namespace gihp\Parser;

use gihp\Object\Loader as OLoader;
use gihp\Defer\Object as Defer;
use gihp\Defer\Reference;

use gihp\Object\Internal;
use gihp\Object\Blob;
use gihp\Object\Commit;
use gihp\Object\AnnotatedTag;
use gihp\Object\Tree;

class File {
    /**
     * Imports an object
     * 
     * @param \gihp\Object\Loader $loader
     * @param string $string
     * @return \gihp\Object\Internal
     * @throws \RuntimeException
     * @throws \LogicException
     */
    static function importObject(OLoader $loader, $string) {
        $parts = explode("\0", $string, 2);
        $header = $parts[0];
        $data = $parts[1];

        if(!preg_match('/^(commit|blob|tree|tag) ([0-9]+)$/', $header, $matches)) {
            throw new \RuntimeException('Bad object header');
        }
        $type = $matches[1];
        $length = (int)$matches[2];

        if(strlen($data) !== $length) {
            throw new \RuntimeException('Data length mismatch');
        }
        switch($type) {
            case 'commit':
                return self::importCommit($loader, $data);
            case 'blob':
                return self::importBlob($loader, $data);
            case 'tree':
                return self::importTree($loader, $data);
            case 'tag':
                return self::importTag($loader, $data);
            default:
                throw new \LogicException('Bad object type. Should have been checked already');
        }
    }
    
    /**
     * Creates a commit
     * @param \gihp\Object\Loader $loader
     * @param string $commit
     * @return \gihp\Object\Commit
     * @throws \RuntimeException
     */
    static private function importCommit(OLoader $loader, $commit) {
        $parts = explode("\n\n", $commit, 2);
        $message = $parts[1];
        $header = $parts[0];


        if(!preg_match('/^tree ([0-9a-f]{40})\\n'.
        '((parent [0-9a-f]{40}\\n)*)'.
        'author (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})\\n'.
        'committer (.*) <(.*)> ([0-9]{10} [+-][0-9]{4})$/', $header, $matches)) {
            throw new \RuntimeException('Bad commit object');
        }
        $tree = $matches[1];
        $tree = new Reference($loader, $tree);
        $parsed_parents = array();
        $parents = explode("\n", $matches[2]);
        foreach($parents as $parent) {
            if(trim($parent) == '') continue;
            if(!preg_match('/^parent ([0-9a-f]{40})$/', $parent, $pmatches)) {
                throw new \RuntimeException('Bad commit object: parsing parents failed');
            }
            $parsed_parents[] = new Reference($loader, $pmatches[1]);
        }
        $parent = $parsed_parents;
        $author = new \gihp\Metadata\Person($matches[4], $matches[5]);
        $author_time = \DateTime::createFromFormat('U O', $matches[6]);
        $committer = new \gihp\Metadata\Person($matches[7], $matches[8]);
        $commit_time = \DateTime::createFromFormat('U O', $matches[9]);
        return Defer::defer(
            array(
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
     * @param \gihp\Object\Loader $loader
     * @param string $data
     * @return \gihp\Object\Blob
     */
    static private function importBlob(OLoader $loader, $data) {
        return new \gihp\Object\Blob($data);
    }
    
    /**
     * Creates a tree
     * @param \gihp\Object\Loader $loader
     * @param string $tree
     * @return \gihp\Object\Tree
     */
    static private function importTree(OLoader $loader, $tree) {
        $l = strlen($tree);
        $objects = array();
        $names = array();
        for($i=0; $i < $l;) {
            $mode = '';
            do {
                if($tree[$i] === chr(32)) break;
                $mode.=$tree[$i];
            } while(++$i);
            $i++;
            $filename = '';
            do {
                if($tree[$i] === "\0") break;
                $filename.=$tree[$i];
            } while(++$i);
            $i++;
            $bin_sha = substr($tree, $i, 20);
            $i+=20;
            $sha = unpack('H*', $bin_sha);
            $sha1 = $sha[1];
            $objects[$sha1] = array(new Reference($loader, $sha1), $mode, $filename);
            $names[$filename] = $sha1;
        }
        return Defer::defer(array('objects'=>$objects,'names'=>$names), 'gihp\\Object\\Tree');
    }
    
    /**
     * Creates an annotated tag
     * @param \gihp\Object\Loader $loader
     * @param string $tag
     * @return \gihp\Object\AnnotatedTag
     * @throws \RuntimeException
     */
    static private function importTag(OLoader $loader, $tag) {
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
        ), 'gihp\\Object\\AnnotatedTag');
    }
    
    /**
     * Exports an object
     * @param \gihp\Object\Internal $object
     * @return string
     * @throws \LogicException
     */
    static function exportObject(Internal $object) {
        if($object instanceof Commit) {
            $data = self::exportCommit($object);
            $type = 'commit';
        }
        else if($object instanceof Blob) {
            $data = self::exportBlob($object);
            $type = 'blob';
        }
        else if($object instanceof Tree) {
            $data = self::exportTree($object);
            $type = 'tree';
        }
        else if($object instanceof AnnotatedTag) {
            $data = self::exportTag($object);
            $type = 'tag';
        }
        else {
            throw new \LogicException('Bad object type');
        }
        
        $header = $type.' '.strlen($data).chr(0);
        return $header.$data;
    }
    
    /**
     * Exports a commit
     * @param \gihp\Object\Commit $commit
     * @return string
     */
    static private function exportCommit(Commit $commit) {
        $data = 'tree '.$commit->getTree()->getSHA1();
        foreach($commit->getParents() as $parent) {
            $data.="\n".'parent '.$parent->getSHA1();
        }
        $data.="\n".'author '.$commit->getAuthor().' '.$commit->getAuthorTime()->format('U O');
        $data.="\n".'committer '.$commit->getCommitter().' '.$commit->getCommitTime()->format('U O');
        $data.="\n\n".$commit->getMessage();
        return $data;
    }
    
    /**
     * Exports a blob
     * @param \gihp\Object\Blob $blob
     * @return string
     */
    static private function exportBlob(Blob $blob) {
        return $blob->getData();
    }
    
    /**
     * Exports a tree
     * @param \gihp\Object\Tree $tree
     * @return string
     */
    static private function exportTree(Tree $tree) {
        $data = '';
        foreach($tree->getObjects() as $object) {
            $data.=$object[1].' '.$object[2].chr(0).pack('H*', $object[0]->getSHA1());
        }
        return $data;
    }
    
    /**
     * Exports a tag
     * @param \gihp\Object\AnnotatedTag $tag
     * @return string
     */
    static private function exportTag(AnnotatedTag $tag) {
        return 'object '.$tag->getObject()->getSHA1()
        ."\n". 'type '.$tag->getObject()->getTypeString()
        ."\n". 'tag '.$tag->getName()
        ."\n". 'tagger '.$tag->getAuthor().' '.$tag->getDate()->format('U O')
        ."\n\n".$tag->getMessage();
    }
}
