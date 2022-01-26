<?php
/**
 * A class for logging LBRY Daemon interactions
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Daemon_Logger
{
    
    /**
     * The directory to log to
     * @var string
     */
    private $dir = LBRY_ABSPATH . 'logs/';

    /**
     * The filename to log to
     * @var string
     */
    private $filename;

    /**
     * The file ext
     * @var string
     */
    private $ext = '.log';

    /**
     * Max size of the file before rotating
     * @var int
     */
    private $maxsize = 15728640; // 15MB;

    /**
     * @param string $filename unique name for this logger to log to
     */
    public function __construct($filename = 'daemon')
    {
        $this->filename = $filename;
    }

    /**
     * Log to a file, with file size maximum. Adds hashs to old files
     *
     * @param string $event
     * @param string $text
     */
    public function log($event, $message = null)
    {
        $filepath = $this->dir . $this->filename . $this->ext;

        // If our file is past our size limit, stash it
        if (file_exists($filepath) && filesize($filepath) > $this->maxsize) {
            rename($filepath, $this->dir . $this->filename . time() . $this->ext);

            $logfiles = scandir($this->dir);

            // If we have to many files, delete the oldest one
            if (count($logfiles) > 7) {
                $oldest = PHP_INT_MAX;
                foreach ($logfiles as $file) {
                    if (!strstr($file, $this->filename)) {
                        continue;
                    }

                    $stamp = substr($file, strlen($this->filename));
                    $stamp = str_replace($this->ext, '', $stamp);
                    if ($stamp && $stamp < $oldest) {
                        $oldest = $stamp;
                    }
                }

                foreach ($logfiles as $file) {
                    if (strstr($file, $this->filename) && strstr($file, $oldest)) {
                        unlink($this->dir . $file);
                        break;
                    }
                }
            }
        }

        $date  = date('Y-m-d H:i:s');
        $space = str_repeat(' ', strlen($date) + 1);
        $data = date('Y-m-d H:i:s') . " ";
        $data .= "EVENT: " . $event . PHP_EOL;
        $data .= $space . "MESSAGE: " . $message . PHP_EOL;
        file_put_contents($filepath, $data, FILE_APPEND);
    }
}
