<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */

class LBRY_Speech_Parser
{
    public function __construct()
    {
    }

    /**
     * [speech_image_srcset description]
     * @param [type] $sources       [description]
     * @param [type] $size_array    [description]
     * @param [type] $image_src     [description]
     * @param [type] $image_meta    [description]
     * @param [type] $attachment_id [description]
     */
    public function speech_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
    {
        $time_start = microtime(true);

        $new_sources = $sources;
        $sizes = $image_meta['sizes'];

        foreach ($sources as $width => $source) {
            $speech_url = $this->find_speech_url($sizes, $width);

            if ($speech_url) {
                $new_sources[$width]['url'] = $speech_url;
            }
        }

        $time_end = microtime(true);

        $time = ($time_end - $time_start) * 1000;
        error_log("srcset in $time milliseconds");
        return $new_sources;
    }

    public function replace_urls_with_speech($content)
    {
        // Find all images
        preg_match_all('/<img [^>]+>/', $content, $images);

        // Check to make sure we have results
        $images  = empty($images[0]) ? array() : $images[0];

        foreach ($images as $image) {
            $attachment_id = null;
            // Looks for wp image class first, if not, pull id from source
            if (preg_match('/wp-image-([0-9]+)/i', $image, $class_id)) {
                $attachment_id = absint($class_id[1]);
            } elseif (preg_match('/src="((?:https?:)?\/\/[^"]+)"/', $image, $src) && $this->is_local($src[1])) {
                $attachment_id = $this->rigid_attachment_url_to_postid($src[1]);
            }

            // Look for size class
            if (!preg_match('/wp-image-([0-9]+)/i', $image, $class_id)) {
            }
                if ($attachment_id) {
                    // Create main image media object
                    $meta = wp_get_attachment_metadata($attachment_id);

                    // If we don't have meta, get out because none of this will work
                    if (!$meta) {
                        break;
                    }

                    if (!$this->is_published($meta)) {
                        $all_media[] = new LBRY_Speech_Media($attachment_id);
                    }

                    // COMBAK: find a way to make this more efficient?
                    // Create a media object for each image size
                    // Get images sizes for this attachment, as not all image sizes implemented
                    $image_sizes = wp_get_attachment_metadata($attachment_id)['sizes'];

                    foreach ($image_sizes as $size => $meta) {
                        if (!$this->is_published($meta)) {
                            $all_media[] = new LBRY_Speech_Media($attachment_id, array('image_size' => $size));
                        }
                    }
                }
            }
        }
        error_log(print_r($content, true));

        return $content;
    }

    /**
     * [find_speech_url description]
     * @param  [type] $sizes [description]
     * @param  [type] $width [description]
     * @return [type]        [description]
     */
    private function find_speech_url($sizes, $width)
    {
        foreach ($sizes as $key => $size) {
            if ($size['width'] == $width && key_exists('speech_asset_url', $size)) {
                return $size['speech_asset_url'];
            }
        }

        return false;
    }



    private function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
