<?php
namespace Josbeir\Filesystem\Formatter;

use Cake\Filesystem\File;
use Josbeir\Filesystem\FormatterInterface;
use SplFileInfo;

/**
 * Simple formatter, just returns the filename
 */
class DefaultFormatter implements FormatterInterface
{
    /**
     * Data to be used for formatting
     *
     * @var mixed
     */
    protected $_data;

    /**
     * Hold pathinfo about the filename
     *
     * @var array
     */
    protected $_info;

    /**
     * {@inheritDoc}
     */
    public function setInfo(string $filename, $data = null) : FormatterInterface
    {
        $this->_data = $data;
        $this->_info = pathinfo($filename);

        return $this;
    }

    /**
     * Return the basename of current file
     *
     * @return string
     */
    public function getBaseName() : string
    {
        return $this->safe($this->_info['basename']);
    }

    /**
     * Return the basename of current file
     *
     * @return string
     */
    public function getExtension() : string
    {
        return $this->_info['extension'];
    }

    /**
     * Return the filename
     *
     * @return string
     */
    public function getFileName() : string
    {
        return $this->safe($this->_info['filename']);
    }

    /**
     * {@inheritDoc}
     */
    public function getPath() : string
    {
        return $this->getBaseName();
    }

    /**
     * Return filesystem safe string
     *
     * @param string $value String to make safe
     *
     * @return string
     */
    public function safe(string $value) : string
    {
        return preg_replace("/(?:[^\w\.-]+)/", '_', $value);
    }
}
