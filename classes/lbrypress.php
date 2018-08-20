<?php
/**
 * Main LBRYPress class
 *
 * @package LBRYPress
 */

if (! class_exists('LBRYPress')) {
    class LBRYPress
    {
        private $LBRY_Admin;

        public function __construct()
        {
            $this->requireDependencies();
            $this->LBRY_Admin = new LBRY_Admin();


            $this->LBRY_Admin->settings_init();
        }

        private function requireDependencies()
        {
            require_once(LBRY_URI . '/classes/admin/lbry_admin.php');
        }
    }
}
