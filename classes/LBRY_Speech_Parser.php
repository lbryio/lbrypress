<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */

class LBRY_Speech_Parser
{
    /**
     * Replace img srcset attributes with Spee.ch urls
     * Check https://developer.wordpress.org/reference/functions/wp_calculate_image_srcset/ hook for details
     */
    public function replace_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        $new_sources = $sources;
        $sizes = $image_meta['sizes'];
        $base_image = pathinfo($image_src)['basename'];

        foreach ($sources as $width => $source) {
            // Check to see if it is using base image first
            if ($image_src == $source['url'] && key_exists('speech_asset_url', $image_meta)) {
                $new_sources[$width]['url'] = $image_meta['speech_asset_url'];
                continue;
            }

            // Otherwise, find the corresponding size
            $speech_url = $this->find_speech_url_by_width($sizes, $width);

            if ($speech_url) {
                $new_sources[$width]['url'] = $speech_url;
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
        // $time_start = microtime(true);
        if (!$image) {
            return $image;
        }

        // Die if we don't have a speech_asset_url
        $image_meta = wp_get_attachment_metadata($attachment_id);
        if (!LBRY()->speech->is_published($image_meta)) {
            return $image;
        }

        $new_image = $image;
        $sizes = $image_meta['sizes'];

        // If we have a given size, then use that immediately
        if (is_string($size)) {
            switch ($size) {
                case 'full':
                $new_image[0] = $image_meta['speech_asset_url'];
                break;
                case 'post-thumbnail':
                if (LBRY()->speech->is_published($sizes['thumbnail'])) {
                    $new_image[0] = $sizes['thumbnail']['speech_asset_url'];
                }
                break;
                default:
                if (key_exists($size, $sizes) && LBRY()->speech->is_published($sizes[$size])) {
                    $new_image[0] = $sizes[$size]['speech_asset_url'];
                }
                break;
            }
            // $time_end = microtime(true);
            // $time = ($time_end - $time_start) * 1000;
            // error_log("attachment image source in $time milliseconds");
            return $new_image;
        }

        // Otherwise, we can find it by the url provided
        $speech_url = $this->find_speech_url_by_file_url($image_meta, $image[0]);
        if ($speech_url) {
            $new_image[0] = $speech_url;
        }

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
        // $time_start = microtime(true);
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

            if ($meta && LBRY()->speech->is_published($meta) && $speech_url = $this->find_speech_url_by_file_url($meta, $asset)) {
                $new_content = str_replace($asset, $speech_url, $new_content);
            }
        }

        // $time_end = microtime(true);
        // $time = ($time_end - $time_start) * 1000;
        // error_log("replace content urls in $time milliseconds");
        return $new_content;
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
     * Retrieves a speech_asset_url based sizes meta provided
     * @param  array    $sizes  Image sizes meta array
     * @param  string   $width  The width to look for
     * @return string           An asset url if found, false if not
     */
    private function find_speech_url_by_width($sizes, $width)
    {
        foreach ($sizes as $key => $size) {
            if ($size['width'] == $width && key_exists('speech_asset_url', $size)) {
                return $size['speech_asset_url'];
            }
        }

        return false;
    }

    /**
     * Retrieves a speech_asset_url based on a passed url
     * @param  array    $meta   The attachment meta data for the asset
     * @param  string   $url    The URL of the asset being exchanged
     * @return string           The URL of the Speech Asset
     */
    private function find_speech_url_by_file_url($meta, $url)
    {
        // See if this looks like video meta
        if (!key_exists('file', $meta) && key_exists('mime_type', $meta) && $meta['mime_type'] == 'video/mp4') {
            return $meta['speech_asset_url'];
        }

        $pathinfo = pathinfo($url);
        $basename = $pathinfo['basename'];

        // Check main file or if $meta is just a url (video) first
        if (key_exists('file', $meta) && $basename == wp_basename($meta['file'])) {
            return $meta['speech_asset_url'];
        }

        // Check to see if we have a meta option here
        if (key_exists('sizes', $meta)) {
            // Then check sizes
            foreach ($meta['sizes'] as $size => $meta) {
                if ($basename == $meta['file'] && key_exists('speech_asset_url', $meta)) {
                    return $meta['speech_asset_url'];
                }
            }
        }

        // Couldn't make a match
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
