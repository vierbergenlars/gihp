<?php
/**
 * Copyright (c) 2013 Lars Vierbergen, Patrik Fimml
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
 * Hacking away on packfiles
 * https://github.com/patrikf/glip/blob/50edbd9c77eda1aa91fbb971403492b635cf9482/lib/git.class.php
 * @internal
 */
class Packfile
{
    const OBJ_NONE = 0;
    const OBJ_COMMIT = 1;
    const OBJ_TREE = 2;
    const OBJ_BLOB = 3;
    const OBJ_TAG = 4;
    const OBJ_OFS_DELTA = 6;
    const OBJ_REF_DELTA = 7;

    public static function getTypeID($name)
    {
        if ($name == 'commit')
        return self::OBJ_COMMIT;
        else if ($name == 'tree')
        return self::OBJ_TREE;
        else if ($name == 'blob')
        return self::OBJ_BLOB;
        else if ($name == 'tag')
        return self::OBJ_TAG;
        throw new \LogicException(sprintf('unknown type name: %s', $name));
    }

    public static function getTypeName($type)
    {
        if ($type == self::OBJ_COMMIT)
        return 'commit';
        else if ($type == self::OBJ_TREE)
        return 'tree';
        else if ($type == self::OBJ_BLOB)
        return 'blob';
        else if ($type == self::OBJ_TAG)
        return 'tag';
        throw new Exception(sprintf('no string representation of type %d', $type));
    }
    private $packs = array();
    protected $cache = array();
    protected $dir;
    /**
     * Creates a new packfile handler
     * @param string $dir The /objects/ directory
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
        if(!is_dir($this->dir)) throw new \RuntimeException('Not a directory');
        $handle = opendir($this->dir.'/pack');
        while (($file = readdir($handle)) !==false) {
            if (preg_match('/^pack-([0-9a-fA-F]{40})\.idx$/', $file, $matches)) {
                $this->packs[] = $matches[1];
            }
        }
        closedir($handle);
    }

    /**
     * Reads an unsigned integer from a file
     * @param  resource $handle The open file handle
     * @return int
     */
    public static function bin_fuint32($handle)
    {
        return self::bin_uint32(fread($handle, 4));
    }

    /**
     * Converts a binary string to an unsigned integer
     * @param  string $str The string to extract the integer from
     * @param  int    $pos The starting position in the string
     * @return int
     */
    public static function bin_uint32($str, $pos = 0)
    {
        $a = unpack('Nx', substr($str, $pos, 4));

        return $a['x'];
    }

    /**
     * I have no idea what this thing does, but it seems important
     * @var string $str
     * @var int $pos
     * @return int
     */
    public static function git_varint($str, &$pos = 0)
    {
        $r = 0;
        $c = 0x80;
        for ($i = 0; $c & 0x80; $i += 7) {
            $c = ord($str{$pos++});
            $r |= (($c & 0x7F) << $i);
        }

        return $r;
    }
    /**
     * Tries to find the file in the fanout table
     * @param  resource $handle   Pointer to the index file
     * @param  string   $sha1_bin Binary sha of the object
     * @param  string   $offset   Start looking for the object in the table at this location
     * @return array    The range where the object can be located
     */
    public static function readFanOut($handle, $object_name, $offset)
    {
        if ($object_name[0] == "\x00") {
            $cur = 0;
            fseek($handle, $offset);
            $after = self::bin_fuint32($handle);
        } else {
            fseek($handle, $offset + (ord($object_name[0]) -1)*4);
            $cur = self::bin_fuint32($handle);
            $after = self::bin_fuint32($handle);
        }

        return array($cur, $after);
    }
    /**
     * Tries to find an object in the packs
     * @param  string     $sha1 The object SHA (binary)
     * @return array|null The name of the pack and the byte offset. Null if the object is not found
     */
    protected function findPackedObject($sha1)
    {
        foreach ($this->packs as $index_sha) {
            $index = fopen($this->dir.'/pack/pack-'.$index_sha.'.idx', 'rb');
            flock($index, LOCK_SH);

            // Read the magic number
            $magic_num = fread($index, 4);
            if ($magic_num !== "\xFFt0c") {
                if (($offset = Packfile2::findHashInIndex($index, $sha1)) !== false) {
                    fclose($index);

                    return array($index_sha, $offset);
                }
            } else {
                $version = unpack('Nx', fread($index, 4));
                if ($version['x'] === 2) {
                    if (($offset = Packfile1::findHashInIndex($index, $sha1)) !== false) {
                        fclose($index);

                        return array($index_sha, $offset);
                    }
                } else {
                    throw new \RuntimeException('Packfile version unsupported');
                }
            }
            fclose($index);
        }
    }

    /**
     * Applies a git delta to a base
     * @param  string $delta The delta to apply
     * @param  string $base  The base to apply the delta to
     * @return string The patched data
     */
    public static function applyDelta($delta, $base)
    {
        $pos = 0;
        $base_size = self::git_varint($delta, $pos);
        $result_size = self::git_varint($delta, $pos);

        $result = '';
        $delta_length = strlen($delta);
        while ($pos < $delta_length) {
            $op = ord($delta[$pos++]);
            if ($op & 0x80) {
                $off = 0;
                if ($op & 0x01) $off = ord($delta{$pos++});
                if ($op & 0x02) $off |= ord($delta{$pos++}) << 8;
                if ($op & 0x04) $off |= ord($delta{$pos++}) << 16;
                if ($op & 0x08) $off |= ord($delta{$pos++}) << 24;
                $len = 0;
                if ($op & 0x10) $len = ord($delta{$pos++});
                if ($op & 0x20) $len |= ord($delta{$pos++}) << 8;
                if ($op & 0x40) $len |= ord($delta{$pos++}) << 16;
                if ($len == 0) $len = 0x10000;

                $result.=substr($base, $off, $len);
            } else {
                $result.=substr($delta, $pos, $op);
                $pos += $op;
            }
        }

        return $result;
    }
    /**
     * Unpacks an object from a pack
     * @param  resource $pack       An open pointer to a packfile
     * @param  int      $obj_offset The offset of the object in the pack
     * @return array    An array of the object type and its binary representation
     */
    protected function unpackObject($pack, $obj_offset)
    {
        fseek($pack, $obj_offset);

        // Read object header
        $c = ord(fgetc($pack));
        $type = ($c >> 4) & 0x07;
        $size = $c & 0x0F;
        for ($i = 4; $c&0x80; $i+=7) {
            $c = ord(fgetc($pack));
            $size |= (( $c &0x7F) << $i); // That's what I'm thinking too =|
        }

        if ($type == self::OBJ_COMMIT || $type == self::OBJ_TREE || $type == self::OBJ_BLOB || $type == self::OBJ_TAG) {
            $data = gzuncompress(fread($pack, $size+512), $size);
        } elseif ($type == self::OBJ_OFS_DELTA) {
            $buf = fread($pack, $size+512+20);

            $pos = 0;
            $offset = -1;
            do {
                $offset++;
                $c= ord($buf[$pos++]);
                $offset = ($offset << 7) + ($c & 0x7F);
            } while ($c & 0x80);

            $delta = gzuncompress(substr($buf, $pos), $size);
            unset($buf);

            $base_offset = $obj_offset - $offset;
            assert($base_offset >= 0);
            list($type, $base) = self::unpackObject($pack, $base_offset);

            $data = self::applyDelta($delta, $base);
        } elseif ($type == self::OBJ_REF_DELTA) {
            $base_name = fread($pack, 20);
            list($type, $base) = $this->getRawObject($base_name);

            $delta = gzuncompress(fread($pack, $size+512), $size);

            $data = self::applyDelta($delta, $base);
        } else {
            throw new \UnexpectedValueException('Unknown type of object');
        }

        return array($type, $data);
    }

    /**
     * Gets a raw object by name
     * @param  string $object_name The binary SHA of the object
     * @return array  The object type and the object contents
     */
    protected function getRawObject($object_name)
    {
        if(isset($this->cache[$object_name]))

            return $this->cache[$object_name];
        $sha1 = unpack('H*', $object_name);
        $sha1 = $sha1[1];
        $path = $this->dir.'/'.substr($sha1, 0, 2).'/'.substr($sha1, 2);

        // Still an unpacked object
        if (file_exists($path)) {
            list($header, $data) =  explode("\0", gzuncompress(file_get_contents($path)), 2);
            sscanf($header, "%s %d", $type, $size);
            $obj_type = self::getTypeId($type);
            $result = array($obj_type, $data);
        } elseif ($x = $this->findPackedObject($object_name)) {
            list($pack_name, $obj_offset) = $x;

            $pack = fopen($this->dir.'/pack/pack-'.$pack_name.'.pack', 'rb');
            flock($pack, LOCK_SH);

            // Check pack
            $magic = fread($pack, 4);
            $version = self::bin_fuint32($pack);
            if ($magic != 'PACK' || $version != 2) {
                throw new \RuntimeException('Unsupported or damaged pack');
            }
            $result = $this->unpackObject($pack, $obj_offset);
            fclose($pack);
        } else {
            throw new \RuntimeException('Object not found');
        }
        $this->cache[$object_name] = $result;

        return $result;
    }

    /**
     * Get the data from the object
     * @param  string $sha1 The SHA of the object
     * @return string The data in the object
     */
    public function getObject($sha1)
    {
        $name = pack('H*', $sha1);
        list($type, $data) = $this->getRawObject($name);
        $data_length = strlen($data);
        $data_type = self::getTypeName($type);

        return $data_type.' '.$data_length."\0".$data;
    }

    /**
     * Flushes the caches of the class
     */
    public function clearCache()
    {
        $this->cache = array();
        $this->packs = array();
        $this->__construct($this->dir);
    }

}
