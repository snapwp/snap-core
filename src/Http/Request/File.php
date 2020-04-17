<?php

namespace Snap\Http\Request;

/**
 * Uploaded file wrapper.
 */
class File
{
    /**
     * The raw $_FILES array.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The filename as provided by the user.
     *
     * This is not necessarily the final uploaded file name, and should be treated as unsafe.
     *
     * @var string
     */
    protected $client_name;

    /**
     * The client MIME type of the uploaded file.
     *
     * @var string
     */
    protected $client_type;

    /**
     * The file extension as derived from the client name.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @var string
     */
    protected $client_extension;

    /**
     * Populated with the real file type if WordPress can guess it.
     *
     * @var null|string
     */
    protected $guessed_type = null;

    /**
     * Populated with the real file extension if WordPress can guess it.
     *
     * @var null|string
     */
    protected $guessed_extension = null;

    /**
     * Upload error string. Populated after upload.
     *
     * @var null|string
     */
    protected $upload_error = null;

    /**
     * Whether the uploaded file is allowed to be uploaded by the current user.
     *
     * Use the upload_mimes filter to add additional types.
     *
     * @var boolean
     */
    protected $is_allowed = false;

    /**
     * Human readable file upload errors.
     *
     * @var array
     */
    protected $errors = [
        UPLOAD_ERR_INI_SIZE => 'The file "%s" exceeds the maximum upload size (limit is %s).',
        UPLOAD_ERR_FORM_SIZE => 'The file "%s" exceeds the upload limit defined in your form.',
        UPLOAD_ERR_PARTIAL => 'The file "%s" was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
        UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
        UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
    ];

    /**
     * Holds the UploadedFile instance if uploaded to the server.
     *
     * @var null|\Snap\Http\Request\UploadedFile
     */
    private $uploaded_file = null;

    /**
     * File constructor.
     *
     * @param array $file The raw data from the $_FILES array.
     */
    public function __construct($file)
    {
        $this->data = $file;
        $this->client_name = $file['name'];
        $this->client_type = $file['type'];
        $this->client_extension = $this->setClientExtension();
        $this->is_allowed = $this->checkIfAllowed();
    }

    /**
     * Whether the file upload was successful or not.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return UPLOAD_ERR_OK === $this->data['error'] && \is_uploaded_file($this->data['tmp_name']);
    }

    /**
     * Whether the file can be uploaded by the current user.
     *
     * @return bool
     */
    public function canUpload()
    {
        return (bool)$this->isValid() && $this->is_allowed;
    }

    /**
     * Output a human readable file size.
     *
     * @param int $precision Optional. The amount of decimal places to show.
     *                       Defaults to 2.
     * @return false|string
     */
    public function getFormattedSize(int $precision = 2)
    {
        return \size_format($this->data['size'], $precision);
    }

    /**
     * Get file size in bytes.
     *
     * @return int
     */
    public function getSize(): int
    {
        return (int)$this->data['size'];
    }

    /**
     * Get temporary file path.
     *
     * @return string
     */
    public function getTmpPath(): string
    {
        return $this->data['tmp_name'];
    }

    /**
     * Get the upload error code.
     *
     * @return int
     */
    public function getError(): int
    {
        return (int)$this->data['error'];
    }

    /**
     * Get the client name for the uploaded file.
     *
     * This is not to be considered a safe or trusted value.
     *
     * @return string|null
     */
    public function getClientFilename(): ?string
    {
        return !empty($this->client_name) ? $this->client_name : null;
    }

    /**
     * Get the client MIME type.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @return string|null
     */
    public function getClientMediaType(): ?string
    {
        return !empty($this->client_type) ? $this->client_type : null;
    }

    /**
     * Get the client file extension.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @return string|null
     */
    public function getClientExtension(): ?string
    {
        return !empty($this->client_extension) ? $this->client_extension : null;
    }

    /**
     * Get the guessed MIME type.
     *
     * @return string|null
     */
    public function getMediaType(): ?string
    {
        return $this->guessed_type;
    }

    /**
     * Get the guessed file extension.
     *
     * @return string|null
     */
    public function getExtension(): ?string
    {
        return $this->guessed_extension;
    }

    /**
     * Get the uploaded file error string if the upload was not successful.
     *
     * @return string|null
     */
    public function getUploadError(): ?string
    {
        return $this->upload_error;
    }

    /**
     * Returns a human readable error string for the file.
     *
     * @return null|string Returns null if no error.
     */
    public function getErrorMessage(): ?string
    {
        if ($this->isValid() === true) {
            return null;
        }

        $message = 'The file "%s" was not uploaded due to an unknown error.';

        if (isset($this->errors[$this->data['error']])) {
            $message = $this->errors[$this->data['error']];
        }

        return \sprintf($message, $this->client_name, \size_format(\wp_max_upload_size()));
    }

    /**
     * Rename the uploaded file before further processing.
     *
     * This will change the filename only, not the extension.
     *
     * @param  string $new_name The new filename.
     * @return $this
     */
    public function rename($new_name)
    {
        $this->data['name'] = \trim($new_name) . ".{$this->client_extension}";
        return $this;
    }

    /**
     * If a file has been been uploaded, return the UploadedFile object for it.
     *
     * @return \Snap\Http\Request\UploadedFile|null
     */
    public function getUploadedFile()
    {
        return $this->uploaded_file;
    }

    /**
     * Move the file to the uploads folder.
     *
     * Defaults to standard WP behavior, such as uploading the file to the year/month
     * sub folder.
     *
     * @param  string  $target          Optional. Sub folder within the uploads directory to upload to.
     *                                  Will be created if it doesn't exist.
     * @param  boolean $append_sub_dirs Optional. Whether to include the year/month folders when using
     *                                  a custom target directory.
     *                                  Defaults to false.
     * @return bool                     Whether the upload was successful.
     */
    public function upload($target = null, $append_sub_dirs = false)
    {
        $data = $this->data;

        if (!\function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        if ($target !== null) {
            $closure = function ($upload_dir) use ($target, $append_sub_dirs) {

                if ($append_sub_dirs !== false) {
                    $upload_dir['subdir'] = '/' . \untrailingslashit($target) . $upload_dir['subdir'];
                } else {
                    $upload_dir['subdir'] = '/' . \untrailingslashit($target);
                }

                $upload_dir['path'] = $upload_dir['basedir'] . $upload_dir['subdir'];
                $upload_dir['url'] = $upload_dir['baseurl'] . $upload_dir['subdir'];

                return $upload_dir;
            };

            \add_filter('upload_dir', $closure);

            $result = \wp_handle_upload($data, ['test_form' => false]);

            if ($target !== null) {
                \remove_filter('upload_dir', $closure);
            }
        } else {
            // Just handle the file.
            $result = \wp_handle_upload($data, ['test_form' => false]);
        }

        // If upload was successful...
        if (isset($result['file'])) {
            $this->uploaded_file = new UploadedFile($result);
            return true;
        }

        if (isset($result['error'])) {
            $this->upload_error = $result['error'];
        }

        return false;
    }

    /**
     * Return a base64 encoded representation of the upload.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return \base64_encode(\file_get_contents($this->getTmpPath()));
    }

    /**
     * Set the client ext of the uploaded file.
     *
     * @return string|null
     */
    private function setClientExtension(): ?string
    {
        $ext = \pathinfo($this->client_name, PATHINFO_EXTENSION);

        if (!empty($ext)) {
            return $ext;
        }

        return null;
    }

    /**
     * Checks if the file passes WordPress upload checks.
     *
     * @return bool
     */
    private function checkIfAllowed(): bool
    {
        // Really the unfiltered_upload should never be enabled, but deal with it anyway.
        if (\current_user_can('unfiltered_upload')) {
            return true;
        }

        $error_level = \error_reporting();
        \error_reporting($error_level & ~E_NOTICE);

        $check = \wp_check_filetype_and_ext($this->data['tmp_name'], $this->client_name);

        \error_reporting($error_level);

        if ($check['type'] !== false) {
            $this->guessed_type = $check['type'];
            $this->guessed_extension = $check['ext'];
        }

        if ($check['type'] === false || $check['ext'] === false) {
            return false;
        }

        $allowed_types = \get_allowed_mime_types();

        return \in_array($this->client_type, $allowed_types);
    }
}
