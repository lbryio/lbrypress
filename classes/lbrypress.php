<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */

if (! class_exists('LBRYPress')) {
    class LBRYPress
    {
        // Employ Singleton pattern to preserve single instance
        private static $instance = null;

        public static function get_instance()
        {
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        // Create instances of all necessary classes
        private $LBRY_Admin;

        private function __construct()
        {
            $this->require_dependencies();
            $this->LBRY_Admin = new LBRY_Admin();


            $this->LBRY_Admin->settings_init();

            $this->download_daemon();
        }

        private function require_dependencies()
        {
            require_once(LBRY_URI . '/classes/admin/lbry_admin.php');
        }

        private function download_daemon()
        {
            $output_filename = "lbrydaemon";

            $host = "http://build.lbry.io/daemon/build-6788_commit-5099e19_branch-lbryum-refactor/mac/lbrynet";
            $fp = fopen(LBRY_URI . '/' . $output_filename, 'w+');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_AUTOREFERER, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            $result = curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $filepath = LBRY_URI . '/' . $output_filename;


            `chmod +x  {$filepath}`;
            error_log(`{$filepath} status`);
            `{$filepath} start &`;
        }
    }
}
