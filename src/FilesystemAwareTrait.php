<?php
namespace Josbeir\Filesystem;

use Cake\Core\Configure;
use Josbeir\Filesystem\Exception\FilesystemException;
use Josbeir\Filesystem\FilesystemRegistry;

trait FilesystemAwareTrait
{
    /**
     * Return instance of filesystem configuration
     *
     * @param string $name Configuration identifier
     *
     * @return \App\Filesystem\Filesystem
     */
    public function getFilesystem($name = FilesystemRegistry::CONFIG_DEFAULT) : Filesystem
    {
        return FilesystemRegistry::get($name);
    }
}
