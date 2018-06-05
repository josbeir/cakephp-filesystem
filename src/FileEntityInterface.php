<?php
namespace Josbeir\Filesystem;

use JsonSerializable;

interface FileEntityInterface extends JsonSerializable
{
    /**
     * Return the file entity path
     *
     * @return string
     */
    public function getPath() : string;

    /**
     * Return the file entity path
     *
     * @param string $path Entity path
     *
     * @return self
     */
    public function setPath(string $path) : self;

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
