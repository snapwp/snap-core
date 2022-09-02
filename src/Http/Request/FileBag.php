<?php

namespace Snap\Http\Request;

use Rakit\Validation\Helper;

/**
 * File parameter bag.
 */
class FileBag extends Bag
{
    /**
     * Expected file keys.
     *
     * @var array
     */
    private $file_keys = [
        'error',
        'name',
        'size',
        'tmp_name',
        'type',
    ];

    /**
     * Gets a file from the bag, or a supplied default if not present.
     *
     * Use get_raw to get an un-sanitized version (should you need to).
     *
     * @param  string $key     Item key to fetch.
     * @param  mixed  $default Default value if the key is not present.
     * @return mixed|\Snap\Http\Request\File|\Snap\Http\Request\File[]
     */
    public function get(string $key, $default = null)
    {
        return Helper::arrayGet($this->data, $key, $default);
    }

    /**
     * Checks if a key is present in the bag.
     *
     * @param  string $key Item key to check.
     * @return boolean
     */
    public function has(string $key): bool
    {
        return !empty(Helper::arrayGet($this->data, $key));
    }

    /**
     * Return how many files were uploaded for a given key.
     *
     * @param string $key Item key to check.
     * @return int
     */
    public function count(string $key): int
    {
        if ($this->has($key)) {
            if (\is_array($this->get($key))) {
                return \count($this->get($key));
            }

            return 1;
        }

        return 0;
    }

    /**
     * Add the individual files to the bag.
     *
     * @param array $contents
     */
    protected function setData(array $contents = []): void
    {
        foreach ($contents as $key => $file) {
            $this->data[$key] = $this->addFile($file);
        }
    }

    /**
     * Turn $_FILES data into File instances, or null if no file was uploaded.
     *
     * @param array $file
     * @return array|null|File
     */
    protected function addFile($file = [])
    {
        if ($file instanceof File) {
            return $file;
        }

        $normalised = $this->formatFilesArray($file);

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
                $normalised = \array_map([$this, 'addFile'], $normalised);

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
    protected function formatFilesArray($data): array
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
            $files[$key] = $this->formatFilesArray(
                [
                    'error' => $data['error'][$key],
                    'name' => $name,
                    'type' => $data['type'][$key],
                    'tmp_name' => $data['tmp_name'][$key],
                    'size' => $data['size'][$key],
                ]
            );
        }

        return $files;
    }
}
