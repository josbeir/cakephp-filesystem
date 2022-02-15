<?php
declare(strict_types=1);

namespace Josbeir\Filesystem\Test\TestCase;

use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\FileEntity;
use Josbeir\Filesystem\FileEntityCollection;

class FileEntityCollectionTest extends TestCase
{
    protected $entities = [];

    public function testConstructor()
    {
        $entities = [];
        for ($x = 1; $x <= 20; $x++) {
            $entities[] = new FileEntity($this->_dummyEntityData());
        }

        $collection = new FileEntityCollection($entities);

        $this->assertEquals((int)20, $collection->extract('hash')->count());
    }

    public function testCreateFromArray()
    {
        $entities = [];
        for ($x = 1; $x <= 2; $x++) {
            $entities[] = $this->_dummyEntityData();
        }

        $entities[] = new FileEntity($this->_dummyEntityData());

        $collection = FileEntityCollection::createFromArray($entities);

        $this->assertEquals((int)3, $collection->extract('hash')->count());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $collection->first());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $collection->last());
    }

    public function testToString()
    {
        $entities = [];
        for ($x = 1; $x <= 2; $x++) {
            $entities[] = new FileEntity($this->_dummyEntityData());
        }

        $collection = (string)new FileEntityCollection($entities);

        $this->assertJson($collection);
    }

    public function testInvalidData()
    {
        $this->expectException('\InvalidArgumentException');

        $collection = new FileEntityCollection('invalid string');
    }

    protected function _dummyEntityData()
    {
        return [
            'path' => uniqid() . '.ext',
            'hash' => uniqid(),
            'mime' => 'file/type',
            'size' => 1337,
        ];
    }
}
