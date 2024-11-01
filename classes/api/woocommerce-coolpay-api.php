<?php
/**
 * WC_CoolPay_API class
 *
 * @class 		WC_CoolPay_API
 * @since		4.0.0
 * @package		Woocommerce_CoolPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        https://coolpay.com/docs/apidocs/
 */

class WC_CoolPay_API
{

    /**
     * Contains cURL instance
     * @access protected
     */
    protected $ch;


    /**
     * Contains the API url
     * @access protected
     */
    protected $api_url = 'https://api.coolpay.com/';


	/**
	 * Contains a resource data object
	 * @access protected
	 */
	protected $resource_data;

	/**
	 * @var null
	 */
    protected $api_key = NULL;

    /**
     * @var bool
     */
    public $block_callback = FALSE;


  	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/
    public function __construct( $api_key = NULL )
    {
        add_action('shutdown', array($this, 'shutdown'));

        if (empty($api_key)) {
            $this->api_key = WC_CP()->s( 'coolpay_apikey' );
        } else {
            $this->api_key = $api_key;
        }

        // Instantiate an empty object ready for population
        $this->resource_data = new stdClass();
    }


	/**
	 * is_authorized_callback function.
	 *
	 * Performs a check on payment callbacks to see if it is legal or spoofed
	 *
	 * @access public
	 *
	 * @param $response_body
	 *
	 * @return boolean
	 */
    public function is_authorized_callback( $response_body )
    {
        if( ! isset( $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"] ) )
        {
            return FALSE;
        }

        return hash_hmac( 'sha256', $response_body, WC_CP()->s( 'coolpay_privatekey' ) ) == $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"];
    }


	/**
	 * get function.
	 *
	 * Performs an API GET request
	 *
	 * @access public
	 *
	 * @param      $path
	 * @param bool $return_array
	 *
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
    public function get( $path, $return_array = FALSE )
    {
    	// Instantiate a new instance
        $this->remote_instance();

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('GET', $return_array);
    }


	/**
	 * post function.
	 *
	 * Performs an API POST request
	 *
	 * @access public
	 *
	 * @param       $path
	 * @param array $form
	 * @param bool  $return_array
	 *
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
    public function post( $path, $form = array(), $return_array = FALSE )
    {
    	// Instantiate a new instance
        $this->remote_instance( $this->get_post_id_from_form_object( $form ) );

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('POST', $form, $return_array);
    }


	/**
	 * put function.
	 *
	 * Performs an API PUT request
	 *
	 * @access public
	 *
	 * @param       $path
	 * @param array $form
	 * @param bool  $return_array
	 *
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
    public function put( $path, $form = array(), $return_array = FALSE )
    {
    	// Instantiate a new instance
        $this->remote_instance( $this->get_post_id_from_form_object( $form ) );

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('PUT', $form, $return_array);
    }

	/**
	 * put function.
	 *
	 * Performs an API PUT request
	 *
	 * @access public
	 * @return object
	 * @throws CoolPay_API_Exception
	 */
	public function patch( $path, $form = array(), $return_array = FALSE )
	{
		// Instantiate a new instance
		$this->remote_instance( $this->get_post_id_from_form_object( $form ) );

		// Set the request params
		$this->set_url( $path );

		// Start the request and return the response
		return $this->execute('PATCH', $form, $return_array);
	}


   	/**
	* execute function.
	*
	* Executes the API request
	*
	* @access public
	* @param  string $request_type
	* @param  array  $form
    * @param  boolean $return_array - if we want to retrieve an array with additional
	* @return object
	* @throws CoolPay_API_Exception
	*/
 	public function execute( $request_type, $form = array(), $return_array = FALSE )
 	{
 		// Set the HTTP request type
 		curl_setopt( $this->ch, CURLOPT_CUSTOMREQUEST, $request_type );

        // Prepare empty variable passed to any exception thrown
        $request_form_data = '';

 		// If additional data is delivered, we will send it along with the API request
 		if( is_array( $form ) && ! empty( $form ) )
 		{
            // Build a string query based on the form array values
            $request_form_data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query( $form, '', '&' ));

            // Prepare to post the data string
 			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $request_form_data );
 		}

 		// Execute the request and decode the response to JSON
 		$this->resource_data = json_decode( curl_exec( $this->ch ) );

 		// Retrieve the HTTP response code
 		$response_code = (int) curl_getinfo( $this->ch, CURLINFO_HTTP_CODE );
        $response_data = json_encode( $this->resource_data );
        $curl_request_url = curl_getinfo( $this->ch, CURLINFO_EFFECTIVE_URL);

 		// If the HTTP response code is higher than 299, the request failed.
 		// Throw an exception to handle the error
 		if( $response_code > 299 )
 		{
            if( isset($this->resource_data->errors) )
            {
                $error_messages = "";
                foreach( $this->resource_data->errors as $error_field => $error_descriptions )
                {
                    $error_messages .= "{$error_field}: ";

                    foreach( $error_descriptions as $error_description )
                    {
                        $error_messages .= "{$error_description}<br />\n";
                    }
                }

                throw new CoolPay_API_Exception( $error_messages, $response_code, NULL, $curl_request_url, $request_form_data, $response_data );
            }
            else if( isset( $this->resource_data->message) )
            {
                throw new CoolPay_API_Exception( $this->resource_data->message, $response_code, NULL, $curl_request_url, $request_form_data, $response_data );
            }
            else
            {
                throw new CoolPay_API_Exception( (string) json_encode($this->resource_data), $response_code, NULL, $curl_request_url, $request_form_data, $response_data );
            }

 		}

 		// Everything went well, return the resource data object.
        if( $return_array ) {
            return array(
                $this->resource_data,
                $curl_request_url,
                $request_form_data,
                $response_data,
                curl_getinfo( $this->ch ),
            );
        }
 		return $this->resource_data;
 	}


  	/**
	* set_url function.
	*
	* Takes an API request string and appends it to the API url
	*
	* @access public
	* @return void
	*/
    public function set_url( $params )
    {
        curl_setopt( $this->ch, CURLOPT_URL, $this->api_url . trim( $params, '/' ) );
    }


 	/**
	* remote_instance function.
	*
	* Create a cURL instance if none exists already
	*
	* @access public
	* @return cURL object
	*/
	protected function remote_instance( $post_id = NULL )
	{
		if( $this->ch === NULL )
		{
			$this->ch = curl_init();
			curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER , FALSE );
			curl_setopt( $this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt( $this->ch, CURLOPT_HTTPHEADER, array(
            	'Authorization: Basic ' . base64_encode(':' . $this->api_key ),
                'Accept-Version: v10',
                'Accept: application/json',
                'QuickPay-Callback-Url: ' . (!$this->block_callback) ? WC_CoolPay_Helper::get_callback_url( $post_id ) : NULL
            ));
		}

		return $this->ch;
	}


	/**
	* shutdown function.
	*
	* Closes the current cURL connection
	*
	* @access public
	* @return void
	*/
	public function shutdown()
	{
		if( ! empty( $this->ch ) )
		{
			curl_close( $this->ch );
		}
	}

    /**
     * Returns the POST ID if available in the form data object
     * @param  [type] $form_data [description]
     * @return [type]            [description]
     */
    public function get_post_id_from_form_object( $form_data ) {
        if( array_key_exists( 'order_post_id', $form_data ) ) {
            return $form_data['order_post_id'];
        }
        return NULL;
    }
}