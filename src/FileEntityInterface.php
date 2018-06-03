<?php
namespace Josbeir\Filesystem;

use JsonSerializable;

interface FileEntityInterface extends JsonSerializable
{
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
