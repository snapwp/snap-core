<?php

namespace Snap\Http\Request\File;



class File
{
    protected $data = [];

    protected $client_name;
    protected $client_type;
    protected $client_extension;
    //protected $name;
    protected $path;
    protected $size;
    protected $error;
    // protected $type;
    // $extension;
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
        $this->client_extension = $this->get_ext();

        $this->path = $file['tmp_name'];
        $this->size = $file['size'];
        $this->error = $file['error'];


        $this->is_allowed = $this->check_if_allowed();

        $this->check_upload();
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
        return UPLOAD_ERR_OK === $this->error && \is_uploaded_file($this->path);
    }

    public function rename_file($new_name)
    {
        $this->data['name'] = $new_name . ".{$this->client_extension}";
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
        return \size_format($this->size, $precision);
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
        return (int) $this->size;
    }

    /**
     * Returns a human readable error string for the file.
     *
     * @since 1.0.0
     *
     * @return bool|string Returns false if no error.
     */
    public function get_error_message()
    {
        if ($this->is_valid()) {
            return false;
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

        if (isset($errors[$this->error])) {
            $message = $errors[$this->error];
        }

        return \sprintf($message, $this->client_name, \size_format(\wp_max_upload_size()));
    }



    /**
     * Get the client ext of the uploaded file.
     *
     * @since 1.0.0
     */
    private function get_ext()
    {
        $ext = \pathinfo($this->client_name, PATHINFO_EXTENSION);

        if (!empty($ext)) {
            return $ext;
        }

        return null;
    }

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

        return $result;
    }

    public function add_to_media_library()
    {
        /*
         * $attachment = array(
	'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
	'post_mime_type' => $filetype['type'],
	'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
	'post_content'   => '',
	'post_status'    => 'inherit'
);

// Insert the attachment.
$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once( ABSPATH . 'wp-admin/includes/image.php' );

// Generate the metadata for the attachment, and update the database record.
$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
wp_update_attachment_metadata( $attach_id, $attach_data );
         */
    }

    public function get_original_data()
    {
        return $this->data;
    }

    private function check_if_allowed()
    {
        $allowed_types = \get_allowed_mime_types();

        return \in_array($this->client_type, $allowed_types);
    }


    private function check_upload()
    {
        $check = \wp_check_filetype_and_ext($this->path, $this->client_name);

        if ($check['type'] === false || $check['ext'] === false) {
            $this->is_allowed = false;
            return;
        }

        //if ($check['ext'] !== $this->extension) {
        //    $this->extension = $check['ext'];
        //}

        //if ($check['proper_filename'] !== false && $check['proper_filename'] !== $this->name) {
        //    $this->name = $check['proper_filename'];
        //}

        //if ($check['type'] !== $this->type) {
        //    $this->type = $check['type'];
        //}
    }
}
