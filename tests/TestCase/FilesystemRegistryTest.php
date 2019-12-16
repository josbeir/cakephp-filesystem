<?php
namespace Josbeir\Filesystem;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\Filesystem;
use Josbeir\Filesystem\FilesystemRegistry;

class FilesystemRegistryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test FilesystemRegistry::get
     *
     * @return void
     */
    public function testGet()
    {
        $defaultFs = FilesystemRegistry::get();
        $this->assertInstanceOf('\Josbeir\Filesystem\Filesystem', $defaultFs);
    }

    /**
     * Test add FS
     *
     * @return void
     */
    public function testAdd()
    {
        $testadapter = new Filesystem([
            'adapter' => 'TestAdapter',
        ]);

        FilesystemRegistry::add('test', $testadapter);

        $this->assertEquals($testadapter, FilesystemRegistry::get('test'));
    }

    public function testReset()
    {
        FilesystemRegistry::get();
        FilesystemRegistry::reset();

        $this->assertFalse(FilesystemRegistry::exists('default'));
    }

    /**
     * Test custom config key
     *
     * @return void
     */
    public function testCustomConfig()
    {
        Configure::write('Filesystem.myfs', [
            'formatter' => 'Entity',
        ]);

        $fs = FilesystemRegistry::get('myfs');

        $this->assertSame('\Josbeir\Filesystem\Formatter\EntityFormatter', $fs->getFormatterClass());
    }

    /**
     * Test exception when undefined config key is used
     *
     * @return void
     */
    public function testUndefinedConfig()
    {
        $this->expectException('\Josbeir\Filesystem\Exception\FilesystemException');
        $undefinedFs = FilesystemRegistry::get('idontexist');
    }
}
