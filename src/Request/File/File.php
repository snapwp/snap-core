<?php

namespace Snap\Request\File;

use finfo;

class File
{
    protected $data = [];

    protected $guessed_mime = null;

    public function __construct($file)
    {
        $this->data = $file;
        $this->guessed_mime = $this->guess_mime();
        // client mime
        // esxtension
        // server mime
        // get error string
        // get client extension

    }

    private function guess_mime()
    {
        if (\function_exists('finfo_open')) {
            $finfo = new finfo;

            $guessed_mime = $finfo->file($this->data['tmp_name'], FILEINFO_MIME_TYPE );

            if ($guessed_mime) {
                return $guessed_mime;
            }
        }

        if (\function_exists('mime_content_type')) {
            $guessed_mime = \mime_content_type($this->data['tmp_name']);

            if ($guessed_mime) {
                return $guessed_mime;
            }
        }

        return null;
    }
}
