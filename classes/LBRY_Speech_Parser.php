<?php
/**
 * Parses post markup in order to use specified spee.ch url for assets
 *
 * @package LBRYPress
 */

class LBRY_Speech_Parser
{

    /**
     * Relative url
     * @param   string  $url a full url
     * @return  string  protocol relative url
     */
    protected function relative_url($url)
    {
        return substr($url, strpos($url, '//'));
    }

    public function rewrite($html)
    {
        // TODO: Completely fix this, as its super slow. Looking at cdn_enabler for ideas
        $speech_url = get_option(LBRY_SETTINGS)[LBRY_SPEECH];

        $html = str_replace(site_url(), $speech_url, $html);

        return $html;
    }
}
