<?php 
/*
 *  Wordpress example that extends the base XBRLUSCall and overrides certain functions to make the tokens
 *      persist between calls
 *      
 *  The configuration is overridden to pull basic information from Wordpresses options system.
 *  The access and refresh tokens are stored as session variables.
 *  
 *  See XBRLUSCall for valid overridable functions
 * 
 * 
 */
require_once 'XBRLUSCall.php';


class XBRLUSCallWP extends XBRLUSCall {
    
    /*
     * Function that returns configuration values needed for XBRLUSCall.  This function can be written to
     *   retrieve the values from any source.  The returned array must have the key values: base_url,
     *   client-id, client_secret, platform, username, password. NOTE: The platform should be unique each call.
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
        
        $options = get_option('wp_api_options');
        
        $config['base_url'] = $options['api_baseurl'];
        $config['client_id'] = $options['api_client'];
        $config['client_secret'] = $options['api_client_secret'];
        
        // Randomizes platform name in case calling script is used to call the api multiple times.  This makes
        //   sure the different instances don't run into issues when trying to refresh tokens
        $config['platform'] = $options['api_platform'] . mt_rand(10,99) . mt_rand(10,99);
        
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
        return $_SESSION['token'];
    }
    
    
    /*
     * OPTIONAL
     * Function that stores information to an external source (i.e. DB, file)
     *
     * @param $access_token string The access token to be stored
     */
    protected function __store_token($access_token) {
        $_SESSION['token'] = $access_token;
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
        return $_SESSION['refresh_token'];
    }
    
    
    /*
     * OPTIONAL
     * Function that stores information to an external source (i.e. DB, file)
     *
     * @param $refresh_token string The refresh token to be stored
     *
     */
    protected function __store_refresh($refresh_token) {
        $_SESSION['refresh_token'] = $refresh_token;
    }
    
    
    /*
     * Handles an error that occurs.  This version kills the script and reports the error.
     *   This function can be overridden.
     *
     * @param $error string The error that occurred
     *
     */
    protected function handle_error($error) {
        $error_response = "{'error': 'invalid_request','error_description': '$error'}";
        
        return $error_response;
    }
    
}



?>