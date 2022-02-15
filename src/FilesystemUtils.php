<?php
declare(strict_types=1);

namespace Josbeir\Filesystem;

class FilesystemUtils
{
    /**
     * Normalize php's weird multi upload structure
     *
     * @link http://php.net/manual/en/features.file-upload.multiple.php
     * @param array $files denormalized $_FILES structure
     * @return array
     */
    public static function normalizeFilesArray(array $files): array
    {
        $output = [];
        $fileCount = count($files['name']);
        $fileKeys = array_keys($files);

        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $output[$i][$key] = $files[$key][$i];
            }
        }

        return $output;
    }
}
