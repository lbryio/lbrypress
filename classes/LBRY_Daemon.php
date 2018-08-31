<?php
/**
 * Main class for the Daemon setup
 *
 * @package LBRYPress
 */

class LBRY_Daemon
{
    private static $instance = null;
    private $address = 'localhost:5279';

    public static function get_instance()
    {
        // Create the object
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Returns an unused wallet address
     * @return string Unused wallet address in base58
     */
    public function wallet_unused_address()
    {
        $result = json_decode($this->request('wallet_unused_address'));
        return $result->result;
    }

    /**
     * Returns the balance of a current LBRY wallet
     * @param  string $address Wallet Address
     * @return float           Wallet Balance
     */
    public function wallet_balance($address)
    {
        return $this->request('wallet_balance', array(
            'address' => $address,
            'include_unconfirmed' => false
        ));
    }

    private function request($method, $params = array())
    {
        // JSONify our request data
        $data = array(
            'method' => $method,
            'params' => $params
        );
        $data = json_encode($data);

        // Send it via curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->address);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
    * Temporary placeholder function for daemon. Not currently in use.
    * @return [type] [description]
    */
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
