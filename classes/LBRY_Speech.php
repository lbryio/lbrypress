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
        $this->parser = new LBRY_Speech_Parser();
        add_action('save_post', array($this, 'upload_media'), 10, 2);
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

        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        // Die if we don't have a spee.ch url
        if (!$speech_url || $speech_url === '') {
            return false;
        }

        $all_media = $this->find_media($post_id);

        // IDEA: Notify user if post save time will take a while, may be a concern for request timeouts
        if ($all_media) {
            foreach ($all_media as $media) {
                //// TODO: Check if media type is accepted
                // $meta = get_post_meta($media->id, '_wp_attachment_metadata', true);
                error_log(print_r($media, true));
                // if (! get_post_meta($media->id, 'lbry_speech_uploaded')) {
                //     $params = array(
                //         'name'  => $media->name,
                //         'file'  => $media->file,
                //         'title' => $media->title,
                //         'type'  => $media->type
                //     );
                //
                //     if (LBRY_SPEECH_CHANNEL && LBRY_SPEECH_CHANNEL_PASSWORD) {
                //         $params['channelName'] = LBRY_SPEECH_CHANNEL;
                //         $params['channelPassword'] = LBRY_SPEECH_CHANNEL_PASSWORD;
                //     }
                //
                //     $result = $this->request('publish', $params);
                //     error_log(print_r($result, true));
                //
                //     // TODO: Make sure to warn if image name is already taken on channel
                //     if ($result->success) {
                //         update_post_meta($media->id, 'lbry_speech_uploaded', true);
                //         update_post_meta($media->id, 'lbry_speech_url', $result->data->serveUrl);
                //     }
                // }
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
        // TODO: Check wp_make_content_images_responsive for cannon way to scrub images & attachments
        // https://developer.wordpress.org/reference/functions/wp_make_content_images_responsive/
        $all_media = array();

        // Get content and put into a DOMDocument
        $content = get_post_field('post_content', $post_id);
        if (!$content) {
            return $all_media;
        }

        preg_match_all('/<img [^>]+>/', $content, $images);

        // Only MP4 videos for now
        preg_match_all('/\[video.*mp4=".*".*\]/', $content, $videos);

        error_log(print_r($images, true));
        error_log(print_r($videos, true));

        // Throw each image into a media object
        foreach ($images as $image) {

            // Looks for wp image class first, if not, pull id from source
            if (preg_match('/wp-image-([0-9]+)/i', $image[0], $class_id)) {
                $attachment_id = absint($class_id[1]);
            } elseif (preg_match('/src="((?:https?:)?\/\/[^"]+)"/', $image[0], $src)) {
                $attachment_id = $this->rigid_attachment_url_to_postid($src[1]);
            }

            if ($attachment_id) {
                $all_media[] = new LBRY_Speech_Media($attachment_id, array(), true);
            }
        }

        // Parse video tags based on wordpress shortcode for local embedds
        foreach ($videos as $video) {
            if (preg_match('/mp4="((?:https?:)?\/\/[^"]+)"/', $video[0], $src)) {
                $attachment_id = $this->rigid_attachment_url_to_postid($src[1]);

                if ($attachment_id) {
                    $all_media[] = new LBRY_Speech_Media($attachment_id);
                }
            }
        }

        return $all_media;
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
        curl_close($ch);
        return json_decode($result);
    }
}
