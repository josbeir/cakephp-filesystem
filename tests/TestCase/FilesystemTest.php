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
     * @var \Josbeir\Filesystem\Filesystem FileSystem Manager
     */
    private $manager;

    /**
     * @var string Path Variable
     */
    private $testFile;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->testFile = dirname(__DIR__) . '/test_app/dummy.png';
        $this->manager = new Filesystem([
            'adapterArguments' => [ dirname(__DIR__) . '/test_app/assets' ],
        ]);

        $this->manager->getEventManager()->setEventList(new EventList());
    }

    /**
     * Cleanup
     *
     * @return void
     */
    public function tearDown(): void
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
        $manager = new Filesystem();

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
            'adapter' => 'NullAdapter',
        ]);

        $fqcNameAdapter = new Filesystem([
            'adapter' => '\League\Flysystem\Adapter\NullAdapter',
        ]);

        $unexistingAdapterFs = new Filesystem([
            'adapter' => 'UnexistingAdapter',
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
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testPathUpload()
    {
        $entity = $this->manager->upload($this->testFile);
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->getPath();

        $this->assertTrue(file_exists($dest));
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);

        $this->assertEventFired('Filesystem.beforeUpload', $this->manager->getEventManager());
        $manager = $this->manager->getEventManager();
        $this->assertEventFiredWith('Filesystem.afterUpload', 'entity', $entity, $this->manager->getEventManager());
    }

    /**
     * Test upload events
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testUploadEvents()
    {
        $called = 0;
        $this->manager->getEventManager()->on('Filesystem.beforeUpload', function ($event, $filedata, $formatter) use (&$called) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileSourceNormalizer', $filedata);
            $this->assertInstanceOf('\Josbeir\Filesystem\FormatterInterface', $formatter);
        });

        $this->manager->getEventManager()->on('Filesystem.afterUpoad', function ($event, $file) use (&$called) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
        });

        $entity = $this->manager->upload($this->testFile);

        $this->assertEquals(1, $called);
    }

    /**
     * Test invalid upload
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
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
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testUploadEntityFormatter()
    {
        $entity = $this->manager->upload($this->testFile, [
            'formatter' => 'Entity',
            'data' => new Entity([
                'id' => 'cool-id',
                'name' => 'myimage',
            ], [ 'source' => 'articles' ]),
        ]);

        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->getPath();

        $this->assertSame('articles/dummy.png', $entity->getPath());
        $this->assertSame('image/png', $entity->getMime());
        $this->assertTrue(file_exists($dest));
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);
    }

    /**
     * Test files upload using $_FILES format
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testFilesUpload()
    {
        $uploadArray = [
            'name' => 'dummy.png',
            'tmp_name' => $this->testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1337,
            'type' => 'image/png',
        ];

        $entity = $this->manager->upload($uploadArray);
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertFileExists($dest);
        $this->assertSame('image/png', $entity->getMime());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);
    }

    /**
     * Test uplaodMany using normalied files array
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
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
                'type' => 'image/png',
            ];
        }

        $collection = $this->manager->uploadMany($uploadArray);

        $this->assertEquals((int)2, $collection->count());
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityCollection', $collection);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $collection->first());
    }

    /**
     * Test uplaodMany using denormalized php's $_FILES array
     *
     * @link http://php.net/manual/en/features.file-upload.multiple.php
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testuploadManyDenormalized()
    {
        $structure = [
            'name' => 'dummy.png',
            'tmp_name' => $this->testFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1337,
            'type' => 'image/png',
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
        $this->assertSame('image/png', $entity->getMime());

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
            'name' => 'myimage',
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
            'name' => 'hello world this is cool',
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
     * Test custom formatter patterns with integer
     *
     * @return void
     */
    public function testEntityFormatterCustomPatternWithInt()
    {
        $entity = new Entity([
            'id' => 1,
            'name' => 'hello world this is cool',
        ], [ 'source' => 'articles' ]);

        $path = $this->manager
            ->setFormatter('Entity')
            ->newFormatter('cool-image.png', [
                'data' => $entity,
                'pattern' => '{entity-source}/{id}/{name}.{file-ext}',
            ])
            ->getPath();

        $this->assertSame('articles/1/hello_world_this_is_cool.png', $path);
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
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
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
     * Test rename events
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testRenameEvents()
    {
        $called = 0;
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeRename', function ($event, $file, $newPath) use (&$called, $entity) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
            $this->assertEquals('dummy2.png', $newPath);
            $this->assertSame($entity, $file);
        });

        $this->manager->getEventManager()->on('Filesystem.afterRename', function ($event, $file) use (&$called) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
            $this->assertEquals('dummy2.png', $file->getPath());
        });

        $this->manager->rename($entity, 'dummy2.png');

        $this->assertEquals(2, $called);
    }

    /**
     * Test rename abort event
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testRenameEventAbort()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeRename', function ($event, $file, $newPath) {
            $event->stopPropagation();

            return 'hello!';
        });

        $this->manager->getEventManager()->on('Filesystem.afterRename', function ($event) {
            $this->fail('Should not be fired');
        });

        $this->assertSame('hello!', $this->manager->rename($entity, 'dummy2.png'));
    }

    /**
     * Upload a file, copy the file and rename it to the original file
     * Without the force option this operation would throw a FileExistsException
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileExistsException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testForcedRename()
    {
        $entity = $this->manager->upload($this->testFile);
        $this->manager->getDisk()->copy($entity->getPath(), 'dummy2.png');

        $copied = $this->manager->newEntity([
            'path' => 'dummy2.png',
            'filename' => 'dummy2.png',
        ]);

        $this->manager->rename($copied, 'dummy.png', true);
        $this->assertFileExists(dirname(__DIR__) . '/test_app/assets/dummy.png');
    }

    /**
     * Test rename a file based on formatter configuration
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testRenameWithFormatter()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->assertEquals('dummy.png', $entity->getPath());

        $this->manager->rename($entity, [
            'formatter' => 'Entity',
            'data' => new Entity([
                'id' => 'cool-id',
                'name' => 'myimage',
            ], [ 'source' => 'articles' ]),
        ]);

        $this->assertEquals('articles/dummy.png', $entity->getPath());
    }

    /**
     * Upload and rename a file
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testCopy()
    {
        $entity = $this->manager->upload($this->testFile);
        $copy = $this->manager->copy($entity, 'dummy2.png');

        $this->assertEquals('dummy.png', $entity->path);
        $this->assertEquals('dummy2.png', $copy->path);
        $this->assertNotEquals($entity, $copy);
        $this->assertEventFiredWith('Filesystem.beforeCopy', 'entity', $entity, $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterCopy', 'entity', $entity, $this->manager->getEventManager());
    }

    /**
     * Upload and rename a file
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testCopyWithFormatter()
    {
        $entity = $this->manager->upload($this->testFile);
        $copy = $this->manager->copy($entity, [
            'formatter' => 'Entity',
            'data' => new Entity([
                'id' => 'cool-id',
                'name' => 'myimage',
            ], [ 'source' => 'articles' ]),
        ]);

        $this->assertEquals('articles/dummy.png', $copy->path);
        $this->assertNotEquals($entity, $copy);
    }

    /**
     * Upload a file, copy the file and rename it to the original file
     * Without the force option this operation would throw a FileExistsException
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testForcedCopy()
    {
        $entity = $this->manager->upload($this->testFile);
        $this->manager->copy($entity, 'dummy2.png');
        $this->manager->copy($entity, 'dummy2.png', true);
        $this->expectException('League\Flysystem\FileExistsException');
        $this->manager->copy($entity, 'dummy2.png');
    }

    /**
     * Test rename abort event
     *
     * @return void
     */
    public function testCopyEventAbort()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeCopy', function ($event, $file, $newPath) {
            $event->stopPropagation();

            return 'hello!';
        });

        $this->manager->getEventManager()->on('Filesystem.afterCopy', function ($event) {
            $this->fail('Should not be fired');
        });

        $this->assertSame('hello!', $this->manager->copy($entity, 'dummy2.png'));
    }

    /**
     * Test copy events
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testCopyEvents()
    {
        $called = 0;
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeCopy', function ($event, $file, $destination) use (&$called, $entity) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
            $this->assertEquals('dummy2.png', $destination);
            $this->assertSame($entity, $file);
        });

        $this->manager->getEventManager()->on('Filesystem.afterCopy', function ($event, $copied, $file) use (&$called) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $copied);
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
            $this->assertNotEquals($copied, $file);
            $this->assertEquals('dummy2.png', $copied->getPath());
        });

        $this->manager->copy($entity, 'dummy2.png');

        $this->assertEquals(2, $called);
    }

    /**
     * Test delete a file
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
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
     * Test deletion of unexisting file
     *
     * @return void
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testDeleteUnexisting()
    {
        $file = $this->manager->newEntity([
            'path' => 'idontexist.gif',
        ]);

        $this->assertFalse($this->manager->delete($file));
    }

    /**
     * Test delete events
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testDeleteEvents()
    {
        $called = 0;
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeDelete', function ($event, $file) use (&$called, $entity) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
            $this->assertSame($entity, $file);
        });

        $this->manager->getEventManager()->on('Filesystem.afterDelete', function ($event, $file) use (&$called) {
            $called++;
            $this->assertInstanceOf('\Josbeir\Filesystem\FileEntityInterface', $file);
        });

        $this->manager->delete($entity);
        $this->assertEquals(2, $called);
    }

    /**
     * Test delete abort event
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testDeleteEventsAbort()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->manager->getEventManager()->on('Filesystem.beforeDelete', function ($event, $file) {
            $event->stopPropagation();

            return 'hello!';
        });

        $this->manager->getEventManager()->on('Filesystem.afterRename', function ($event, $file) {
            $this->fail('Should not be fired');
        });

        $this->assertSame('hello!', $this->manager->delete($entity));
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

    /**
     * Test __call
     *
     * @return void
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testCallProxy()
    {
        $entity = $this->manager->upload($this->testFile);
        $result = $this->manager->listContents();

        $this->assertSame('dummy.png', $result[0]['path']);
    }
}
