<?php
namespace Josbeir\Filesystem\Test\TestCase;

use Cake\Core\Configure;
use Cake\Event\EventList;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\Filesystem;
use Zend\Diactoros\UploadedFile;

class FilesystemTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->testFile = dirname(__DIR__) . '/test_app/dummy.png';
        $this->manager = new Filesystem([
            'adapterArguments' => [ dirname(__DIR__) . '/test_app/assets' ]
        ]);

        $this->manager->getEventManager()->setEventList(new EventList());
    }

    /**
     * Cleanup
     *
     * @return void
     */
    public function tearDown()
    {
        exec('rm -rf ' . dirname(__DIR__) . '/test_app/assets/*');

        unset($this->testFile);
        unset($this->manager);

        parent::tearDown();
    }

    /**
     * Test the default adapter
     *
     * @return void
     */
    public function testDefaultAdapter()
    {
        $manager = new Filesystem;

        $this->assertInstanceOf('League\Flysystem\Filesystem', $manager->getDisk());
        $this->assertInstanceOf('League\Flysystem\Adapter\Local', $manager->getAdapter());
        $this->assertEquals('\Josbeir\Filesystem\Formatter\DefaultFormatter', $manager->getFormatterClass());
    }

    /**
     * Test getting an adapter
     *
     * @return void
     */
    public function testGetAdapter()
    {
        $shortNameAdapter = new Filesystem([
            'adapter' => 'NullAdapter'
        ]);

        $fqcNameAdapter = new Filesystem([
            'adapter' => '\League\Flysystem\Adapter\NullAdapter'
        ]);

        $unexistingAdapterFs = new Filesystem([
            'adapter' => 'UnexistingAdapter'
        ]);

        $this->assertInstanceOf('League\Flysystem\Adapter\NullAdapter', $shortNameAdapter->getAdapter());
        $this->assertInstanceOf('League\Flysystem\Adapter\NullAdapter', $fqcNameAdapter->getAdapter());

        $this->expectException('\InvalidArgumentException');
        $unexistingAdapterFs->getAdapter();
    }

    /**
     * Test setting an adapter
     *
     * @return void
     */
    public function testSetAdapter()
    {
        $adapter = $this->getMockBuilder('\League\Flysystem\Adapter\NullAdapter')->getMock();

        $this->manager->setAdapter($adapter);

        $this->assertInstanceOf('\League\Flysystem\Adapter\NullAdapter', $this->manager->getAdapter());
    }

    /**
     * Test "uploads" using plain paths
     *
     * @return void
     */
    public function testPathUpload()
    {
        $entity = $this->manager->upload($this->testFile);
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertTrue(file_exists($dest));
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);

        $this->assertEventFired('Filesystem.beforeUpload', $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterUpload', 'entity', $entity, $this->manager->getEventManager());
    }

    /**
     * Test invalid upload
     *
     * @return void
     */
    public function testInvalidUpload()
    {
        $this->expectException('\Josbeir\Filesystem\Exception\FilesystemException');

        $entity = $this->manager->upload('invalidfile');
    }

    /**
     * Test upload with entity formatter
     *
     * @return void
     */
    public function testUploadEntityFormatter()
    {
        $entity = $this->manager->upload($this->testFile, [
            'formatter' => 'Entity',
            'data' => new Entity([
                'id' => 'cool-id',
                'name' => 'myimage'
            ], [ 'source' => 'articles' ])
        ]);

        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertSame('articles/dummy.png', $entity->path);
        $this->assertTrue(file_exists($dest));
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);
    }

    /**
     * Test files upload using $_FILES format
     *
     * @return void
     */
    public function testFilesUpload()
    {
        $uploadArray = [
            'name' => 'dummy.png',
            'tmp_name' => $this->testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1337,
            'type' => 'image/png'
        ];

        $file = $this->manager->upload($uploadArray);
        $dest = $this->manager->getAdapter()->getPathPrefix() . $file->path;

        $this->assertFileExists($dest);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $file);
    }

    /**
     * Test uplaodMany using normalied files array
     *
     * @return void
     */
    public function testuploadMany()
    {
        $uploadsArray = [];
        for ($x = 0; $x <= 1; $x++) {
            $uploadArray[] = [
                'name' => 'dummy.png',
                'tmp_name' => $this->testFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1337,
                'type' => 'image/png'
            ];
        }

        $collection = $this->manager->uploadMany($uploadArray);

        $this->assertEquals((int)2, $collection->count());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityCollection', $collection);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $collection->first());
    }

    /**
     * Test uplaodMany using normalied files array
     *
     * @return void
     */
    public function testuploadManyDenormalized()
    {
        $structure = [
            'name' => 'dummy.png',
            'tmp_name' => $this->testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1337,
            'type' => 'image/png'
        ];

        $uploadsArray = [];
        for ($x = 0; $x <= 1; $x++) {
            foreach ($structure as $key => $item) {
                $uploadArray[$key][$x] = $item;
            }
        }

        $collection = $this->manager->uploadMany($uploadArray);

        $this->assertEquals((int)2, $collection->count());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityCollection', $collection);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $collection->first());
    }

    /**
     * Test file uploads using Zend\Diactoros\UploadedFile
     */
    public function testUploadedFileUpload()
    {
        $data = new UploadedFile(tmpfile(), 1337, UPLOAD_ERR_OK, 'dummy_test.png', 'image/png');
        $entity = $this->manager->upload($data);

        $this->assertSame('dummy_test.png', $entity->getPath());
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertFileExists($dest);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);

        $this->assertEventFired('Filesystem.beforeUpload', $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterUpload', 'entity', $entity, $this->manager->getEventManager());
    }

    /**
     * Test entity formatters
     *
     * @return void
     */
    public function testEntityFormatter()
    {
        $entity = new Entity([
            'id' => 'cool-id',
            'name' => 'myimage'
        ], [ 'source' => 'articles' ]);

        $formatter = $this->manager
            ->setFormatter('Entity')
            ->newFormatter('test.png', [ 'data' => $entity ]);

        $this->assertInstanceOf('\Josbeir\Filesystem\Formatter\EntityFormatter', $formatter);
        $this->assertSame('articles/test.png', $formatter->getPath());

        $path = $this->manager
            ->setFormatter('Default')
            ->newFormatter('test.png')
            ->getPath();

        $this->assertSame('test.png', $path);

        // test invalid data passed
        $this->expectException('\InvalidArgumentException');
        $this->manager->setFormatter('Entity')->newFormatter('filename', [ 'data' => 'imnotvalid' ])->getPath();
    }

    /**
     * Test custom formatter patterns
     *
     * @return void
     */
    public function testEntityFormatterCustomPattern()
    {
        $entity = new Entity([
            'id' => 'cool-id',
            'name' => 'hello world this is cool'
        ], [ 'source' => 'articles' ]);

        $path = $this->manager
            ->setFormatter('Entity')
            ->newFormatter('cool-image.png', [
                'data' => $entity,
                'pattern' => '{entity-source}/{id}-{name}.{file-ext}',
            ])
            ->getPath();

        $this->assertSame('articles/cool-id-hello_world_this_is_cool.png', $path);
    }

    /**
     * Test exception when invalid formatter is used
     *
     * @return void
     */
    public function testInvalidFormatterClass()
    {
        $this->expectException('\InvalidArgumentException');
        $this->manager->setFormatter('unknown');
    }

    /**
     * Upload and rename a file
     *
     * @return void
     */
    public function testRename()
    {
        $entity = $this->manager->upload($this->testFile);

        // rename to dummy2
        $this->manager->rename($entity, 'dummy2.png');
        $this->assertEquals('dummy2.png', $entity->path);
        $this->assertEventFiredWith('Filesystem.beforeRename', 'entity', $entity, $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterRename', 'entity', $entity, $this->manager->getEventManager());

        // try again, file should excist and exception should be thrown
        $this->expectException('\League\Flysystem\FileExistsException');
        $this->manager->rename($entity, 'dummy2.png');

        // now rename once again
        $this->manager->rename($entity, 'dummy.png');
    }

    /**
     * Upload a file, copy the file and rename it to the original file
     * Without the force option this operation would throw a FileExistsException
     *
     * @return void
     */
    public function testForcedRename()
    {
        $entity = $this->manager->upload($this->testFile);
        $this->manager->copy($entity->getPath(), 'dummy2.png');

        $copied = $this->manager->newEntity([
            'path' => 'dummy2.png',
            'filename' => 'dummy2.png'
        ]);

        $this->manager->rename($copied, 'dummy.png', true);
        $this->assertFileExists(dirname(__DIR__) . '/test_app/assets/dummy.png');
    }

    /**
     * Test rename a file based on formatter configuration
     *
     * @return void
     */
    public function testRenameWithFormatter()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->assertEquals('dummy.png', $entity->getPath());

        $this->manager->rename($entity, [
            'formatter' => 'Entity',
            'data' => new Entity([
                'id' => 'cool-id',
                'name' => 'myimage'
            ], [ 'source' => 'articles' ])
        ]);

        $this->assertEquals('articles/dummy.png', $entity->getPath());
    }

    /**
     * Test delete a file
     *
     * @return void
     */
    public function testDelete()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->assertFileExists(dirname(__DIR__) . '/test_app/assets/' . $entity->path);
        $this->manager->delete($entity);

        $this->assertFileNotExists(dirname(__DIR__) . '/test_app/assets/' . $entity->path);
        $this->assertEventFiredWith('Filesystem.beforeDelete', 'entity', $entity, $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterDelete', 'entity', $entity, $this->manager->getEventManager());
    }

    /**
     * Test reset class
     *
     * @return void
     */
    public function testReset()
    {
        $this->manager->setFormatter('Entity');
        $this->assertEquals('\Josbeir\Filesystem\Formatter\EntityFormatter', $this->manager->getFormatterClass());
        $this->manager->reset();
        $this->assertEquals('\Josbeir\Filesystem\Formatter\DefaultFormatter', $this->manager->getFormatterClass());
    }
}
