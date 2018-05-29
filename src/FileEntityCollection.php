<?php
namespace Josbeir\Filesystem;

use ArrayIterator;
use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use Cake\Core\InstanceConfigTrait;
use IteratorIterator;
use Josbeir\Filesystem\FileEntityInterface;
use Josbeir\Filesystem\FilesystemRegistry;
use Josbier\Filesystem\Filesystem;
use Traversable;

/**
 * Collection class that holds file entities
 */
class FileEntityCollection extends IteratorIterator implements CollectionInterface
{
    use CollectionTrait;
    use InstanceConfigTrait;

    /**
     * Default configuration
     *
     * Options
     * -------
     * `filesystem` Key of the filesystem to use to initiate entity classes
     *
     * @var array
     */
    protected $_defaultConfig = [
        'filesystem' => FilesystemRegistry::CONFIG_DEFAULT
    ];

    /**
     * Constructor
     *
     * @param array $entities Entities
     * @param array $config Config
     */
    public function __construct(array $entities, array $config = [])
    {
        $this->setConfig($config);

        if (is_array($entities)) {
            $entities = $this->prepareEntities($entities);
            $entities = new ArrayIterator($entities);
        }

        if (!($entities instanceof Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new InvalidArgumentException($msg);
        }

        parent::__construct($entities);
    }

    /**
     * Prepare entity data
     *
     * @param array $entities Array with entities
     * @return void
     */
    public function prepareEntities($entities)
    {
        foreach ($entities as &$entity) {
            if (!$entity instanceof FileEntityInterface) {
                $entities = $this->getFilesystem()->newEntity($entity);
            }
        }

        return $entities;
    }

    /**
     * Return filesystem class
     *
     * @return \Josbier\Filesystem\Filesystem
     */
    public function getFilesystem() : Filesystem
    {
        $filesystem = $this->getConfig('filesystem');

        if ($filesystem instanceof Filesystem) {
            return $this->getConfig('filesystem');
        }

        return FilesystemRegistry::get($filesystem);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'count' => $this->count(),
            'data' => $this->getArrayCopy()
        ];
    }
}
