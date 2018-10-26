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
        add_action('save_post', array($this, 'upload_media'));
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
    public function upload_media($post_id)
    {
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        // Die if we don't have a spee.ch url
        if (!$speech_url || $speech_url === '') {
            return false;
        }

        $all_media = $this->find_media($post_id);

        // IDEA: Notify user if post save time will take a while, may be a concern for request timeouts
        if ($all_media) {
            error_log(print_r($all_media, true));
            foreach ($all_media as $media) {
                // TODO: set post meta to see if already uploaded

                $params = array(
                    'name'  => $media->name,
                    'file'  => $media->file,
                    'title' => $media->title,
                    'type'  => $media->type
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
     * @return array            An array of Speech Media Objects
     */
    protected function find_media($post_id)
    {
        $all_media = array();

        // Get content and put into a DOMDocument
        $content = apply_filters('the_content', get_post_field('post_content', $post_id));
        $DOM = new DOMDocument();
        // Hide HTML5 Tag warnings
        libxml_use_internal_errors(true);
        $DOM->loadHTML($content);

        $images = $DOM->getElementsByTagName('img');
        $videos = $DOM->getElementsByTagName('video');

        // Get each image attribute
        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            if ($this->is_local($src)) {
                $all_media[] = new LBRY_Speech_Media($src);
            }
        }

        // Parse video tags based on wordpress output for local embedds
        // Because video tag is HTML5, treat it like an XML node
        foreach ($videos as $video) {
            $source = $video->getElementsByTagName('source');
            $src = $source[0]->attributes->getNamedItem('src')->value;
            if ($this->is_local($src)) {
                $all_media[] = new LBRY_Speech_Media($src);
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

        return false;
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
