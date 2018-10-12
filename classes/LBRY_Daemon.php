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
     * https://lbryio.github.io/lbry/#wallet_unused_address
     * @return string Unused wallet address in base58
     */
    public function wallet_unused_address()
    {
        $result = $this->request('wallet_unused_address');
        return $result->result;
    }

    /**
     * Returns the balance of a current LBRY wallet
     * https://lbryio.github.io/lbry/cli/#wallet_balance
     * @param  string $address Wallet Address
     * @return float           Wallet Balance
     */
    public function wallet_balance()
    {
        $result = $this->request('wallet_balance');
        return $result->result;
    }

    /**
     * https://lbryio.github.io/lbry/#channel_list
     * @return array claim dictionary or null if empty
     */
    public function channel_list()
    {
        $result = $this->request('channel_list')->result;
        return empty($result) ? null : $result;
    }

    /**
     * https://lbryio.github.io/lbry/#channel_new
     * @return array dictionary containing result of the request
     */
    public function channel_new($channel_name, $bid_amount)
    {
        // TODO: Sanitize channel name and bid

        // Make sure no @ sign, as we will add that
        if (strpos($channel_name, '@')) {
            throw new \Exception('Illegal character "@" in channel name', 1);
        }

        // No white space allowed
        if (strpos($channel_name, ' ')) {
            throw new \Exception("No spaces allowed in channel name", 1);
        }

        $channel_name = '@' . $channel_name;

        $result = $this->request(
            'channel_new',
            array(
                'channel_name' => $channel_name,
                'amount' => floatval($bid_amount)
            )
        );
        $this->check_for_errors($result);
        return $result->result;
    }

    /**
     * Publishes a post to the LBRY Network
     * @param  string $name        The slug for the post
     * @param  float  $bid         The amount of LBC to bid
     * @param  string $filepath    The path of the temporary content file
     * @param  string $title       The Title of the post
     * @param  string $description The Description of the Post
     * @param  string $language    Two letter ISO Code of the language
     * @return string $channel     The Claim ID of the Channel
     */
    public function publish($name, $bid, $filepath, $title, $description, $language, $channel)
    {
        $args = array(
            'name' => $name,
            'bid' => $bid,
            'file_path' => $filepath,
            'title' => $title,
            'description' => $description,
            'language' => $language,
        );

        // Make sure we aren't publishing to unattributed
        if ($channel != 'null') {
            $args['channel_id'] = $channel;
        }

        // TODO: Bring thumbnails into the mix
        $result = $this->request(
            'publish',
            $args
        );

        $this->check_for_errors($result);
        return $result;
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
        return json_decode($result);
    }

    /**
     * Checks for erros in decoded daemon response and throws an exception if it finds one
     * @param  $response
     */
    private function check_for_errors($response)
    {
        if (property_exists($response, 'error')) {
            throw new \Exception($response->error->message, $response->error->code);
        }
    }

    /**
    * Temporary placeholder function for daemon. Not currently in use.
    * @return [type] [description]
    */
    private function download_daemon()
    {
        $output_filename = "lbrydaemon";

        // HACK: Shouldn't just directly download, need to know OS, etc
        // TODO: Make sure we are only installing if not there or corrupted
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
