<?php
declare(strict_types=1);

namespace Josbeir\Filesystem;

use ArrayIterator;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use InvalidArgumentException;
use IteratorIterator;
use Josbeir\Filesystem\FileEntityInterface;
use Josbeir\Filesystem\FilesystemRegistry;
use Traversable;

/**
 * Collection class that holds file entities
 */
final class FileEntityCollection extends IteratorIterator implements CollectionInterface
{
    use CollectionTrait;

    /**
     * Constructor
     *
     * @param array|\Traversable $entities File entities array.
     *
     * @throws \InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct($entities)
    {
        if (is_array($entities)) {
            $entities = new ArrayIterator($entities);
        }

        if (!($entities instanceof Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new InvalidArgumentException($msg);
        }

        parent::__construct($entities);
    }

    /**
     * Create a collection instance from a mixed data array
     * Array can contain Entity array data to the entity itself
     *
     * @param array $entities Array with entity data or entities
     * @param string $filesystem Filesystem name, used to generate the correct entity
     * @return self
     */
    public static function createFromArray(array $entities, string $filesystem = null): self
    {
        foreach ($entities as &$entity) {
            if (!$entity instanceof FileEntityInterface) {
                $entity = FilesystemRegistry::get($filesystem)->newEntity($entity);
            }
        }

        return new static($entities);
    }

    /**
     * Return a string representation of the collection
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'count' => $this->count(),
        ];
    }
}
