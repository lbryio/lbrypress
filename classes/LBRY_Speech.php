<?php
/**
 * Connects to an spee.ch style server to host assets via the LBRY Protocol
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

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

        if (is_admin()) {
            add_action('save_post', array($this, 'upload_media'), 10, 2);
        }

        // Replace the image srcsets
        add_filter('wp_calculate_image_srcset', array($this->parser, 'replace_image_srcset'), 10, 5);
        // Core filter for lots of image source calls
        add_filter('wp_get_attachment_image_src', array($this->parser, 'replace_attachment_image_src'), 10, 3);
        // Replace any left over urls with speech urls
        add_filter('the_content', array($this->parser, 'replace_urls_with_speech'));
    }

    /**
     * Uploads assets to the speech server
     * @param  int $post_id     The ID of the post to
     * @return bool             True if successful, false if not or if no Speech URL available
     */
    public function upload_media($post_id, $post)
    {
        // Only check post_type of Post
        if ('post' !== $post->post_type) {
            return false;
        }

        $speech_url = get_option(LBRY_SPEECH_SETTINGS)[LBRY_SPEECH];

        // Die if we don't have a spee.ch url
        if (!$speech_url || $speech_url === '') {
            return false;
        }

        $all_media = $this->find_media($post_id);

        // IDEA: Notify user if post save time will take a while
        if ($all_media) {
            $requests = array();

            // Build all the Curl Requests
            foreach ($all_media as $media) {
                $params = array(
                        'name'  => $media->name,
                        'file'  => $media->file,
                        'title' => $media->title,
                        'type'  => $media->type
                    );

                // Pull Channel and Password from config file for now
                $speech_channel = get_option(LBRY_SPEECH_SETTINGS)[LBRY_SPEECH_CHANNEL];
                $speech_pw = LBRY()->admin->get_speech_pw();
                if (!empty($speech_channel) && !empty($speech_pw)) {
                    $params['channelName'] = '@' . $speech_channel;
                    $params['channelPassword'] = $speech_pw;
                }

                $ch = $this->build_request('publish', $params);
                $requests[] = array(
                    'request' => $ch,
                    'media' => $media
                );
            }

            // Init the curl multi handle
            $mh = curl_multi_init();

            // Add each request to the multi handle
            foreach ($requests as $request) {
                curl_multi_add_handle($mh, $request['request']);
            }

            // Execute all requests simultaneously
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while ($running);

            // Close the handles
            foreach ($requests as $request) {
                curl_multi_remove_handle($mh, $request['request']);
            }
            curl_multi_close($mh);

            // Run through responses, and upload meta as necessary
            foreach ($requests as $request) {
                $result = json_decode(curl_multi_getcontent($request['request']));
                $media = $request['media'];
                $response_code = curl_getinfo($request['request'], CURLINFO_RESPONSE_CODE);

                try {
                    // check we got a success code
                    if ($response_code != '200') {
                        if (!empty($result) && !$result->success && $result->message) {
                            throw new \Exception("API Issue with message: " . $result->message);
                        } else {
                            throw new \Exception("Speech URL Connection Issue | Code: " . $response_code, 1);
                        }
                    }

                    // Update image meta
                    if ($result && $result->success) {
                        $meta = wp_get_attachment_metadata($media->id);
                        $meta[LBRY_SPEECH_ASSET_URL] = $result->data->serveUrl;
                        wp_update_attachment_metadata($media->id, $meta);
                    } else { // Something unhandled happened here
                        throw new \Exception("Unknown Speech Upload issue for asset");
                    }
                } catch (\Exception $e) {
                    error_log('Failed to upload asset with ID ' . $media->id . ' to supplied speech URL.');
                    LBRY()->daemon->logger->log('Speech Upload', 'Failed to upload asset with ID ' . $media->id . ' to supplied speech URL. Message | ' . $e->getMessage());
                    error_log($e->getMessage());
                }
            }
        }
    }

    /**
     * Finds all media attached to a post
     * @param  int $post_id     The post to search
     * @return array            An array of Speech Media Objects
     */
    private function find_media($post_id)
    {
        $all_media = array();

        $content = get_post_field('post_content', $post_id);

        $images = $this->parser->scrape_images($content);

        // Get all the image ID's
        $image_ids = array();
        foreach ($images as $image) {
            $new_id = $this->parser->get_attachment_id_from_tag('image', $image);
            if ($new_id) {
                $image_ids[] = $new_id;
            }
        }
        // Don't forget the featured image
        if ($featured_id = get_post_thumbnail_id($post_id)) {
            $image_ids = array_merge($image_ids, array($featured_id));
        }

        // Throw each image into a media object
        foreach ($image_ids as $attachment_id) {

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
            // $image_sizes = wp_get_attachment_metadata($attachment_id)['sizes'];
            //
            // foreach ($image_sizes as $size => $meta) {
            //     if (!$this->is_published($meta)) {
            //         $all_media[] = new LBRY_Speech_Media($attachment_id, array('image_size' => $size));
            //     }
            // }
        }

        $videos = $this->parser->scrape_videos($content);

        $video_ids = array();
        foreach ($videos as $video) {
            $new_id = $this->parser->get_attachment_id_from_tag('mp4', $video);
            if ($new_id) {
                $video_ids[] = $new_id;
            }
        }
        // Parse video tags based on wordpress shortcode for local embedds
        foreach ($video_ids as $attachment_id) {
            $meta = wp_get_attachment_metadata($attachment_id);

            if (!$this->is_published($meta)) {
                $all_media[] = new LBRY_Speech_Media($attachment_id);
            }
        }

        return $all_media;
    }

    /**
     * Checks meta to see if a spee.ch url exists
     * @param  array    $meta   An array of meta which would possibly contain a speech_asset_url
     * @return boolean          Whether or not its published to speech
     */
    public function is_published($meta)
    {
        if (key_exists(LBRY_SPEECH_ASSET_URL, $meta) && $meta[LBRY_SPEECH_ASSET_URL] !== '') {
            return true;
        }

        return false;
    }

    /**
     * Builds a cURL request to the Speech URL
     * @param  string   $method The method to call on the Speech API
     * @param  array    $params The Parameters to send the Speech API Call
     * @return string   The cURL object pointer
     */
    private function build_request($method, $params = array())
    {
        $speech_url = get_option(LBRY_SPEECH_SETTINGS)[LBRY_SPEECH];

        // Die if no URL
        if (!$speech_url) {
            return false;
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

        return $ch;
    }
}
