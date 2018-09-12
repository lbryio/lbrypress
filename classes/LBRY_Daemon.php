<?php
/**
 * Main class for the Daemon setup
 *
 * @package LBRYPress
 */

class LBRY_Daemon
{
    private $address = 'localhost:5279';

    /**
     * LBRY Daemon Object constructor
     */
    public function __construct()
    {
    }

    /**
     * Returns an unused wallet address
     * @return string Unused wallet address in base58
     */
    public function wallet_unused_address()
    {
        $result = $this->request('wallet_unused_address');
        return json_decode($result)->result;
    }

    /**
     * Returns the balance of a current LBRY wallet
     * @param  string $address Wallet Address
     * @return float           Wallet Balance
     */
    public function wallet_balance($address = '')
    {
        $address = $address ?? get_option(LBRY_WALLET);
        $result = $this->request('wallet_balance', array(
            'address' => $address,
            'include_unconfirmed' => false
        ));

        return json_decode($result)->result;
    }

    /**
     * https://lbryio.github.io/lbry/#channel_list
     * @return array claim dictionary
     */
    public function channel_list()
    {
        $result = $this->request('channel_list');
        error_log(print_r(json_decode($result), true));
        return null;
    }

    /**
     * https://lbryio.github.io/lbry/#channel_new
     * @return array dictionary containing result of the request
     */
    public function channel_new()
    {
    }

    /**
     * Sends a cURL request to the LBRY Daemon
     * @param  string $method The method to call on the LBRY API
     * @param  array  $params The Parameters to send the LBRY API Call
     * @return string The cURL response
     */
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
