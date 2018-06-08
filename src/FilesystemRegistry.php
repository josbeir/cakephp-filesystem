<?php
declare(strict_types = 1);

namespace Josbeir\Filesystem;

use Cake\Core\Configure;
use Josbeir\Filesystem\Exception\FilesystemException;
use Josbeir\Filesystem\Filesystem;

/**
 * Static registry to hold FS instances
 */
class FilesystemRegistry
{
    /**
     * Configuration prefix
     *
     * @var string
     */
    const CONFIG_PREFIX = 'Filesystem.';

    /**
     * Configuration key for default config
     *
     * @var string
     */
    const CONFIG_DEFAULT = 'default';

    /**
     * Hold filesystem instances
     *
     * @var Filesystem[]
     */
    protected static $_filesystems = [];

    /**
     * Get a configured filesystem
     *
     * @param string $name Configuration key identifier
     *
     * @throws App\Filesystem\Exception\FilesystemException When configuration is not defined
     *
     * @return Filesystem
     */
    public static function get(string $name = null) : Filesystem
    {
        $name = $name ?: self::CONFIG_DEFAULT;

        if (!self::exists($name)) {
            $config = Configure::read(static::CONFIG_PREFIX . $name);
            if (!$config) {
                throw new FilesystemException(sprintf('No "%s" filesystem configuration found.', $name));
            }

            static::$_filesystems[$name] = new Filesystem($config);
        }

        return static::$_filesystems[$name];
    }

    /**
     * Add a filesystem to the list of instances
     *
     * @param string $name Configuration key identifier
     * @param Filesystem $filesystem Filesytem instance
     *
     * @return void
     */
    public static function add(string $name, Filesystem $filesystem) : void
    {
        static::$_filesystems[$name] = $filesystem;
    }

    /**
     * Method to check if Filesystem is defined
     *
     * @param string $name Configuration key identifier
     * @return bool
     */
    public static function exists(string $name) : bool
    {
        return isset(static::$_filesystems[$name]);
    }

    /**
     * Reset Filesystem instances
     *
     * @return void
     */
    public static function reset() : void
    {
        static::$_filesystems = [];
    }
}
