<?php

namespace Snap\Http\Request;

use SplFileInfo;

class UploadedFile extends SplFileInfo
{
    /**
     * The public URL to the file.
     *
     * @var null|string
     */
    private $public_url = null;

    /**
     * The MIME type of the file.
     *
     * @var null|string
     */
    private $media_type = null;

    /**
     * The attachment ID if added tot he media library.
     *
     * @var null|int
     */
    private $attachment_id = null;

    /**
     * UploadedFile constructor.
     *
     * @param array $upload_data The output of wp_handle_upload().
     */
    public function __construct(array $upload_data)
    {
        parent::__construct($upload_data['file']);

        $this->public_url = $upload_data['url'];
        $this->media_type = $upload_data['type'];
    }

    /**
     * Return the public URL to the file.
     *
     * @return null|string
     */
    public function getPublicUrl(): ?string
    {
        return $this->public_url;
    }

    /**
     * Return the MIME type of the file.
     *
     * @return null|string
     */
    public function getMediaType(): ?string
    {
        return $this->media_type;
    }

    /**
     * Return the attachment ID if added to the media library.
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int
    {
        return $this->attachment_id;
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
        return \size_format($this->getSize(), $precision);
    }

    /**
     * Add an uploaded file into the media library so it is usable in the WordPress admin.
     *
     * @return bool
     */
    public function addToMediaLibrary()
    {
        $attachment = [
            'guid' => $this->public_url,
            'post_mime_type' => $this->getMediaType(),
            'post_title' => $this->getFilename(),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        // Insert the attachment.
        $attach_id = \wp_insert_attachment($attachment);

        if ($attach_id == 0 || $attach_id instanceof \WP_Error) {
            return false;
        }

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the meta for the attachment.
        $attach_data = \wp_generate_attachment_metadata($attach_id, $this->getPathname());

        // Update attachment meta.
        \wp_update_attachment_metadata($attach_id, $attach_data);
        \update_attached_file($attach_id, $this->getPathname());

        $this->attachment_id = (int)$attach_id;

        return true;
    }

    /**
     * Remove the uploaded version of the file, and delete from the media library if added.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->getAttachmentId() !== null) {
            $result = !\wp_delete_attachment($this->getAttachmentId(), true) === false;
        } else {
            \wp_delete_file($this->getRealPath());
            $result = !\file_exists($this->getRealPath());
        }

        if ($result === true) {
            $this->attachment_id = null;
            $this->public_url = null;
            $this->media_type = null;
        }

        return $result;
    }

    /**
     * Return a base64 encoded representation of the upload.
     *
     * @return string
     */
    public function toBase64(): string
    {
        return \base64_encode(\file_get_contents($this->getRealPath()));
    }
}