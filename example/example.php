<?php 

require_once '../XBRLUSCall.php';

$xbrl = new XBRLUSCall();


// Returns fact information for one report 
$url_1 = "/api/v1/fact/search?fields=report.id,entity.id,fact.decimals,fact.value,unit,entity.cik,report.filing-date,concept.local-name," .
        "entity.cik,entity.name,concept.local-name,fact.ultimus-index,fact.id,dimensions,dimensions.count,period.fiscal-year.sort(DESC)," .
        "period.fiscal-period.sort(DESC)&report.id=243065"; 


// Same parameters as $url_1
$url_2 = "/api/v1/fact/search";
$parameter_array = array('fields' => 'report.id,entity.id,fact.decimals,fact.value,unit,entity.cik,report.filing-date,concept.local-name,' .
        'entity.cik,entity.name,concept.local-name,fact.ultimus-index,fact.id,dimensions,dimensions.count,period.fiscal-year.sort(DESC),' .
        'period.fiscal-period.sort(DESC),fact.limit(5)',
                         'report.id' => 243065,
                         'max_limit' => 10
);


/* Example Query 1
 *  
 *  Returns the information requested in one array.
 *  
 */

$response = $xbrl->call($url_1); 

echo "Query 1 response: \n" . print_r($response, true);

/* Example Query 2
*  
*  Returns the information requested, making multiple requests to the api, in batches of 5 returning a total of 10 facts ($max_limit = 10).  This example uses the $arguments
*   variable to submit the parameters of the query
*   
*/

$response = $xbrl->call($url_2, $parameter_array);

echo "Query 2 response: \n" . print_r($response, true);


?>
