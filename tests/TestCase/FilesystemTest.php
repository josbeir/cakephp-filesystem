<?php
namespace Josbeir\Filesystem\Test\TestCase;

use Cake\Core\Configure;
use Cake\Event\EventList;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\FileSourceNormalizer;
use Josbeir\Filesystem\Filesystem;
use Zend\Diactoros\UploadedFile;

class FilesystemTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->testFile = dirname(__DIR__) . '/test_app/dummy.png';
        $this->manager = new Filesystem([
            'adapterArguments' => [ dirname(__DIR__) . '/test_app/assets' ]
        ]);

        $this->manager->getEventManager()->setEventList(new EventList());
    }

    public function tearDown()
    {
        exec('rm -rf ' . dirname(__DIR__) . '/test_app/assets/*');

        unset($this->testFile);
        unset($this->manager);

        parent::tearDown();
    }

    public function testDefaultAdapter()
    {
        $manager = new Filesystem;

        $this->assertInstanceOf('League\Flysystem\Filesystem', $manager->getDisk());
        $this->assertInstanceOf('League\Flysystem\Adapter\Local', $manager->getAdapter());
        $this->assertInstanceOf('\Josbeir\Filesystem\Formatter\DefaultFormatter', $manager->getFormatter());
    }

    public function testGetAdapter()
    {
        $shortNameAdapter = new Filesystem([
            'adapter' => 'Local'
        ]);

        $fqcNameAdapter = new Filesystem([
            'adapter' => 'League\Flysystem\Adapter\Local'
        ]);

        $unexistingAdapterFs = new Filesystem([
            'adapter' => 'UnexistingAdapter'
        ]);

        $this->assertInstanceOf('League\Flysystem\Adapter\Local', $shortNameAdapter->getAdapter());
        $this->assertInstanceOf('League\Flysystem\Adapter\Local', $fqcNameAdapter->getAdapter());

        $this->expectException('\InvalidArgumentException');
        $unexistingAdapterFs->getAdapter();
    }

    public function testPathUpload()
    {
        $entity = $this->manager->upload($this->testFile);
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertTrue(file_exists($dest));
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);

        $this->assertEventFired('Filesystem.beforeUpload', $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterUpload', 'entity', $entity, $this->manager->getEventManager());
    }

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

    public function testUploadedFileUpload()
    {
        $data = new UploadedFile(tmpfile(), 1337, UPLOAD_ERR_OK, 'dummy_test.png', 'image/png');
        $entity = $this->manager->upload($data);

        $this->assertSame('dummy_test.png', $this->manager->getFormatter()->getPath());
        $dest = $this->manager->getAdapter()->getPathPrefix() . $entity->path;

        $this->assertFileExists($dest);
        $this->assertInstanceOf('\Josbeir\Filesystem\FileEntity', $entity);

        $this->assertEventFired('Filesystem.beforeUpload', $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterUpload', 'entity', $entity, $this->manager->getEventManager());
    }

    public function testEntityFormatter()
    {
        $entity = new Entity([
            'id' => 'cool-id',
            'name' => 'myimage'
        ], [ 'source' => 'articles' ]);

        $path = $this->manager
            ->setFormatter('Entity')
            ->getFormatter()
            ->setInfo('test.png', $entity)
            ->getPath();

        $this->assertInstanceOf('\Josbeir\Filesystem\Formatter\EntityFormatter', $this->manager->getFormatter());
        $this->assertSame('articles/test.png', $path);

        $path = $this->manager
            ->setFormatter('Default')
            ->getFormatter()
            ->setInfo('test.png')
            ->getPath();

        $this->assertSame('test.png', $path);
    }

    public function testEntityFormatterCustomPattern()
    {
        $entity = new Entity([
            'id' => 'cool-id',
            'name' => 'hello world this is cool'
        ], [ 'source' => 'articles' ]);

        $path = $this->manager
            ->setFormatter('Entity', [
                'pattern' => '{entity-source}/{id}-{name}.{file-ext}',
            ])
            ->getFormatter()
            ->setInfo('cool-image.png', $entity)
            ->getPath();

        $this->assertSame('articles/cool-id-hello_world_this_is_cool.png', $path);
    }

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

    public function testDelete()
    {
        $entity = $this->manager->upload($this->testFile);

        $this->assertFileExists(dirname(__DIR__) . '/test_app/assets/' . $entity->path);
        $this->manager->delete($entity);
        $this->assertFileNotExists(dirname(__DIR__) . '/test_app/assets/' . $entity->path);

        $this->assertEventFiredWith('Filesystem.beforeDelete', 'entity', $entity, $this->manager->getEventManager());
        $this->assertEventFiredWith('Filesystem.afterDelete', 'entity', $entity, $this->manager->getEventManager());
    }
}
