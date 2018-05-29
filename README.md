[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://travis-ci.org/josbeir/cakephp-filesystem.svg?branch=master)](https://travis-ci.org/josbeir/cakephp-filesystem)

# Filesystem plugin for CakePHP

(WIP) CakePHP filesystem plugin using [Flysystem](http://flysystem.thephpleague.com/docs/) as it's backend.

## Why use it

- Easy access to Flysystem filesystems in your application
- Upload normalization, accepts $_FILES, Zend\Diactoros\UploadedFile or just a path on the local FS
- Files are represented by customisable and json serialisable entities, Multiple files are returned in a custom [Collection](https://book.cakephp.org/3.0/en/core-libraries/collections.html) instance.
- A trait is available, use it everywhere in your app
- Customizable path/filename formatting during upload, custom formatters are possible, ships with a Default and EntityFormatter.

## Requirements

* CakePHP 3.6+

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require josbeir/cakephp-filesystem:dev-master
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
            'adapterArguments' => [ WWW_ROOT . 'files' ],
        ],
        'other' => [
            'adapter' => 'Local',
            'adapterArguments' => [ WWW_ROOT . 'cache' ],
            'entityClass' => '\My\Cool\EntityClass
        ]
    ]
];
```

## Simple upload example

Filesystem instances can be accessed from everywhere where you either use the **FilesystemAwareTrait** and calling ```MyClass::getFilesystem($configKey)``` or the ```FilesystemRegistry::get()```

## Upload data

```php
 [
    'tmp_name' => '/tmp/blabla',
    'filename' => 'lame filename.png',
    'error' => 0,
    'size' => 1337,
    'type' => 'image/png'
]
```

### Upload magic

In this example we are using a fictive 'myfs' filesystem definition, if you leave that empty the default FS will be returned.

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

```yaml
object(Josbeir\Filesystem\FileEntity) {

    'path' => 'articles/now_im_called_bar.png',
    'originalFilename' => 'lame filename.png',
    'filesize' => (int) 28277,
    'mime' => 'image/png',
    'hash' => '6b16dafccd78955892d3eae973b49c6c',
    'created' => object(Cake\I18n\Time) {

        'time' => '2018-05-27T15:31:54+00:00',
        'timezone' => '+00:00',
        'fixedNowTime' => false

    }

}
```

### File entity methods

File entitites implement \Jsonserialzable interface.

Magic getters and settings can be used to access properties on the entity

Some methods are predefined
```php
FileEntity::getPath();
FileEntity::getHash();
FileEntity::hasHash($hash);
FileEntity::toArray();
```

## Using your own entities

Creating your own entities is possible by implementing the FileEntityInterface class and setting the entity class FQCN in your configuration's ``entityClass`` key.

## Formatters

During upload a formatter is used to set the path and filename. For instance, if you use the EntityFormatter you can use variables available in an entity to build the filename

```php
$entity = $this->Posts->get(1);

$fileEntity = $this->getFilesystem()->upload(TMP . 'myfile.png', [
    'formatter' => 'Entity', // formatter to use
    'data' => $entity // data to pass to the formatter
]);

// The default EntityFormatter pattern is {entity-source}/{file-name}.{file-ext}
// Should result in something posts/myfile.png
```

### Setting formatter patterns and extra key value data

Formatters are classes used to name the files during upload, this plugins comes with two formatters.

* **DefaultFormatter**, this just returns the 'cleaned' filename
* **EntityFormatter**, extends the default formatter, expect an EntityInterface as argument, used to format filenames based on data from an entity

Formatter patterns can be set be either creating your own formatter class or setting the pattern before calling the upload method

```php
$entity = $this->Posts->get(1);

$this->getFilesystem()
    ->setFormatter('Entity', [
        'pattern' => '{entity-source}/{date-y}-{date-m}-{date-d}-{file-name}-{custom}.{file-ext}',
        'replacements' => [ 'custom' => 'key' ] // extra replacement key/values that can be used
    ])
    ->upload(TMP . 'myfile.png', [
        'data' => $entity
    ]);

    // Should result in something posts/2018-05-26-myfile-key.png
```

Creating your own formatter class is pretty straightforward. The class should implement ``FormatterInterface`` and consists of two methods, getPath and setInfo. Check Default or EntityFormatter for more information.

```php
$this->getFilesystem()
    ->setFormatter('\My\Cool\Formatter')
    ->upload(TMP . 'myfile.png', [
        'data' => $entity
    ]);
```

## Methods

The Filesystem class itself implements a few convenience methods around the Flysystem Fs.

Other methods are proxied to the Flysystem filesystem. If you wish to use th Flysystem FS directly then please use getDisk() wich returns the Filesystm instance.

```php
// Upload a file
// Will fire Filesystem.beforeUpload and Filesystem.afterUpload
$this->getFilesystem()->upload($data, $config);

// Upload multiple files
// Will fire Filesystem.beforeUpload and Filesystem.afterUpload (after each file upload)
$this->getFilesystem()->uploadMany($files, $config);

// Merge existing uploaded entities with newly uploaded entities/files
// Can optionally remove items from the resultset based on an array of filehash keys
$this->getFilesystem()->mergeEntities($entities, $data, $config);

// Rename an entity
// Will fire Filesystem.beforeRename and Filesystem.afterRename
$this->getFilesystem()->rename($entity, $newPath);

// Delete an entity from the FS
// Will fire Filesystem.beforeDelete and Filesystem.afterDelete
$this->getFilesystem()->delete($entity);

// Check if a file entity exists on the FS
$this->getFilesystem()->exists($entity);

// Get Flysystem FS instance
$this->getFilesystem()->getDisk();

// Get Flysystem adatapter
$this->getFilesystem()->getAdapter();
```

## Recreating entities

If you for instance saved a file entity somwhere as a json object you could recreate the entity using `Filemanager::newEntity`

```php
$entity = $this->getFilesystem()->newEntity([
    'path' => 'articles/now_im_called_bar.png',
    'originalFilename' => 'lame filename.png',
    'filesize' => 28277,
    'mime' => 'image/png',
    'hash' => '6b16dafccd78955892d3eae973b49c6c',
    'created' => '2018-05-27T15:31:54+00:00'
]);
```
