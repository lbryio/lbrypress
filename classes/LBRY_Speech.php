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
}
