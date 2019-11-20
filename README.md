## Introduction
XBRLUSCall simplifies the task of creating programatic requests in PHP to the XBRL US API.  

This class:
- Handles requesting and managing OAuth tokens (access_token and refresh_token)
- Handles multiple calls (pagination) to the API to obtain the full results of the data set rather than the per-request limit. 
- Automatically handles potential errors
- Certain functions can be overridden to allow token persistence between initializations.



## Initialization
To begin using this function you must set up a configuration file.  See the file [config.ini](config.ini) for an example.  The values in this file is explained in the XBRL API documentation.  There is an option to extend this class to allow it to retrieve this information from another location.  This will be covered later.

Import the class at the beginning of your file
```php
require_once 'XBRLUSCall.php';
```
Before requesting data, initialize the class:
```php
$xbrl = new XBRLUSCall();
```

## Use

Build a query according to the XBRL API docs.  You will build a url WITHOUT the url or domain.  Make the request which will be returned as an array.
```php
$url = '/api/v1/report/search?fields=report.*,report.limit(10),report.accepted-timestamp.sort(DESC)';
$response = $xbrl->call($url);
echo "Response is: \n" . print_r($response, true);
```

## Error Handling

This class will attempt to handle errors when they occur.  If they can't be addressed then it will return the error rather than the results.  After each request the results should be examined to make sure the array key "error" doesn't exist.

```php
$response = $xbrl->call($url);
if (array_key_exists('error', $response)) {
    echo "An error occurred: " . $response['error_description'];
}
```


## Notes
- The parameters in the request can be included either in the uri or as a parameter array.  See [example.php](example/example.php) for an example.
- By default the maximum number of results returned is 10,000.  This can be overridden by changing $max_num and submit it in $arguments.  This max may be affected by the type of account you have.  Also note that the more results that are requested, the longer the process will take.
- Limits defined in the parameter (ex. fact.limit(5)) will control how many are requested at a time.  The total amount returned by this class is defined by $max_limit.


## Alternate Locations for the configuration information

There are cases where it would be more convenient to credential information in a location other than the configuration file, for example, a database.  This can be handled by extending XBRLUSCall and overriding the function:
- __get_main_config.  

See [XBRLUSCallWP.php](XBRLUSCallWP.php) for an example.


## Persistent Tokens

There are also cases where multiple calls may need to be made to the API, but not all at the same time.  It would be beneficial to allow this function to save the access and refresh token and retrieve them when necessary.  This can be done by overriding the function:

- __get_token()
- __store_token()
- __get_refresh()
- __store_refresh()

These can be modified to retrieve the information from a database or any other form of persistent storage.  See the documentation comments in [XBRLUSCall.php](XBRLUSCall.php) and the example in [XBRLUSCallWP.php](XBRLUSCallWP.php).




