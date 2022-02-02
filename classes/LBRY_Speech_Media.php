<?php
/**
 * Data container for necessary Speech api media uploaduploads
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

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

    // public $image_size = false;

    public function __construct(int $attachment_id, $args = array())
    {

        // Set supplied arguments
        $default = array(
            'nsfw'          => null,
            'license'       => null,
            'description'   => null,
            'thumbnail'     => null,
            // 'image_size'    => false,
        );

        $settings = array_merge($default, array_intersect_key($args, $default));

        foreach ($settings as $key => $value) {
            $this->{$key} = $value;
        }


        // Get attachment ID, name, file, and type from the URL
        $this->id = $attachment_id;


        $path = get_attached_file($this->id);

        // Apply data dependent on whether this is an image 'size' or not
        // if ($this->image_size) {
        //     $meta = wp_get_attachment_metadata($this->id)['sizes'][$this->image_size];
        //     $pathinfo = pathinfo($meta['file']);
        //     $ext = '.' . $pathinfo['extension'];
        //     $new_ext = '-' . $meta['width'] . 'x' . $meta['height'] . $ext;
        //     $path = str_replace($ext, $new_ext, $path);
        //     $filename = $pathinfo['basename'];
        //     // COMBAK: Probably wont need this underscore check with Daemon V3
        //     $this->name = str_replace('_', '-', $pathinfo['filename']);
        //     $this->type = $meta['mime-type'];
        //     $this->title = $pathinfo['filename'];
        // } else {
        $attachment = get_post($this->id);
        $filename = wp_basename($path);
        $this->name = str_replace('_', '-', $attachment->post_name);
        $this->type = $attachment->post_mime_type;
        $this->title = $attachment->post_title;
        // }

        $this->file = new CURLFile($path, $this->type, $filename);
    }
}
