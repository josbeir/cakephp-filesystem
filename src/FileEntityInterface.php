<?php
namespace Josbeir\Filesystem;

use JsonSerializable;

interface FileEntityInterface extends JsonSerializable
{
    /**
     * Return the file path
     *
     * @return string
     */
    public function getPath() : string;

    /**
     * Return the files unique uuid
     *
     * @return string
     */
    public function getUuid() : string;

    /**
     * Set the entity path
     *
     * @param string $path Entity path
     * @return self
     */
    public function setPath(string $path): self;

    /**
     * Compare a hash
     *
     * @param string $hash Hash to compare with

     * @return bool
     */
    public function hasHash(string $hash) : bool;

    /**
     * Return the current file hash

     * @return string
     */
    public function getHash() : string;

    /**
     * Get file data as array
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Should return path if the object is cast as string
     *
     * @return string
     */
    public function __toString() : string;
}
