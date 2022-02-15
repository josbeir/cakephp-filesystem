<?php
declare(strict_types=1);

namespace Josbeir\Filesystem\Test\TestCase;

use Cake\TestSuite\TestCase;
use Josbeir\Filesystem\FilesystemAwareTrait;

class FilesystemAwareTraitTest extends TestCase
{
    use FilesystemAwareTrait;

    public function testGetFilesystem()
    {
        $this->assertInstanceOf('\Josbeir\Filesystem\Filesystem', $this->getFilesystem());

        $this->expectException('\Josbeir\Filesystem\Exception\FilesystemException');
        $this->assertInstanceOf('\Josbeir\Filesystem\Filesystem', $this->getFilesystem('nonexistant'));
    }
}
