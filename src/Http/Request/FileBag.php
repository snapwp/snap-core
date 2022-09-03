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
     */
    private array $file_keys = [
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
     * @return mixed|File|File[]
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Helper::arrayGet($this->data, $key, $default);
    }

    /**
     * Checks if a key is present in the bag.
     */
    public function has(string $key): bool
    {
        return !empty(Helper::arrayGet($this->data, $key));
    }

    /**
     * Return how many files were uploaded for a given key.
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
     */
    protected function setData(array $contents = []): void
    {
        foreach ($contents as $key => $file) {
            $this->data[$key] = $this->addFile($file);
        }
    }

    /**
     * Turn $_FILES data into File instances, or null if no file was uploaded.
     */
    protected function addFile(array|File $file = []): File|array|null
    {
        if ($file instanceof File) {
            return $file;
        }

        $normalised = $this->formatFilesArray($file);

        if (\is_array($normalised)) {
            $keys = \array_keys($normalised);
            \sort($keys);

            if ($keys === $this->file_keys) {
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
     */
    protected function formatFilesArray(mixed $data): ?array
    {
        if (!\is_array($data)) {
            return $data;
        }

        // PHP 8.1 introduced a new key into the $_FILES array. We don't need it, so unset.
        if (isset($data['full_path'])) {
            unset($data['full_path']);
        }

        $keys = \array_keys($data);
        \sort($keys);

        if ($this->file_keys !== $keys || !isset($data['name']) || !\is_array($data['name'])) {
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
