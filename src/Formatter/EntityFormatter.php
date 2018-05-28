<?php
namespace Josbeir\Filesystem\Formatter;

use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Josbeir\Filesystem\Formatter\DefaultFormatter;

/**
 * Entity formatter, returns a path based on a given pattern
 * Data should be of the EntityInterface kind
 */
class EntityFormatter extends DefaultFormatter
{
    use InstanceConfigTrait;

    /**
     * Configuration options
     *
     * @var array
     */
    protected $_defaultConfig = [
        'pattern' => '{entity-source}/{file-name}.{file-ext}',
        'replacements' => []
    ];

    /**
     * Constructor
     *
     * @param array $options configuration options
     */
    public function __construct($options)
    {
        $this->setConfig($options);
    }

    /**
     * (@inheritDoc)
     *
     * @return string
     */
    public function getPath() : string
    {
        if (!$this->_data instanceof EntityInterface) {
            throw new \Exception(
                sprintf('Passed formatter data is not EntityInterface compatible (%s given)', gettype($this->_data))
            );
        }

        $data = array_map(function ($item) {
            return is_string($item) ? $this->safe($item) : $item;
        }, $this->_data->toArray());

        $patterns = $data + $this->getConfig('replacements') + [
            'entity-source' => $this->safe(strtolower($this->_data->getSource())),
            'file-name' => $this->getFileName(),
            'file-basename' => $this->getBaseName(),
            'file-ext' => $this->getExtension(),
            'date-y' => date('Y'),
            'date-m' => date('m'),
            'date-d' => date('d')
        ];

        $path = Text::insert($this->getConfig('pattern'), $patterns, [
            'before' => '{',
            'after' => '}'
        ]);

        return $path;
    }
}
