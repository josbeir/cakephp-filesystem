[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://travis-ci.org/josbeir/cakephp-filesystem.svg?branch=master)](https://travis-ci.org/josbeir/cakephp-filesystem)
[![codecov](https://codecov.io/gh/josbeir/cakephp-filesystem/branch/master/graph/badge.svg)](https://codecov.io/gh/josbeir/cakephp-filesystem)
[![Latest Stable Version](https://poser.pugx.org/josbeir/cakephp-filesystem/v/stable)](https://packagist.org/packages/josbeir/cakephp-filesystem)
[![Total Downloads](https://poser.pugx.org/josbeir/cakephp-filesystem/downloads)](https://packagist.org/packages/josbeir/cakephp-filesystem)

# Filesystem plugin for CakePHP

CakePHP filesystem plugin using [Flysystem](http://flysystem.thephpleague.com/docs/) as it's backend.

## Why use it

- Easy access to Flysystem filesystems in your application
- Upload normalization, accepts $_FILES, Zend\Diactoros\UploadedFile or just a path on the local FS
- Files are represented by customisable and json serialisable entities, Multiple files are returned in a custom [Collection](https://book.cakephp.org/3.0/en/core-libraries/collections.html) instance.
- A trait is available, use it everywhere in your app
- Customizable path/filename formatting during upload, custom formatters are possible, ships with a Default and EntityFormatter.

## Requirements

* CakePHP 3.6+
* PHP 7.1

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require josbeir/cakephp-filesystem
```

## Configuration

A filesystem configuration array should be available in your Configure instance. You can create a config/filesystems.php file with following content
Make sure to load the file in your bootstrap.php using ```Configure::load('filesystems', 'default');```.

The configuration options defined for each 'filestem' are passed directly to the Filesystem.php class. A `default` configuration must be set when using FilesystemAwareTrait / FilesystemRegistry classes

```php
<?php
return [
    'Filesystem' => [
        'default' => [
            'adapter' => 'Local', // default
            'adapterArguments' => [ WWW_ROOT . 'files' ]
        ],
        'other' => [
            'adapter' => 'Local',
            'adapterArguments' => [ WWW_ROOT . 'cache' ],
            'entityClass' => '\My\Cool\EntityClass',
            'formatter' => '\My\Cool\Formatter'
        ]
    ]
];
```

## Simple upload example

Filesystem instances can be accessed from everywhere where you either use the **FilesystemAwareTrait** and calling ```MyClass::getFilesystem($configKey)``` or the ```FilesystemRegistry::get()```

In this example we are using a fictive 'myfs' filesystem definition, if you leave that empty the default FS will be used when calling ``getFilesystem()``.

Upload data submitted in POST:
```php
 [
    'tmp_name' => '/tmp/blabla',
    'filename' => 'lame filename.png',
    'error' => 0,
    'size' => 1337,
    'type' => 'image/png'
]
```

Example controller:
```php
<?php
namespace App\Controller;

use Josbeir\Filesystem\FilesystemAwareTrait;

class MyController extends AppController {

    use FilesystemAwareTrait;

    public function upload()
    {
        $fileEntity = $this->getFilesystem('myfs')->upload($this->request->getData('upload'));

        debug($fileEntity);
    }
}
```

### Result

The result from the above example will output a file entity class

```php
object(Josbeir\Filesystem\FileEntity) {

    'uuid' => 'a105663a-f1a5-40ab-8716-fac211fb01fd',
    'path' => 'articles/now_im_called_bar.png',
    'filename' => 'lame filename.png',
    'filesize' => (int) 28277,
    'mime' => 'image/png',
    'hash' => '6b16dafccd78955892d3eae973b49c6c',
    'meta' => null,
    'created' => object(Cake\I18n\Time) {

        'time' => '2018-05-27T15:31:54+00:00',
        'timezone' => '+00:00',
        'fixedNowTime' => false

    }

}
```


## Entity properties

A JsonSerializable FileEntity ArrayObject is returned when the file was successfully uploaded.
Properties can be accessed, checked and manipulated using get** and set** and has**

```php
$entity->hasUuid('a105663a-f1a5-40ab-8716-fac211fb01fd');
$entity->getUuid() // a105663a-f1a5-40ab-8716-fac211fb01fd
$entity->setUuid('a105663a-f1a5-40ab-8716-fac211fb01fd');
...
...
```

Calling json_encode on the entity

```json
// json_encode($entitiy);
{
    "uuid": "3ae258dd-ab1d-425c-b3b0-450f0c702d64",
    "path": "dummy.png",
    "filename": "dummy.png",
    "size": 59992,
    "mime": "image\/png",
    "hash": "3ba92ed92481b4fc68842a2b3dcee525",
    "created": "2018-06-03T09:27:41+00:00",
    "meta": null
}
```

## Recreating entities

If you for instance saved a file entity somwhere as a json object you could recreate the entity using `Filemanager::newEntity`

```php
$entity = $this->getFilesystem()->newEntity([
    'uuid' => 'a105663a-f1a5-40ab-8716-fac211fb01fd',
    'path' => 'articles/now_im_called_bar.png',
    'filename' => 'lame filename.png',
    'filesize' => 28277,
    'mime' => 'image/png',
    'hash' => '6b16dafccd78955892d3eae973b49c6c',
    'created' => '2018-05-27T15:31:54+00:00',
    "meta": [
        "extra" => "stuf"
    ]
]);
```

Recreating a [Collection](https://book.cakephp.org/3.0/en/core-libraries/collections.html) of entities.

```php
$entities = FileEntityCollection::createFromArray($entities [, string $filesystem]);
```

## Using your own entities

Creating your own entities is possible by implementing the FileEntityInterface class and setting the entity class FQCN in your configuration's ``entityClass`` key.

### Example on using Cake ORM entities instead of the built entity class

If you want to store your entities in the ORM you can easily swap the entity class with an ORM one. The only requirement is that the entity implements the ``FileEntityInterface`` class.

```php
return [
    'Filesystem' => [
        'default' => [
            'entityClass' => 'App\Model\Entity\MyFileEntity'
        ]
]
```

Then make sure your ORM entity implements the FileEntityInterface and its required method 'getPath':

```php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Josbeir\Filesystem\FileEntityInterface;

class MyFileEntity extends Entity implements FileEntityInterface
{
    public function getPath() : string
    {
        return $this->path;
    }

    public function setPath(string $path) : FileEntityInterface
    {
        $this->set('path', $path);

        return $this;
    }
}
```

Now when uploading and using files you can work with ORM entities.

## Formatters

During upload a formatter is used to construct a path and filename. For instance, if you use the EntityFormatter you can use variables available in an entity to build the filename.

```php
$entity = $this->Posts->get(1);

$fileEntity = $this->getFilesystem()->upload(TMP . 'myfile.png', [
    'formatter' => 'Entity', // formatter to use
    'data' => $entity // data to pass to the formatter
]);
```
The default EntityFormatter pattern is ``{entity-source}/{file-name}.{file-ext}`` which results in ``posts/myfile.png``

### Setting up formatters

Formatters are simple classes used to name and clean file paths during upload, this plugin currently comes with two formatters.

* **DefaultFormatter**, this just returns the 'cleaned' filename
* **EntityFormatter**, extends the default formatter, expects an EntityInterface as data and used to format filenames based on data from an entity.

```php
$entity = $this->Posts->get(1);

$this->getFilesystem()
    ->upload(TMP . 'myfile.png', [
        'formatter' => 'Entity',
        'data' => $entity,
        'pattern' => '{entity-source}/{date-y}-{date-m}-{date-d}-{file-name}-{custom}.{file-ext}',
        'replacements' => [ 'custom' => 'key' ] // extra replacement patterns
    ]);
```

Should result in something like ``posts/2018-05-26-myfile-key.png`` .

### Creating a custom formatter class

Creating your own formatter class is pretty straightforward. The class should implement ``FormatterInterface`` Check the ``DefaultFormatter`` or ``EntityFormatter``classes for more information.

#### Example custom formatter
```php
<?php
namespace \Path\To\Formatters

use Josbeir\Filesystem\DefaultFormatter;

class MyFormatter extends DefaultFormatter
{
    // Extra settings?
    protected $_defaultConfig = [
        'mysetting1' => 'hello'
        'mysetting2' => 'world'
    ];

    public function getPath() : string
    {
        $setting = $this->getConfig('mysetting1');
        $setting2 = $this->getConfig('mysetting2');

        return $setting . DS . $setting2 . DS . $this->getBaseName();
    }
}
```

#### Using the custom formatter class in your application

The formatter FQCN can be set in the filesystem config or whenever you call setFormatter.

```php
$file = $this->getFilesystem()
    ->setFormatter('\Path\To\Formatters\MyFormatter')
    ->upload($file, [
        'mysetting2' => 'cool',
    ]);

debug($file->getPath()) // hello/cool/myfile.png
```

## Methods

The Filesystem class itself implements a few convenience methods around the Flysystem filesystem class.

Other methods are proxied over. If you wish to use the Flysystem instance directly then please use getDisk().

```php
// Upload a file
// Will fire Filesystem.beforeUpload and Filesystem.afterUpload
$this->getFilesystem()->upload($data, $config);

// Upload multiple files and returns a FileEntityCollection
// Will fire Filesystem.beforeUpload and Filesystem.afterUpload (after each file upload)
$this->getFilesystem()->uploadMany($files, $config);

// Copy an entity
// Will fire Filesystem.beforeCopy and Filesystem.afterCopy
$this->getFilesystem()->copy($entity, $config, $force);

// Rename an entity
// Will fire Filesystem.beforeRename and Filesystem.afterRename
$this->getFilesystem()->rename($entity, $config, $force);

// Delete an entity from the FS
// Will fire Filesystem.beforeDelete and Filesystem.afterDelete
$this->getFilesystem()->delete($entity);

// Check if a file entity exists on the FS
$this->getFilesystem()->exists($entity);

// Get Flysystem FS instance
$this->getFilesystem()->getDisk();

// Get Flysystem adatapter
$this->getFilesystem()->getAdapter();

// Set the formatter class name to be used
$this->getFilesystem()->setFormatter($name);

// Return a new formatter instance
$this->getFilesystem()->newFormatter($filename, $config);

// Reset formatter and adapter to default configuration
$this->getFilesystem()->reset();
```

## Events

Events are dispatched when performing an operation on a file entity.
Currently the following events are implemented:

| Name | Passed params | Stoppable?  |
|------| ---------- | ----------- |
| Filesystem.beforeUpload | FileSource, Formatter | No
| Filesystem.afterUpload | FileEntity, FileSource | No
| Filesystem.beforeDelete | FileEntity | Yes
| Filesystem.afterDelete | FileEntity | No
| Filesystem.beforeRename | FileEntity, new path | Yes
| Filesystem.afterRename | FileEntity | No
| Filesystem.beforeCopy | FileEntity, destination path | Yes
| Filesystem.afterCopy | (new) FileEntity, (old) FileEntity | No

## Extras

Because this plugin is using flysystem at its core one could easily integrate with other flysystem compatible code.
Accessing the flysystem directly can be done using ``Filesystem::getDisk()``.

As an example we can work with [Admad's glide plugin](https://github.com/ADmad/cakephp-glide) and use configured filesystems as source and cache:

First set up your default and cache configurations:

```php
<?php
return [
    'Filesystem' => [
        'default' => [
            'adapter' => 'Local',
            'adapterArguments' => [ WWW_ROOT . 'assets' . DS . 'local' ],
            'entityClass' => 'App\Model\Entity\FilesystemFile'
        ],
        'cache' => [
            'adapter' => 'Local',
            'adapterArguments' => [ WWW_ROOT . 'assets' . DS . 'cached' ],
        ]
    ]
];
```

Then set up the Glide middleware using the configured filesystems mentioned above:

```php
use FilesystemAwareTrait;

.. 
..

$routes->registerMiddleware('glide', new GlideMiddleware([
    'server' => [
        'source' => $this->getFilesystem()->getDisk(),
        'cache' => $this->getFilesystem('cache')->getDisk()
    ]
]));

$routes->scope('/images', [ 'cache' => false ], function ($routes) {
    $routes->applyMiddleware('glide');
    $routes->connect('/*');
});
```

## Contribute

Before submitting a PR make sure:

- [PHPUnit](http://book.cakephp.org/3.0/en/development/testing.html#running-tests)
and [CakePHP Code Sniffer](https://github.com/cakephp/cakephp-codesniffer) tests pass
- [Codecov Code Coverage ](https://codecov.io/gh/josbeir/cakephp-filesystem) does not drop
