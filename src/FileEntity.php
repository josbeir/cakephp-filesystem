<?php
namespace Josbeir\Filesystem;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Utility\Text;
use Josbeir\Filesystem\Exception\FileEntityException;
use Josbeir\Filesystem\FileEntityInterface;
use \InvalidArgumentException;

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
        'created',
        'meta'
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
     * Magic method for set,get,has methods
     * Allowed fields can be called and manipulated
     *
     * @param string $field Field name
     * @param array $arguments Arguments
     *
     * @return mixed|bool|self
     */
    public function __call($field, $arguments)
    {
        $prefix = substr($field, 0, 3);
        if (in_array($prefix, [ 'get', 'set', 'has' ])) {
            $field = lcfirst(substr($field, 3));
        }

        if (in_array($field, $this->_allowed)) {
            /** @return mixed */
            if ($prefix == 'get') {
                return $this->get($field);
            }

            /** @return self */
            if ($prefix == 'set') {
                return $this->set($field, $arguments[0]);
            }

            /** @return bool */
            if ($prefix == 'has') {
                return $this->has($field, $arguments[0]);
            }
        }

        throw new InvalidArgumentException(sprintf('Field %s could not be found', $field));
    }

    /**
     * Get an internal value
     *
     * @param string $field Field name
     *
     * @return mixed
     */
    public function get($field)
    {
        return $this->{$field};
    }

    /**
     * Set an internal value
     *
     * @param string $field Field name
     * @param mixed $value Value to set
     *
     * @return self
     */
    public function set($field, $value) : self
    {
        $this->{$field} = $value;

        return $this;
    }

    /**
     * Compare a value with the internal value
     *
     * @param string $field Internal field name
     * @param mixed $value Value to compare with
     *
     * @return bool
     */
    public function has($field, $value) : bool
    {
        return $this->{$field} == $value;
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
        return $this->get('path');
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
