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

    private function find_speech_url($sizes, $width)
    {
        foreach ($sizes as $key => $size) {
            if ($size['width'] == $width && key_exists('speech_asset_url', $size)) {
                return $size['speech_asset_url'];
            }
        }

        return false;
    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
