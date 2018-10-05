<?php
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Parses wordpress posts to be ready for the LBRY Network
 * Uses the Html-to-Markdown package
 * https://github.com/thephpleague/html-to-markdown
 *
 * @package LBRYPress
 */

class LBRY_Network_Parser
{
    public $converter = null;

    public function __construct()
    {
        require_once LBRY_ABSPATH . 'vendor/autoload.php';
        $this->converter = new HtmlConverter();
    }
}
