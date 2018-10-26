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
        add_action('save_post', array($this, 'upload_attachments'));
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
    public function upload_attachments($post_id)
    {
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        // Die if we don't have a spee.ch url
        if (!$speech_url || $speech_url === '') {
            return false;
        }

        $attachments = $this->find_attachments($post_id);

        // IDEA: Notify user if post save time will take a while, may be a concern for request timeouts
        if ($attachments) {
            foreach ($attachments as $attachment) {
                error_log(print_r($attachment, true));
                // TODO: set post meta to see if already uploaded


                // Create a CURLFile object to pass our attachments to the spee.ch instance
                $file_url = get_attached_file($attachment->ID);
                $file_name = wp_basename($file_url);
                $file_type = $attachment->post_mime_type;
                $cfile = new CURLFile($file_url, $file_type, $file_name);

                $params = array(
                    'name' => $attachment->post_name,
                    'file' => $cfile,
                    'title' => $attachment->post_title,
                    'type' => $file_type
                );

                $result = $this->request('publish', $params);
                error_log(print_r($result, true));

                // TODO: Make sure to warn if image name is already taken on channel
            }
        }
    }

    /**
     * Finds all media attached to a post
     * @param  int $post_id     The post to search
     * @return array            An array of WP_Post Objects, or false if none found
     */
    protected function find_attachments($post_id)
    {
        // Get all attachments
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => 'any',
            'post_parent' => $post_id,
        ));

        // Return attachments arary
        if ($attachments) {
            return $attachments;
        } else {
            return false;
        }
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

        error_log(print_r($params, true));

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
