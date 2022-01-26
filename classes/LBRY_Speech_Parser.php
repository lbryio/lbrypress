<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Speech_Parser
{
    // COMBAK: May not need this, as replace_attachment_image_src may cover all use cases
    /**
     * Replace img srcset attributes with Spee.ch urls
     * Check https://developer.wordpress.org/reference/functions/wp_calculate_image_srcset/ hook for details
     */
    public function replace_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        // If we don't have a speech URL, bail
        if (!LBRY()->speech->is_published($image_meta)) {
            return;
        }

        $new_sources = $sources;

        foreach ($sources as $width => $source) {
            if ($cropped_url = $this->get_speech_crop_url($source['url'], $image_meta)) {
                $new_sources[$width]['url'] = $cropped_url;
            }
        }
        return $new_sources;
    }

    /**
    * Replaces the output of the 'wp_get_attachment_image_src' function
    * Check https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src/ for details
    */
    public function replace_attachment_image_src($image, $attachment_id, $size)
    {
        if (!$image) {
            return $image;
        }

        $image_meta = wp_get_attachment_metadata($attachment_id);

        // Die if we don't have a speech_asset_url
        if (!LBRY()->speech->is_published($image_meta)) {
            return $image;
        }

        $new_image = $image;
        $cropped_url = $this->get_speech_crop_url($image[0], $image_meta);
        $new_image[0] = $cropped_url;

        return $new_image;
    }

    /**
     * Scrape content for urls that have a speech equivalent and replace
     * @param  string   $content    The content to scrape
     * @return string               The content with Spee.ch urls replaced
     */
    // COMBAK: Need to make this a bit faster. Sitting between 100 and 300ms currently
    public function replace_urls_with_speech($content)
    {
        $new_content = $content;

        $assets = array();

        // Get Images
        $images = $this->scrape_images($content);
        foreach ($images as $image) {
            if ($src = $this->get_src_from_image_tag($image)) {
                $assets[] = $src;
            }
        }

        // Get Videos
        $videos = $this->scrape_videos($content);
        foreach ($videos as $video) {
            if ($src = $this->get_src_from_video_tag($video)) {
                $assets[] = $src;
            }
        }

        // Replace with Speech Urls
        foreach ($assets as $asset) {
            $id = $this->rigid_attachment_url_to_postid($asset);
            $meta = wp_get_attachment_metadata($id);


            if ($meta && LBRY()->speech->is_published($meta)) {
                // If its a video, handle accordingly
                if (!key_exists('file', $meta) && key_exists('mime_type', $meta) && $meta['mime_type'] == 'video/mp4') {
                    $speech_url = $meta[LBRY_SPEECH_ASSET_URL];
                } else {
                    $speech_url = $this->get_speech_crop_url($asset, $meta);
                }

                if ($speech_url) {
                    $new_content = str_replace($asset, $speech_url, $new_content);
                }
            }
        }
        return $new_content;
    }

    /**
     * Calculates the crop url for Spee.ch to deliver responsive images
     * @param  string $image_src  The src of the image that needs a spee.ch url
     * @param  array  $image_meta The meta for the attachment that needs a spee.ch url
     * @return string             The proper Spee.ch URL, false if none found
     */
    private function get_speech_crop_url($image_src, $image_meta)
    {
        $base_url = wp_get_upload_dir()['baseurl'];
        $image_file_info = pathinfo($image_meta['file']);

        // If this is the base image, just return it as is
        if ($image_file_info['basename'] == pathinfo($image_src)['basename']) {
            return $image_meta[LBRY_SPEECH_ASSET_URL];
        }

        // Otherwise, find the crop size
        $comparable_url = $base_url . '/' . $image_file_info['dirname'] . '/' . $image_file_info['filename'];
        $crop_size = str_replace($comparable_url, '', $image_src);
        if (preg_match('/-(\d+)x(\d+)\..+/', $crop_size, $matches)) {
            return $image_meta[LBRY_SPEECH_ASSET_URL] . '?w=' . $matches[1] . '&h=' . $matches[2] . '&t=crop';
        }

        return false;
    }

    /**
     * Scrapes all image tags from content
     * @param  string   $content    The content to scrape
     * @return array                Array of image tags, empty if none found
     */
    public function scrape_images($content)
    {
        // Return empty array if no images
        if (!$content) {
            return array();
        }
        // Find all images
        preg_match_all('/<img [^>]+>/', $content, $images);

        // Check to make sure we have results
        $images = empty($images[0]) ? array() : $images[0];

        return array_unique($images);
    }

    /**
     * Scrapes all video shortcodes from content
     * @param  string   $content    The content to scrape
     * @return array                Array of video tags, empty if none found
     */
    public function scrape_videos($content)
    {
        // Return empty array if no videos
        if (!$content) {
            return array();
        }

        // Only MP4 videos for now
        preg_match_all('/\[video.*mp4=".*".*\]/', $content, $videos);
        $videos = empty($videos[0]) ? array() : $videos[0];

        return array_unique($videos);
    }

    /**
     * Retrives an attachment ID via an html tag
     * @param  string $type     Can be 'image' or 'mp4'
     * @param  string $tag      The Tag / shortcode to scrub for an ID
     * @return int|bool         An ID if one is found, false otherwise
     */
    public function get_attachment_id_from_tag($type, $tag)
    {
        $attachment_id = false;
        switch ($type) {
            case 'image':
                // Looks for wp image class first, if not, pull id from source
                if (preg_match('/wp-image-([0-9]+)/i', $tag, $class_id)) {
                    $attachment_id = absint($class_id[1]);
                } elseif ($src = $this->get_src_from_image_tag($tag)) {
                    $attachment_id = $this->rigid_attachment_url_to_postid($src);
                }
                break;

            case 'mp4':
                if ($src = $this->get_src_from_video_tag($tag)) {
                    $attachment_id = $this->rigid_attachment_url_to_postid($src);
                }
                break;
        }
        return $attachment_id;
    }

    /**
     * Pulls src from image tag
     * @param  string   $image  HTML Image tag
     * @return string           The source if found and is a local source, otherwise false
     */
    private function get_src_from_image_tag($image)
    {
        if (preg_match('/src="((?:https?:)?\/\/[^"]+)"/', $image, $src) && $src[1]) {
            if ($this->is_local($src[1])) {
                return $src[1];
            }
        }

        return false;
    }

    /**
     * Pulls src from video tag
     * @param  string   $video  The video shortcode
     * @return string           The source if found and is a local source, otherwise false
     */
    private function get_src_from_video_tag($video)
    {
        if (preg_match('/mp4="((?:https?:)?\/\/[^"]+)"/', $video, $src) && $src[1]) {
            if ($this->is_local($src[1])) {
                return $src[1];
            }
        }

        return false;
    }

    /**
     * Checks to see if a url is local to this installation
     * @param  string   $url
     * @return boolean
     */
    private function is_local($url)
    {
        if (strpos($url, home_url()) !== false) {
            return true;
        }
    }

    /**
     * Checks for image crop sizes and filters out query params
     * Courtesy of this post: http://bordoni.me/get-attachment-id-by-image-url/
     * @param  string   $url    The url of the attachment you want an ID for
     * @return int              The found post_id
     */
    private function rigid_attachment_url_to_postid($url)
    {
        $scrubbed_url = strtok($url, '?'); // Clean up query params first
        $post_id = attachment_url_to_postid($scrubbed_url);

        if (! $post_id) {
            $dir = wp_upload_dir();
            $path = $scrubbed_url;

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
