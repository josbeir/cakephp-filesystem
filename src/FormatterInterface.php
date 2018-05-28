<?php
namespace Josbeir\Filesystem;

interface FormatterInterface
{
    /**
     * Set data to be used in the formatter
     *
     * @param string $filename Filename used during upload
     * @param mixed $data Data to be used in the class
     * @return $this
     */
    public function setInfo(string $filename, $data = null) : self;

    /**
     * Return the formatted path
     *
     * @return string
     */
    public function getPath() : string;
}
