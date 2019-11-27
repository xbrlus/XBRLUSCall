## Introduction
XBRLUSCall simplifies the task of creating programatic requests in PHP to the XBRL US API.  

This class:
- Handles requesting and managing OAuth tokens (access_token and refresh_token)
- Handles multiple calls (pagination) to the API to obtain the full results of the data set rather than the per-request limit. 
- Automatically handles potential errors
- Certain functions can be overridden to allow token persistence between initializations.



## Initialization
To use this function, set up a configuration file.  See the file [config.ini](config.ini) for an example.  The values in this file are explained in the [XBRL API documentation](https://github.com/xbrlus/xbrl-api).  Optionally, the class can be extended so configuration details can be [retrieved from an alternate location](#alternate-locations-for-the-configuration-information).

Import the class at the beginning of your file
```php 
require_once 'XBRLUSCall.php';
```
Before requesting data, initialize the class:
```php 
$xbrl = new XBRLUSCall();
```

## Use
Build a query according to the XBRL API documentation - the query **must not include the the url or domain** as these are handled in the configuration file.  The request will be returned as an array.
```php 
$url = '/api/v1/report/search?fields=report.*,report.limit(10),report.accepted-timestamp.sort(DESC)';
$response = $xbrl->call($url);
echo "Response is: \n" . print_r($response, true);
```  

### Notes
- The parameters in the request can be included either in the $url or as a parameter array.  See [example.php](example.php) for an example of each.
- The more results requested by a query, the longer the process will take, so use the following to optimize queries:
	- By default the maximum number of results returned is 10,000.  This can be overridden by changing **$max_num** and submit it in $arguments.  The maximum number of results may be limited by your [XBRL US Membership](https://xbrl.us/benefits).  
	- Limits defined in the parameter (ex. fact.limit(5)) will control how many are requested at a time.  The total amount returned by this class is defined by **$max_limit**.

## Error Handling
This class will attempt to handle errors when they occur.  If they can't be addressed then it will return the error rather than the results.  After each request the results should be examined to make sure the array key "error" doesn't exist.

```php 
$response = $xbrl->call($url);
if (array_key_exists('error', $response)) {
    echo "An error occurred: " . $response['error_description'];
}
```

## Alternate Locations for the configuration information
There are cases where it would be more convenient to store credential information in a location other than the configuration file, for example, a database.  This can be handled by extending XBRLUSCall and overriding the function:
```php 
__get_main_config
```  

See [XBRLUSCallWP.php](XBRLUSCallWP.php) for an example.

## Persistent Tokens
There are also cases where multiple calls may need to be made to the XBRL API, but not all at the same time.  To save the access and refresh token and retrieve them as necessary, override the function:

```php
__get_token()
__store_token()
__get_refresh()
__store_refresh()
```  

These can be modified to retrieve the information from a database or any other form of persistent storage.  See the documentation comments in [XBRLUSCall.php](XBRLUSCall.php) and the example in [XBRLUSCallWP.php](XBRLUSCallWP.php).




