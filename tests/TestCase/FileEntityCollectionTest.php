<?php
namespace Josbeir\Filesystem\Test\TestCase;

use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\FileEntity;
use Josbeir\Filesystem\FileEntityCollection;

class FileEntityCollectionTest extends TestCase
{
    public function setUp()
    {
        $this->entities = [];

        for($x = 1; $x <= 20; $x++) {
            $this->entities[] = new FileEntity([
                'path' => uniqid() .'.ext',
                'hash' => uniqid(),
                'mime' => 'file/type',
                'filesize' => 1337
            ]);
        }
    }

    // public function testConstructor()
    // {
    //     $collection = new FileEntityCollection($this->entities);

    //     $this->markTestSkipped();
    // }
}
