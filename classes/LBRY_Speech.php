<?php
/**
 * Connects to an spee.ch style server to host assets via the LBRY Protocol
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */

class LBRY_Speech
{
    /**
     * HTML Parser
     * @var LBRY_Speech_Parser
     */
    private $parser = null;

    public function __construct()
    {
        add_action('save_post', array($this, 'upload_media'), 10, 2);

        if (!is_admin()) {
            $this->parser = new LBRY_Speech_Parser();
            add_filter('wp_calculate_image_srcset', array($this->parser, 'speech_image_srcset'), 10, 5);
            add_filter('the_content', array($this->parser, 'replace_urls_with_speech'));
        }
    }

    /**
     * Checks to see if we need to rewrite URLS, does if necessary
     */

    public function maybe_rewrite_urls()
    {
        // See if we have a Spee.ch URL and if we are on the front-end
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];
        if ($speech_url != '' && !is_admin()) {
            ob_start(array($this->parser, 'rewrite'));
        }
    }

    /**
     * Uploads assets to the speech server
     * @param  int $post_id     The ID of the post to
     * @return bool             True if successful, false if not or if no Speech URL available
     */
    // TODO: set up error reporting
    public function upload_media($post_id, $post)
    {
        // Only check post_type of Post
        if ('post' !== $post->post_type) {
            return;
        }

        // error_log('======================== START =====================');

        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        // Die if we don't have a spee.ch url
        if (!$speech_url || $speech_url === '') {
            return false;
        }

        $all_media = $this->find_media($post_id);

        // IDEA: Notify user if post save time will take a while, may be a concern for request timeouts
        if ($all_media) {
            // error_log(print_r($all_media, true));
            foreach ($all_media as $media) {
                $params = array(
                        'name'  => $media->name,
                        'file'  => $media->file,
                        'title' => $media->title,
                        'type'  => $media->type
                    );

                // Pull Channel and Password from config file for now
                // COMBAK: This will change in the future
                if (LBRY_SPEECH_CHANNEL && LBRY_SPEECH_CHANNEL_PASSWORD) {
                    $params['channelName'] = LBRY_SPEECH_CHANNEL;
                    $params['channelPassword'] = LBRY_SPEECH_CHANNEL_PASSWORD;
                }

                try {
                    $result = $this->request('publish', $params);
                } catch (\Exception $e) {
                    error_log('Failed to upload asset with ID ' . $media->id . ' to supplied speech URL.');
                    error_log($e->getMessage());
                    continue;
                }

                $result = $this->request('publish', $params);

                // TODO: Handle if image is already taken on channel
                if ($result && $result->success) {
                    $meta = wp_get_attachment_metadata($media->id);
                    if ($media->image_size) {
                        $meta['sizes'][$media->image_size]['speech_asset_url'] =  $result->data->serveUrl;
                    } else {
                        $meta['speech_asset_url'] = $result->data->serveUrl;
                    }
                    wp_update_attachment_metadata($media->id, $meta);
                    error_log(print_r($meta, true));
                }
            }
        }
    }

    /**
     * Finds all media attached to a post
     * @param  int $post_id     The post to search
     * @return array            An array of Speech Media Objects
     */
    protected function find_media($post_id)
    {
        $all_media = array();


        // Get content and put into a DOMDocument
        $content = get_post_field('post_content', $post_id);
        if (!$content) {
            return $all_media;
        }

        // Find all images
        preg_match_all('/<img [^>]+>/', $content, $images);

        // Only MP4 videos for now
        preg_match_all('/\[video.*mp4=".*".*\]/', $content, $videos);

        // Check to make sure we have results
        $images  = empty($images[0]) ? array() : $images[0];
        $videos  = empty($videos[0]) ? array() : $videos[0];

        // error_log(print_r($images, true));
        // error_log(print_r($videos, true));

        // TODO: only create media objects if hasn't been uploaded. IE check meta here
        // Throw each image into a media object
        foreach ($images as $image) {
            $attachment_id = null;
            // Looks for wp image class first, if not, pull id from source
            if (preg_match('/wp-image-([0-9]+)/i', $image, $class_id)) {
                $attachment_id = absint($class_id[1]);
            // error_log('found with wp-image: ' . $attachment_id);
            } elseif (preg_match('/src="((?:https?:)?\/\/[^"]+)"/', $image, $src) && $this->is_local($src[1])) {
                $attachment_id = $this->rigid_attachment_url_to_postid($src[1]);
                // error_log('found with url: ' . $attachment_id);
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

        // Parse video tags based on wordpress shortcode for local embedds
        foreach ($videos as $video) {
            $attachment_id = null;
            if (preg_match('/mp4="((?:https?:)?\/\/[^"]+)"/', $video, $src) && $this->is_local($src[1])) {
                $attachment_id = $this->rigid_attachment_url_to_postid($src[1]);

                if ($attachment_id) {
                    $meta = wp_get_attachment_metadata($attachment_id);

                    if (!$this->is_published($meta)) {
                        $all_media[] = new LBRY_Speech_Media($attachment_id);
                    }
                }
            }
        }

        return $all_media;
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
     * Checks array to see if a spee.ch url exists
     */
    private function is_published($meta)
    {
        if (key_exists('speech_asset_url', $meta) && $meta['speech_asset_url'] !== '') {
            return true;
        }

        return false;
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

    /**
     * Sends a cURL request to the Speech URL
     * @param  string $method The method to call on the Speech API
     * @param  array  $params The Parameters to send the Speech API Call
     * @return string The cURL response
     */
    private function request($method, $params = array())
    {
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        // Die if no URL
        if (!$speech_url) {
            return;
        }

        $address = $speech_url . '/api/claim/' . $method;

        // Send it via curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $address);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $result = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($response_code != '200') {
            throw new \Exception("Speech URL Connection Issue | Code: " . $response_code, 1);
        }

        curl_close($ch);
        return json_decode($result);
    }
}
