<?php
/**
 * Main class for the Daemon setup
 *
 * @package LBRYPress
 */
defined('ABSPATH') || die(); // Exit if accessed directly

class LBRY_Daemon
{
    /**
     * The address of the Lbry Daemon
     * @var string
     */
    private $address = 'localhost:5279';

    /**
     * The Daemon Logger
     * @var LBRY_Daemon_Logger
     */
    public $logger = null;

    /**
     * LBRY Daemon Object constructor
     */
    public function __construct()
    {
        $this->logger = new LBRY_Daemon_Logger();
    }

    /**
     * Returns an unused address
     * https://lbry.tech/api/sdk#address_unused
     * @return string   Unused wallet address in base58
     */
    public function wallet_unused_address() {
        try {
            $result = $this->request( 'address_unused' );
            return $result->result;
        } catch ( LBRYDaemonException $e ) {
            $this->logger->log( 'address_unused error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            LBRY()->notice->set_notice( 'error', 'Issue getting unused address.' );
            return;
        }
    }

    /**
     * Returns an paginated array of Address lists
     * https://lbry.tech/api/sdk#address_list
     * @param  int      $page   Pagination page number
     * @return array    Array of address lists linked to this account
     */
    public function address_list( $page = 1 ) {
        // Get 20 per page
        $params = array(
            'page'  => $page,
            'page_size' => 20
        );
        try {
            $result = $this->request( 'address_list', $params );
            return $result->result->items;
        } catch ( LBRYDaemonException $e ) {
            $this->logger->log( 'address_list error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            LBRY()->notice->set_notice( 'error', 'Issue getting address list.' );
            return;
        }
    }

    /**
     * Returns the available balance of a current LBRY account
     * https://lbry.tech/api/sdk#wallet_balance
     * @param  string   $address           Wallet Address
     * @return object   $wallet_balance    Wallet Balance
     * 
     */
    public function wallet_balance()
    {
        
        try { // Convert JSON string to an object
            $result = $this->request( 'wallet_balance', array() );
            return $result;
        } catch (LBRYDaemonException $e) {
            $this->logger->log('wallet_balance error', $e->getMessage() . ' | Code: ' . $e->getCode());
            LBRY()->notice->set_notice('error', 'Issue getting wallet balance.');
            return;
        }
    }

    /**
     * Returns a list of channels for this account
     * https://lbry.tech/api/sdk#channel_list
     * @param  int      $page    Pagination page number
     * @return array    claim dictionary or null if empty
     */
    public function channel_list( $page = 1 )
    {
        $params = array(
            'page'      => $page,
            'page_size' => 20
        );

        try {
            $result = $this->request( 'channel_list', $params )->result->items;
            return empty( $result ) ? null : $result;
        } catch ( LBRYDaemonException $e ) {
            $this->logger->log( 'channel_list error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            LBRY()->notice->set_notice( 'error', 'Issue retrieving channel list.' );
            return;
        }
    }

    /**
     * Create a claim for a new channel
     * https://lbry.tech/api/sdk#channel_create
     * @return array    dictionary containing result of the request
     */

    public function channel_new( $channel_name, $channel_bid )
    {
        // TODO: Sanitize channel name and bid
        // Make sure no @ sign, as we will add that
        if ( strpos( $channel_name, '@' ) ) {
            throw new \Exception( 'Illegal character "@" in channel name', 1 );
        }
        
        // No white space allowed
        if ( strpos( $channel_name, ' ' ) ) {
            throw new \Exception( "No spaces allowed in channel name", 1 );
        }
 
        $channel_name = '@' . $channel_name;

        try {
            $result = $this->request(
                'channel_create',
                array(
                    'name' => $channel_name,
                    'bid'  => $channel_bid
                )
            );

            $this->logger->log( 'channel_create success!', 'Successfully created channel with result: ' . print_r( $result->result, true ) );
            return $result->result;
            
        } catch (LBRYDaemonException $e) {
            $this->logger->log( 'channel_new error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            throw new \Exception( 'Issue creating new channel.', 1 );
            return;
        }
    }

    /**
     * Returns the canonical URL for the supplied claim ID, null otherwise
     * @param  string $claim_id
     * @return string           Canonical URL, null if not found
     */
    public function canonical_url( $claim_id = null )
    {
        if ( ! $claim_id ) {
            return null;
        }

        try {
            $result = $this->request(
                'claim_search',
                array(
                    'claim_id'  => $claim_id,
                    'no_totals' => true
                )
            );

            $items = $result->result->items;
            if ( ! $items ) {
                return null;
            }

            return $items[0]->canonical_url;
        } catch ( LBRYDaemonException $e ) {
            $this->logger->log( 'canonical_url error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            return;
        }
    }

    /**
     * Publishes a post to the LBRY Network
     * https://lbry.tech/api/sdk#publish
     * @param  array  $args        An array containing necessary data for publish post
     *
     *      Available params:
     *      ['name', 'bid', 'file_path', 'title', 'description', 'language', 'license', 'channel_id', 'thumbnail']
     *
     * @return object $result
     */
    public function publish( $args )
    {
        try {
            $result = $this->request(
                'publish',
                $args
            );
            $this->logger->log( 'publish success!', 'Successfully published post with result: ' . print_r( $result->result, true ) );
            return $result->result;
        } catch ( LBRYDaemonException $e ) {
            $this->logger->log('publish error', $e->getMessage() . ' | Code: ' . $e->getCode() );
            LBRY()->notice->set_notice( 'error', 'Issue publishing / updating post to LBRY Network.' );
            return;
        }
    }

    /**
     * Sends a cURL request to the LBRY Daemon
     * @param  string   $method     The method to call on the LBRY API
     * @param  array    $params     The Parameters to send the LBRY API Call
     * @return string               The cURL response
     */
    private function request( $method, $params = array() ) 
    {
        // JSONify our request data
        $data = array(
            'method' => $method,
            'params' => $params
        );
        $data = json_encode( $data );

        // Send it via curl
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $this->address );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, false );
        curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );

        $result = curl_exec( $ch );
        $response_code = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
        curl_close( $ch );

        if ( $response_code != '200' ) {
            $this->logger->log( "Damon Connection Issue", "Daemon connection returned response code $response_code" );
            throw new LBRYDaemonException( "Daemon Connection Issue", $response_code );
        }


        $result = json_decode( $result );
        $this->check_for_errors( $result );
        return $result;
    }

    /**
     * Checks for errors in decoded daemon response and throws an exception if it finds one
     * @param  $response
     */
    private function check_for_errors( $response )
    {
        if ( property_exists( $response, 'error' ) ) {
            $message = $response->error->message;
            $code = $response->error->code;
            $this->logger->log( "Daemon error code $code", $message );
            throw new LBRYDaemonException( $message, $code );
        }
    }

    /**
    * Temporary placeholder function for daemon. Not currently in use.
    * @return [type] [description]
    */
    // private function download_daemon()
    // {
    //     $output_filename = "lbrydaemon";
    //
    //     // HACK: Shouldn't just directly download, need to know OS, etc
    //     // TODO: Make sure we are only installing if not there or corrupted
    //     $host = "http://build.lbry.io/daemon/build-6788_commit-5099e19_branch-lbryum-refactor/mac/lbrynet";
    //     $fp = fopen(LBRY_URI . '/' . $output_filename, 'w+');
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $host);
    //     curl_setopt($ch, CURLOPT_VERBOSE, 1);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_FILE, $fp);
    //     curl_setopt($ch, CURLOPT_AUTOREFERER, false);
    //     curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    //     curl_setopt($ch, CURLOPT_HEADER, 0);
    //
    //     $result = curl_exec($ch);
    //     curl_close($ch);
    //     fclose($fp);
    //
    //     $filepath = LBRY_URI . '/' . $output_filename;
    //
    //     `chmod +x  {$filepath}`;
    //     error_log(`{$filepath} status`);
    //     `{$filepath} start &`;
    // }
}

class LBRYDaemonException extends Exception
{
    public function __contstruct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
