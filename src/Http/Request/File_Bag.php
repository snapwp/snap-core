<?php

namespace Snap\Http\Request;

use finfo;
use Snap\Http\Request\Bag;
use Snap\Http\Request\File\File;

/**
 * File parameter bag.
 */
class File_Bag extends Bag
{
    private $file_keys = [
        'error',
        'name',
        'size',
        'tmp_name',
        'type'
    ];

    /**
     * Add the individual files to the bag.
     *
     * @since 1.0.0
     *
     * @param array $contents
     */
    protected function set_data($contents = [])
    {
        foreach ($contents as $key => $file) {
            $this->data[$key] = $this->add_file($file);
        }
    }

    /**
     * Turn $_FILES data into File instances, or null if no file was uploaded.
     *
     * @since 1.0.0
     *
     * @param array $file
     * @return array|null|File
     */
    protected function add_file($file = [])
    {
        if ($file instanceof File) {
            return $file;
        }

        $normalised = $this->format_files_array($file);

        if (\is_array($normalised)) {
            $keys = \array_keys($normalised);
            \sort($keys);

            if ($keys == $this->file_keys) {
                if (UPLOAD_ERR_NO_FILE === $normalised['error']) {
                    $normalised = null;
                } else {
                    $normalised = new File($normalised);
                }
            } else {
                $normalised = \array_map([$this, 'add_file'], $normalised);

                if (\array_keys($keys) === $keys) {
                    $normalised = \array_filter($normalised);
                }
            }
        }

        return $normalised;
    }

    /**
     * Fixes a malformed PHP $_FILES array.
     *
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     *
     * This method fixes the array to look like the "normal" $_FILES array.
     *
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @param $data
     * @return array
     */
    protected function format_files_array($data)
    {
        if (!\is_array($data)) {
            return $data;
        }

        $keys = \array_keys($data);
        \sort($keys);

        if ($this->file_keys != $keys || !isset($data['name']) || !\is_array($data['name'])) {
            return $data;
        }

        $files = $data;

        foreach ($this->file_keys as $k) {
            unset($files[$k]);
        }

        foreach ($data['name'] as $key => $name) {
            $files[$key] = $this->format_files_array([
                'error' => $data['error'][$key],
                'name' => $name,
                'type' => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size' => $data['size'][$key],
            ]);
        }

        return $files;
    }

    /**
     * Checks if a key is present in the bag.
     *
     * @since 1.0.0
     *
     * @param  string $key Item key to check.
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->data[$key]) && !empty($this->data[$key]);
    }
}
