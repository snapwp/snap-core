<?php

namespace Snap\Database;

class MediaQuery extends PostQuery
{
    /**
     * @var string[]
     */
    private $extensions;

    /**
     * @var array[]
     */
    private $types;

    /**
     * MediaQuery constructor.
     */
    public function __construct()
    {
        parent::__construct('attachment');
        $this->params['ignore_sticky_posts'] = true;
        $this->params['post_status'] = 'inherit';

        $this->setupMimes();
    }

    /**
     * Query by media type.
     *
     * Accepted values are image, audio, video, document, spreadsheet, interactive, text, archive, code
     *
     * @param string $type
     * @return $this
     * @see \wp_get_ext_types()
     */
    public function whereType(string $type): MediaQuery
    {
        if (isset($this->types[$type])) {
            foreach ($this->types[$type] as $ext) {
                $this->maybeAddMime($ext);
            }
        }

        return $this;
    }

    /**
     * @param string|array $extensions
     * @return $this
     */
    public function whereExtension($extensions): MediaQuery
    {
        collect($extensions)->map(
            function ($ext) {
                $this->maybeAddMime($ext);
            }
        );

        return $this;
    }

    /**
     * Setup mime lookups.
     */
    private function setupMimes(): void
    {
        $this->types = \wp_get_ext_types();

        foreach (\wp_get_mime_types() as $key => $mime) {
            foreach (\explode('|', $key) as $ext) {
                $this->extensions[$ext] = $mime;
            }
        }
    }

    /**
     * @param string $ext
     */
    private function maybeAddMime(string $ext): void
    {
        if (isset($this->extensions[$ext])) {
            $this->params['post_mime_type'][] = $this->extensions[$ext];
        }
    }
}
