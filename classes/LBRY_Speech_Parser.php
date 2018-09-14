<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */

class LBRY_Speech_Parser
{
    public function rewrite($html)
    {
        // TODO: Completely fix this, as its super slow. Looking at cdn_enabler for ideas
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        $html = str_replace(site_url(), $speech_url, $html);

        return $html;
    }
}
