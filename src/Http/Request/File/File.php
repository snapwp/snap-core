<?php

namespace Snap\Http\Request\File;



class File
{
    /**
     * The raw $_FILES array.
     *
     * @since 1.0.0
     * @var array
     */
    protected $data = [];

    /**
     * The filename as provided by the user. 
     * 
     * This is not necessarily the final uploaded file name, and should be treated as unsafe.
     *
     * @since 1.0.0
     * @var string
     */
    protected $client_name;

    /**
     * The client mime type of the uploaded file.
     *
     * @since 1.0.0
     * @var string
     */
    protected $client_type;

    /**
     * The file extension as derived from the client name.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @since 1.0.0
     * @var string
     */
    protected $client_extension;

    protected $upload_path = null;
    protected $upload_url = null;
    protected $upload_type = null;
    protected $upload_error = null;

    /**
     * Whether the uploaded file is allowed to be uploaded by the current user.
     *
     * Use the upload_mimes filter to add additional types.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $is_allowed = false;

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
        $this->client_extension = $this->set_client_extension();


        $this->is_allowed = $this->check_if_allowed();
    }

    /**
     * Whether the file upload was successful or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_valid()
    {
        return UPLOAD_ERR_OK === $this->data['error'] && \is_uploaded_file($this->data['tmp_name']);
    }

    /**
     * Whether the file can be uploaded by the current user.
     *
     * @since 1.0.0
     * 
     * @return bool
     */
    public function user_can_upload()
    {
        return (bool) $this->is_valid() && $this->is_allowed;
    }

    /**
     * Get the original $_FILES array.
     *
     * @since 1.0.0
     * 
     * @return array
     */
    public function get_original_data()
    {
        return $this->data;
    }

    /**
     * Output a human readable file size.
     *
     * @since 1.0.0
     *
     * @param int $precision Optional. The amount of decimal places to show.
     *                       Defaults to 2.
     * @return false|string
     */
    public function get_formatted_size(int $precision = 2)
    {
        return \size_format($this->data['size'], $precision);
    }

    /**
     * Get file size in bytes.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function get_size()
    {
        return (int) $this->data['size'];
    }

    /**
     * Get the upload error code.
     *
     * @since 1.0.0
     * 
     * @return int
     */
    public function get_error()
    {
        return (int) $this->data['error'];
    }

    /**
     * Get the client name for the uploaded file.
     *
     * This is not to be considered a safe or trusted value.
     *
     * @since 1.0.0
     * 
     * @return string
     */
    public function get_client_name()
    {
        return (string) $this->client_name;
    }

    /**
     * Get the client mime type.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @since 1.0.0
     * 
     * @return string
     */
    public function get_client_type()
    {
        return (string) $this->client_type;
    }

    /**
     * Get the client file extension.
     *
     * This is not necessarily correct, and should not be trusted.
     *
     * @since 1.0.0
     * 
     * @return string
     */
    public function get_client_extension()
    {
        return (string) $this->client_extension;
    }

    /**
     * Get the uploaded file path.
     *
     * @since 1.0.0
     * 
     * @return string|null
     */
    public function get_upload_path()
    {
        return $this->upload_path;
    }

    /**
     * Get the uploaded file URL.
     *
     * @since 1.0.0
     * 
     * @return string|null
     */
    public function get_upload_url()
    {
        return $this->upload_url;
    }

    /**
     * Returns a human readable error string for the file.
     *
     * @since 1.0.0
     *
     * @return null|string Returns null if no error.
     */
    public function get_error_message()
    {
        if ($this->is_valid()) {
            return null;
        }

        static $errors = [
            UPLOAD_ERR_INI_SIZE => 'The file "%s" exceeds the maximum upload size (limit is %s).',
            UPLOAD_ERR_FORM_SIZE => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        ];

        $message = 'The file "%s" was not uploaded due to an unknown error.';

        if (isset($errors[$this->data['error']])) {
            $message = $errors[$this->data['error']];
        }

        return \sprintf($message, $this->client_name, \size_format(\wp_max_upload_size()));
    }

    /**
     * Rename the uploaded file before further processing.
     *
     * This will change the filename only, not the extension.
     *
     * @since 1.0.0
     * 
     * @param  string $new_name The new filename.
     * @return File
     */
    public function rename($new_name)
    {
        $this->data['name'] = \trim($new_name) . ".{$this->client_extension}";

        return $this;
    }

    /**
     * Move the file to the uploads folder.
     *
     * Defaults to standard WP behavior, such as uploading the file to the year/month
     * subfolder.
     *
     * @param  string  $target          Optional. Subfolder within the uploads directory to upload to.
     *                                  Will be created if it doesn't exist.
     * @param  boolean $append_sub_dirs Optional. Whether to include the year/month folders when using
     *                                  a custom target directory.
     *                                  Defaults to false.
     * @return bool                     Whether the upload was successful.
     */
    public function upload($target = null, $append_sub_dirs = false)
    {
        if (!\function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        if ($target !== null) {
            $closure = function($upload_dir) use ($target, $append_sub_dirs) {
                $upload_dir['subdir'] = '/' . \untrailingslashit($target);

                if ($append_sub_dirs !== false) {
                    $upload_dir['subdir'] = '/' . \untrailingslashit($target) . $upload_dir['subdir'];
                }

                $upload_dir['path'] = $upload_dir['basedir'] . $upload_dir['subdir'];
                $upload_dir['url'] = $upload_dir['baseurl'] . $upload_dir['subdir'];

                return $upload_dir;
            };

            \add_filter('upload_dir', $closure);
        }

        $data = $this->get_original_data();

        $result = \wp_handle_upload($data, ['test_form' => false]);

        if ($target !== null) {
            \remove_filter('upload_dir', $closure);
        }

        if (isset($result['file'])) {
            $this->upload_path = $result['file'];
            $this->upload_url = $result['url'];
            $this->upload_type = $result['type'];

            return true;
        }

        if (isset($result['error'])) {
            $this->upload_error = $result['error'];
        }

        return false;
    }

    /**
     * Get the client ext of the uploaded file.
     *
     * @since 1.0.0
     */
    private function set_client_extension()
    {
        $ext = \pathinfo($this->client_name, PATHINFO_EXTENSION);

        if (!empty($ext)) {
            return $ext;
        }

        return null;
    }




    public function add_to_media_library()
    {
        if ($this->upload_path === false) {
            return false;
        }



        $attachment = [
            'guid'           => $this->upload_url,
            'post_mime_type' => $this->upload_type,
            'post_title'     => \pathinfo($filename, PATHINFO_FILENAME),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        // Insert the attachment.
        $attach_id = \wp_insert_attachment($attachment);

        if ($attach_id == 0 || $attach_id instanceof \WP_Error) {
            return false;
        }

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        // Generate the meta for the attachment.
        $attach_data = \wp_generate_attachment_metadata( $attach_id, $this->upload_path );

        // Update attachment meta.
        \wp_update_attachment_metadata($attach_id, $attach_data);
        \update_attached_file($attach_id, $this->upload_path);

        return true;
    }




    private function check_if_allowed()
    {
        $check = \wp_check_filetype_and_ext($this->data['tmp_name'], $this->client_name);

        if (
            ($check['type'] === false || $check['ext'] === false)
            && ! \current_user_can( 'unfiltered_upload' )
        ) {
            return false;
        }

        $allowed_types = \get_allowed_mime_types();

        return \in_array($this->client_type, $allowed_types);
    }
}
