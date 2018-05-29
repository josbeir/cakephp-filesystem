<?php
namespace Josbeir\Filesystem;

use ArrayObject;
use Cake\I18n\Time;
use Josbeir\Filesystem\Exception\FileEntityException;
use Josbeir\Filesystem\FileEntityInterface;

/**
 * Representation of a file entity
 */
class FileEntity extends ArrayObject implements FileEntityInterface
{
    /**
     * File data array
     *
     * @var array
     */
    protected $_data = [
        'path' => null,
        'originalFilename' => null,
        'mime' => null,
        'hash' => null,
        'filesize' => 0,
        'created' => null
    ];

    /**
     * Setup the file entity
     *
     * @param array $data file data
     *
     * @throws Josbeir\Filesystem\Exception\FileEntityException When constructor data doesnt match expected data in $_data
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->setData($data);

        parent::__construct($this->_data);
    }

    /**
     * Populate the entity

     * @param array $data file data
     *
     * @throws \Josbeir\Filesystem\Exception\FileEntityException When data contains keys that are not allowed

     * @return self
     */
    public function setData($data)
    {
        $diff = array_diff_key($data, $this->_data);

        if (!empty($diff)) {
            throw new FileEntityException(sprintf('FileEntity constructor data contains keys that are not allowed (%s)', implode(',', array_keys($diff))));
        }

        $this->_data = $data + $this->_data;

        if (is_string($this->created)) {
            $this->created = new Time($this->created);
        }

        if (!$this->created) {
            $this->created = Time::now();
        }

        return $this;
    }

    /**
     * Magic getter, return field from data
     *
     * @param string $field Fieldname to return
     *
     * @throws Josbeir\Filesystem\Exception\FileEntityException When passed field is not defined in $_data
     *
     * @return mixed
     */
    public function __get($field)
    {
        if (array_key_exists($field, $this->_data)) {
            return $this->_data[$field];
        }

        throw new FileEntityException(sprintf('Field %s not available', $field));
    }

    /**
     * Magic setter, set data
     *
     * @param string $field name of the field to set
     * @param string $value value to set
     *
     * @return void
     */
    public function __set($field, $value)
    {
        if (array_key_exists($field, $this->_data)) {
            $this->_data[$field] = $value;
        }
    }

    /**
     * Return a human readable filesize
     *
     * @param int $precision Precision to return
     *
     * @deprecated Dont use, this is going to be removed soon!
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function humanFilesize($precision = 2)
    {
        $base = log($this->filesize, 1024);
        $suffixes = [ '', 'KB', 'MB', 'GB', 'TB' ];

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    /**
     * {@inheritDoc}
     */
    public function hasHash(string $hash) : bool
    {
        return $this->hash == $hash;
    }

    /**
     * {@inheritDoc}
     */
    public function getHash() : string
    {
        return $this->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray() : array
    {
        return $this->_data;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() : string
    {
        return $this->getPath();
    }

    /**
     * Return properties for debugging.
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function __debugInfo() : array
    {
         return $this->toArray();
    }
}
