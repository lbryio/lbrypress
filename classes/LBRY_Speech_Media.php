<?php
/**
 * Data container for necessary Speech api media uploaduploads
 *
 * Visit https://github.com/lbryio/spee.ch for more info
 *
 * @package LBRYPress
 */

class LBRY_Speech_Media
{
    public $name;

    public $file;

    public $type;

    public $nsfw;

    public $license;

    public $title;

    public $description;

    public $thumbnail;

    public function __construct($name, $file, $type, $nsfw = null, $license = null, $title = null, $description = null, $thumbnail = null)
    {
        $this->name         = $name;
        $this->file         = $file;
        $this->type         = $type;
        $this->nsfw         = $nsfw;
        $this->license      = $license;
        $this->title        = $title;
        $this->description  = $description;
        $this->thumbnail    = $thumbnail;
    }
}
