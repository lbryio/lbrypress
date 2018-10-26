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

    public function __construct($url, $args = array())
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

        // Get attachment ID, name, file, and type from the URL
        $url = strtok($url, '?'); // Clean up query params first
        $id = $this->rigid_attachment_url_to_postid($url);
        $attachment = get_post($id);
        $path = get_attached_file($id);
        $type = $attachment->post_mime_type;
        $filename = wp_basename($path);

        $this->id = $id;
        // COMBAK: Probably wont need this underscore check with Daemon V3
        $this->name = str_replace('_', '-', $attachment->post_name);
        $this->file = new CURLFile($path, $type, $filename);
        $this->type = $type;
        $this->title = $attachment->post_title;
    }

    /**
     * Checks for image crop sizes and filters out query params
     * Courtesy of this post: http://bordoni.me/get-attachment-id-by-image-url/
     * @param  string   $url    The url of the attachment you want an ID for
     * @return int              The found post_id
     */
    private function rigid_attachment_url_to_postid($url)
    {
        $post_id = attachment_url_to_postid($url);

        if (! $post_id) {
            $dir = wp_upload_dir();
            $path = $url;

            if (0 === strpos($path, $dir['baseurl'] . '/')) {
                $path = substr($path, strlen($dir['baseurl'] . '/'));
            }

            if (preg_match('/^(.*)(\-\d*x\d*)(\.\w{1,})/i', $path, $matches)) {
                $url = $dir['baseurl'] . '/' . $matches[1] . $matches[3];
                $post_id = attachment_url_to_postid($url);
            }
        }

        return (int) $post_id;
    }
}
