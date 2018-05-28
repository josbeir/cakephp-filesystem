<?php
namespace Josbeir\Filesystem\Exception;

class FilesystemException extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message Exception message
     * @param int $code Status code, defaults to 500
     *
     * @param \Exception|null $previous The previous exception.
     */
    public function __construct($message, $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
