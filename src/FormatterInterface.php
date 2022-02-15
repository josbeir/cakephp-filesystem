<?php
declare(strict_types=1);

namespace Josbeir\Filesystem;

interface FormatterInterface
{
    /**
     * Formatter constructor
     *
     * @param string $filename Original filename
     * @param mixed $data Data passed
     * @param array $config Configuration options
     * @return void
     */
    public function __construct(string $filename, $data = null, array $config = []);

    /**
     * Return the formatted path
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Return the basename of current file
     *
     * @return string
     */
    public function getBaseName(): string;
}
