<?php

class XBRLUSCall {
    /*
     * Class that handles the details of a call to the XBRL US API.  To use, include this class in the script, configure
     *   the config.ini file, then use the "call" function to obtain information information from the API.
     *   
     * If the configuration information needs to be pulled from a different source or the access/refresh tokens need to be 
     *   persistent between class calls certain functions can be overridden to obtain the information externally.  See comments 
     *   at the functions for details about what needs to be returned.
     *   
     * Overridable functions
     *   - protected function handle_error($error) - By defaults stops the program with the error string passed
     *   - protected function __get_main_config($username = null, $password = null) - obtains basic configuration data
     *   - protected function __get_token() - retrieves the access_token from persistent storage
     *   - protected function __store_token($access_token) - stores access_token in persistent storage
     *   - protected function __get_refresh() - retrieves the refresh_token from persistency storage
     *   - protected function __store_refresh($refresh_token) - stores the refresh_token in persistent storage
     *
     * Author: Marc Ward
     *
     */
    
    
    // Main Configuration information
    private $base_url;
    private $client_id;
    private $client_secret;
    private $platform;
    private $username;
    private $password;
    
    private $access_token;
    private $refresh_token;
    
    private $max_limit = 10000;
    private $offset_base = "";
    private $offset_num = 0;
    
    
    /* Initialized the class setting options from the configuration file and obtaining the
     *   access token and refresh token if necessary
     *
     *   @param $username string Username that is used for processing this call
     *   @param $password string Password of the user
     *
     */
    function __construct($username = null, $password = null) {
        // assign config variables with options of sending the username and password
        // should return a key/value array of the variables
        $config_params = $this->__get_main_config($username, $password);
        $diff_array = array_diff(array('base_url', 'client_id', 'client_secret', 'platform', 'username', 'password'), array_keys($config_params));
        
        if (count($diff_array) > 0) {
            $this->handle_error(sprintf("The following configuration parameters are missing in %s: %s.", $this->function_array['get_config'], implode(', ', $diff_array)));
        }
        
        $this->base_url = $config_params['base_url'];
        $this->client_id = $config_params['client_id'];
        $this->client_secret = $config_params['client_secret'];
        $this->platform = $config_params['platform'];
        $this->username = ($username != null ? $username : $config_params['username']);
        $this->password = ($password != null ? $password : $config_params['password']);
        
        if (is_null($this->get_info('access_token'))) {
            if ($this->username == null || $this->password == null) {
                $this->handle_error("ERROR: Username or password incorrect");
                return;
            }
            
            $this->login();
        } else {
            $this->access_token = $this->get_info('access_token');
            $this->refresh_token = $this->get_info('refresh_token');
        }
        
        if (is_null($this->access_token) || is_null($this->refresh_token)) {
            $this->handle_error("Unable to obtain the access or refresh token.");
        }
        
    }
    
    /*
     * Returns either access_token or the refresh_token from internal class variable.  If it hasn't
     *   been populated yet attempts to retrieve it from any defined external store.
     *
     * @param $infoname string (access_token|refresh_token) Indicates which token is needed
     * @returns $string Returns the requested token
     *
     */
    private function get_info($infoname) {
        $response = null;
        
        switch ($infoname) {
            case 'access_token':
                if ($this->access_token != null) {
                    $response = $this->access_token;
                } else {
                    $response = $this->__get_token();
                }
                break;
            case 'refresh_token':
                if ($this->refresh_token != null) {
                    $response = $this->refresh_token;
                } else {
                    $response = $this->__get_refresh();
                }
                break;
        }
        
        return $response;
        
    }
    
    /*
     * Uses input array and stores them in class variables.  If defined, will store them in
     *   an external store.
     *
     *   @param $info array Name value array consisting of any combination of 'access_token' and
     *     'refresh_token'
     *
     */
    private function store_info($info) {
        if (array_key_exists('access_token', $info)) {
            $this->access_token = $info['access_token'];
            $this->__store_token($this->access_token);
        }
        if (array_key_exists('refresh_token', $info)) {
            $this->refresh_token = $info['refresh_token'];
            $this->__store_refresh($this->refresh_token);
        }
    }
    
    
    /*
     * Function that handles logging in and retrieval the AUTH information
     *
     *   @param $username string Username that is used for processing this call
     *   @param $password string Password of the user
     *
     */
    private function login() {
        $url = "/oauth2/token";
        
        if (is_null($this->username) || is_null($this->password)) {
            handle_error("Unable to obtain token due to missing username and/or password.");
        }
        
        $arguments = array (
            'grant_type' => 'password',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'username' => $this->username,
            'password' => $this->password,
            'platform' => $this->platform
        );
        
        $output = $this->call ( $url, $arguments, false, 'POST');
        
        if (!array_key_exists ( "error", $output )) {
            $this->store_info(array(
                'access_token' => $output['access_token'],
                'refresh_token' => $output['refresh_token']
            ));
        } else {
            $this->handle_error(sprintf('Error during token refresh: %s', $output['error_description']));
        }
        
    }
    
    /*
     * If the access_token expires this function is called to use the refresh_token
     *   to obtain new tokens
     *
     *
     */
    private function refresh_tokens() {
        if (isset ( $this->refresh_token )) {
            $arguments = array (
            'grant_type' => 'refresh_token',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
            'platform' => $this->platform
            );
            $output = $this->call ( '/oauth2/token', $arguments, false, 'POST');
            
            if (!array_key_exists ( "error", $output )) {
                $this->store_info(array(
                'access_token' => $output['access_token'],
                'refresh_token' => $output['refresh_token']
                ));
            } else {
                $this->handle_error(sprintf('Error during token refresh: %s', $output['error_description']));
            }
        }
    }
    
    
    /**
     *
     * Function that handles the cURL request.  It parses the request, handles errors and returns the
     *   results as an array.
     * If the amount of returned data exceeds the limit for one call, this makes multiple calls until 
     *   either the full data set is retrieved or the max_limit is reached.  max_limit stops the retrieval
     *   of information. It is possible for this limit to be exceeded by a batch.  max_limit can be overridden
     *   by passing a new number in the $arguments array parameter.  
     *   NOTE: To limit the response of the query to a specific number you need to use the ".limit(X)" parameter in 
     *     the field state AND set " 'max_limit' => X " in the call statement
     *   WARNING: Using overly large numbers will slow down the total time of request or may include too much data 
     *     within the response. 
     *
     *
     * @param $url -
     *        	The URL route to use. Only include the path.
     * @param string $type
     *        	- GET, POST, PUT, DELETE. Defaults to GET.
     * @param array $arguments
     *        	- Parameters that could be included in the query
     * @param boolean $encodeData
     *        	- Whether or not to JSON encode the data.
     * @param boolean $returnHeaders
     *        	- Whether or not to return the headers.
     * @return mixed data from the request either as an array or json
     *
     */
    function call($url, $arguments = array(), $encodeData = false, $type = 'GET', $returnHeaders = false) {
        $count = 1;
        
        $this->offset_base = substr($url, 8, stripos($url,'/', 8)-8);
        
        if ($url == null || $url == '') {
            handle_error("No Url Defined");
        }
        
        if (array_key_exists('max_limit', $arguments)) {
            $this->max_limit = $arguments['max_limit'];
            unset($arguments['max_limit']);
        }
        
        $moreinfo = True;
        $records = array();
        $result = array();
        $full_result = array();
        $num_requests = 0;  // number of requests to fulfill data requirements
        
        
        while ($moreinfo) {
        
            $process = true;
            while ( $process ) {
                // Adds base_url if not defined (should be defined in config file
                $a = strpos ( $url, 'http' );
                if (strpos ( $url, 'http' ) === false) {
                    if ($url [0] != "/")
                        $url = "/" . $url;
                        $url = $this->base_url . $url;
                }
                
                $curl_request = curl_init ();
                
                // process options
                $headerarray = array ();
                if (!empty ( $this->get_info('access_token')
                        && !array_key_exists('grant_type', $arguments))) {
                            $ac_token = $this->get_info('access_token');
                            array_push ( $headerarray, "Authorization: Bearer {$ac_token}" );
                        }
                        
                        $type = strtoupper ( $type );
                        if ($type == 'GET' && ! empty ( $arguments )) {
                            if (strpos ( $url, '?' ) !== FALSE) {
                                $url .= "&" . http_build_query ( $arguments );
                            } else {
                                $url .= "?" . http_build_query ( $arguments );
                            }
                        }
                        
                        if ($type == 'POST') {
                            curl_setopt ( $curl_request, CURLOPT_POST, 1 );
                        } elseif ($type == 'PUT') {
                            curl_setopt ( $curl_request, CURLOPT_CUSTOMREQUEST, "PUT" );
                        } elseif ($type == 'DELETE') {
                            curl_setopt ( $curl_request, CURLOPT_CUSTOMREQUEST, "DELETE" );
                        }
                        
                        if (! empty ( $arguments ) && $type !== 'GET') {
                            if ($encodeData) {
                                // encode the arguments as JSON
                                $arguments = json_encode ( $arguments );
                                array_push ( $headerarray, "Content-type: application/json" );
                            }
                            curl_setopt ( $curl_request, CURLOPT_POSTFIELDS, $arguments );
                        }
                        
                        curl_setopt ( $curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
                        curl_setopt ( $curl_request, CURLOPT_HEADER, $returnHeaders );
                        curl_setopt ( $curl_request, CURLOPT_SSL_VERIFYPEER, 0 );
                        curl_setopt ( $curl_request, CURLOPT_RETURNTRANSFER, 1 );
                        curl_setopt ( $curl_request, CURLOPT_FOLLOWLOCATION, 0 );
                        
                        
                        $modded_url = $url;
                        if ($this->offset_base != "" && array_key_exists('paging', $full_result)) {
                            // Parse the url to add information if multiple requests are necessary
                            
                            $parsed = parse_url($url);
                            parse_str($parsed['query'], $parsed_query);
                            $parsed_query['fields'] = $parsed_query['fields'] . ",$this->offset_base.offset(" . ($full_result['paging']['count'] + $this->offset_num) . ")";
                            $modded_url = explode("?", $url)[0] . "?" . http_build_query($parsed_query); //['fields'];
                        }
                        
                        curl_setopt ( $curl_request, CURLOPT_URL, $modded_url );
                        curl_setopt ( $curl_request, CURLOPT_HTTPHEADER, $headerarray );
                        
                        $result = curl_exec ( $curl_request );
                        
                        // return headers
                        if ($returnHeaders) {
                            // set headers from response
                            list ( $headers, $content ) = explode ( "\r\n\r\n", $result, 2 );
                            foreach ( explode ( "\r\n", $headers ) as $header ) {
                                header ( $header );
                            }
                            
                            // return the nonheader data
                            return trim ( $content );
                        }
                        
                        curl_close ( $curl_request );
                        
                        // decode the response from JSON
                        $result = json_decode ( $result, true );
                        
                        // Handle known errors.  
                        if (array_key_exists ( "error", $result )) {
                            switch ($result ['error_description']) {
                                case "Bad or expired token" :
                                    $this->refresh_tokens ();
                                    $count ++;
                                    break;
                                case "Invalid refresh token":
                                    $this->store_info(array('access_token' => '', 'refresh_token' => ''));
                                    $this->login();
                                    $count++;
                                    break;
                                default :
                                    $count ++;
                                    break;
                            }
                        } else {
                            $process = false;
                        }
                        
                        // limits times it can rerequest a token
                        if ($count > 2) {
                            $process = false;
                        }
            }
            
            
            if (array_key_exists('paging', $result)) {
                // Builds final array to be returned
                if ($num_requests > 0) {
                    $full_result['paging']['count'] = $full_result['paging']['count'] + $result['paging']['count'];
                    $full_result['paging']['offset'] = -1;
                    $full_result['data'] = array_merge($full_result['data'], $result['data']);
                } else {
                    $full_result = $result;
                    $this->offset_num = $result['paging']['offset'];
                }
                
                // Determines if another request should occur
                if ($result['paging']['count'] >= $result['paging']['limit']) {
                    $num_requests++;
                } else {
                    $moreinfo = False;
                }
                
                if ($result['paging']['count'] <= 0) {
                    $moreinfo = False;
                }
                
                // Prevent request from exceeding max_limit
                if ($full_result['paging']['count'] >= $this->max_limit) {
                    $moreinfo = False;
                }
            } else {
                $full_result = $result;
                $moreinfo = False;
            }
        }
        
        if ($encodeData) {
            return json_encode ( $full_result );
        } else {
            return $full_result;
        }
    }
    
    
    // Functions that can be overridden
    
    
    /*
     * Handles an error that occurs.  This version kills the script and reports the error.
     *   This function can be overridden.
     * 
     * @param $error string The error that occurred
     *
     */
    protected function handle_error($error) {
        die("ERROR: " . $error);
    }
    
    
    /*
     * Function that returns configuration values needed for XBRLUSCall.  This function can be written to
     *   retrieve the values from any source.  The returned array must have the key values: base_url,
     *   client-id, client_secret, platform, username, password. NOTE: The platform should be unique 
     *   each time XBRLUSCall is initialized.
     *
     * This function returns information from the configuration file
     *
     * The following parameters are optional.  They can be set by passed values during XBRLUSCall initialization
     *   or retrieved from another source
     *   @param [Optional] $username string Username that is used for processing this call
     *   @param [Optional] $password string Password of the user
     *
     *   @return array (Name/value array with the keys specified above
     */
    protected function __get_main_config($username = null, $password = null) {
        
        $config = array();
        $config['username'] = null;
        $config['password'] = null;
        
        $configini = parse_ini_file('config.ini', true)
        or die ('Cannot load configuration file.');
        $api_config = $configini['api'];
        
        $config['base_url'] = $api_config['api_base_url'];
        $config['client_id'] = $api_config['api_client'];
        $config['client_secret'] = $api_config['api_client_secret'];
        
        // Randomizes platform name in case calling script is used to call the api multiple times.  This makes
        //   sure the different instances don't run into issues when trying to refresh tokens
        $config['platform'] = $api_config['api_platform'] . mt_rand(10,99) . mt_rand(10,99);
        
        if ($username == null) {
            $config['username'] = $api_config['api_username'];
        }
        
        if ($password == null) {
            $config['password'] = $api_config['api_password'];
        }
        
        return $config;
        
    }
    
    /*
     * OPTIONAL
     * Function that retrieves information from an external source (i.e. DB, file)
     *   and returns it.  Returns null by default.
     *
     * @returns string The access_token or null
     *
     */
    protected function __get_token() {
        return null;
    }
    
    
    /*
     * OPTIONAL
     * Function that stores information to an external source (i.e. DB, file)
     *
     * @param $access_token string The access token to be stored
     */
    protected function __store_token($access_token) {
        
    }
    
    
    /*
     * OPTIONAL
     * Function that retrieves information from an external source (i.e. DB, file)
     *   and returns it.  Returns null by default.
     *
     * @returns string The refresh_token or null
     *
     */
    protected function __get_refresh() {
        return null;
    }
    
    
    /*
     * OPTIONAL
     * Function that stores information to an external source (i.e. DB, file)
     *
     * @param $refresh_token string The refresh token to be stored
     *
     */
    protected function __store_refresh($refresh_token) {
    }
    
}
