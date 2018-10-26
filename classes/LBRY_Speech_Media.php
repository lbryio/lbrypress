<?php
/**
 * Data container for necessary Speech api media uploaduploads
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */

class LBRY_Speech_Media
{
    public $name;

    public $file;

    public $type;

    public $nsfw;

    public $license;

    public $title;

    public $description;

    public $thumbnail;

    public function __construct($url, $args = array())
    {

        // Set supplied arguments
        $default = array(
            'nsfw'          => null,
            'license'       => null,
            'title'         => null,
            'description'   => null,
            'thumbnail'     => null
        );

        $settings = array_merge($default, array_intersect_key($args, $default));

        foreach ($settings as $key => $value) {
            $this->{$key} = $value;
        }

        // TODO: Name can't have extension in it.
        // TODO: Get attachment ID... so we can mark postmeta when needed.
        // Get name, file, and type from the URL
        // First, strip of any query params
        $url = strtok($url, '?');
        $path = ABSPATH . str_replace(home_url(), '', $url);
        error_log($path);
        $type = mime_content_type($path);
        $name = wp_basename($url);
        $this->name = $name;
        $this->file = new CURLFile($path, $type, $name);
        $this->type = $type;
    }
}
