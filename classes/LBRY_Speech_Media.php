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
    public $id;

    public $name;

    public $file;

    public $type;

    public $nsfw;

    public $license;

    public $title;

    public $description;

    public $thumbnail;

    private $is_image = false;

    public function __construct(int $attachment_id, $args = array(), bool $is_image = false)
    {

        // Set supplied arguments
        $default = array(
            'nsfw'          => null,
            'license'       => null,
            'description'   => null,
            'thumbnail'     => null
        );

        $settings = array_merge($default, array_intersect_key($args, $default));

        foreach ($settings as $key => $value) {
            $this->{$key} = $value;
        }

        // Flag as image if it is one
        if ($is_image) {
            $this->is_image = true;
        }

        

        // // Get attachment ID, name, file, and type from the URL
        // $url = strtok($url, '?'); // Clean up query params first
        // $id = $this->rigid_attachment_url_to_postid($url);
        $meta = wp_get_attachment_metadata($attachment_id);
        error_log(print_r($meta, true));
        $attachment = get_post($id);
        $path = get_attached_file($id);
        // $type = $attachment->post_mime_type;
        // $filename = wp_basename($path);
        //
        // $this->id = $id;
        // // COMBAK: Probably wont need this underscore check with Daemon V3
        // $this->name = str_replace('_', '-', $attachment->post_name);
        // $this->file = new CURLFile($path, $type, $filename);
        // $this->type = $type;
        // $this->title = $attachment->post_title;
    }



    public function is_image()
    {
        return $this->is_image;
    }
}
