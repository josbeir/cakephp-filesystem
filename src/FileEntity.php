<?php
namespace Josbeir\Filesystem;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Utility\Text;
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
    protected $_allowed = [
        'uuid',
        'path',
        'filename',
        'mime',
        'hash',
        'size',
        'created'
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
        $defaults = array_fill_keys($this->_allowed, null);
        $data = $data + $defaults;

        $diff = array_diff_key($data, $defaults);
        if (!empty($diff)) {
            throw new FileEntityException(sprintf('FileEntity constructor data contains keys that are not allowed (%s)', implode(',', array_keys($diff))));
        }

        if (!$data['uuid']) {
            $data['uuid'] = Text::uuid();
        }

        if (!$data['created']) {
            $data['created'] = Time::now();
        }

        if (is_string($data['created'])) {
            $data['created'] = new Time($data['created']);
        }

        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
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
    public function setPath(string $path) : FileEntityInterface
    {
        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid() : string
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray() : array
    {
        return $this->getArrayCopy();
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
