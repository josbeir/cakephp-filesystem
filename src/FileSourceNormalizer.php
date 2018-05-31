<?php
namespace Josbeir\Filesystem;

use Cake\Core\InstanceConfigTrait;
use Cake\Filesystem\File;
use Josbeir\Filesystem\Exception\FilesystemException;
use Zend\Diactoros\UploadedFile;

/**
 * Normalize different upload entry points to a headache free dataset
 */
class FileSourceNormalizer
{
    use InstanceConfigTrait;

    protected $_defaultConfig = [
        'hashingAlgo' => 'md5',
        'fallbackFilename' => 'untitled'
    ];

    /**
     * Filename
     *
     * @var string
     */
    public $filename;

    /**
     * Resource handle
     *
     * @var resource
     */
    public $resource;

    /**
     * File hash
     *
     * @var string
     */
    public $hash;

    /**
     * File type
     *
     * @var string
     */
    public $mime;

    /**
     * File size
     *
     * @var int
     */
    public $size;

    /**
     * Constructor
     *
     * @param string|array|\Zend\Diactoros\UploadedFile $uploadData Mixed upload data
     * @param string $config Config options
     *
     * @throws \Josbeir\Filesystem\Exception\FilesystemException When after parsing no valid file resource could be detected
     */
    public function __construct($uploadData, array $config = [])
    {
        $this->setConfig($config);

        if ($uploadData instanceof UploadedFile) {
            $this->_handleUploadedFile($uploadData);
        }

        if (is_array($uploadData) && isset($uploadData['tmp_name'])) {
            $this->_handleFilesUpload($uploadData);
        }

        if (is_string($uploadData) && is_file($uploadData)) {
            $this->_handlePathUploads($uploadData);
        }

        if (!is_resource($this->resource)) {
            throw new FilesystemException(sprintf('Passed file (%s) does not contain a valid resource', $this->filename ?: 'unknown'));
        }
    }

    /**
     * Handle UploadedFile
     *
     * @param \Zend\Diactoros\UploadedFile $uploadedFile Instance of an UploadedFile
     *
     * @return void
     */
    protected function _handleUploadedFile(UploadedFile $uploadedFile) : void
    {
        $stream = $uploadedFile->getStream();
        $hash = hash($this->getConfig('hashingAlgo'), (string)$stream); // should do some benchmarks on this

        $this->filename = $uploadedFile->getClientFilename() ?: $this->getConfig('fallbackFilename');
        $this->resource = $stream->detach();
        $this->hash = $hash;
        $this->size = $uploadedFile->getSize();
        $this->mime = $uploadedFile->getClientMediaType();
    }

    /**
     * Handle 'vanilla' $_FILES upload
     *
     * @param array $file Vanilla $_FILES compatible array
     *
     * @return void
     */
    protected function _handleFilesUpload(array $file) : void
    {
        $this->filename = empty($file['name']) ? $this->getConfig('fallbackFilename') : $file['name'];
        $this->resource = fopen($file['tmp_name'], 'r+');
        $this->hash = hash_file($this->getConfig('hashingAlgo'), $file['tmp_name']);
        $this->size = $file['size'];
        $this->mime = $file['type'];
    }

    /**
     * Handle path (location) based uploads
     *
     * @param string $path Path to file
     *
     * @return void
     */
    protected function _handlePathUploads(string $path) : void
    {
        $file = new File($path);

        $this->filename = $file->name ?: $this->getConfig('fallbackFilename');
        $this->resource = fopen($path, 'r+');
        $this->hash = hash_file($this->getConfig('hashingAlgo'), $path);
        $this->size = $file->size();
        $this->mime = $file->mime();
    }

    /**
     * Cleanup method
     *
     * @return void
     */
    public function shutdown()
    {
        fclose($this->resource);
    }

    /**
     * Return string representation
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->filename;
    }
}
