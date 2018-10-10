<?php

header('Access-Control-Allow-Methods: POST, PUT');
header("Access-Control-Allow-Headers: X-Requested-With");

require_once "../auth/iFormTokenResolver.php";
require_once "../auth/keys.php";
use iForm\Auth\iFormTokenResolver;

//:::::::::::::: Define the environment where we obtain an access token ::::::::::::::

$tokenUrl = 'https://' . $server . '.iformbuilder.com/exzact/api/oauth/token';

//:::::::::::::: Need to get the name of the active page so we can show the progress. ::::::::::::::

foreach($pageArray as $activePage) {

//::::::::::::::  FETCH ACCESS TOKEN   ::::::::::::::
// Couldn't wrap method call in PHP 5.3 so this has to become two separate variables
$tokenFetcher = new iFormTokenResolver($tokenUrl, $client, $secret);
$token = $tokenFetcher->getToken();

echo "Active Page ID: ".$activePage."\r\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://" . $server . ".iformbuilder.com/exzact/api/v60/profiles/$profileId/pages/$activePage");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Authorization: Bearer $token"
  ));

  $response = curl_exec($ch);
  if(curl_errno($ch))
      echo 'Curl error: '.curl_error($ch);
  curl_close($ch);

  //:::::::::::::: Print out the name and label of the form.
  $activePageJson = json_decode($response,true);
  $activePageLabel = $activePageJson["label"];
  $activePageName = $activePageJson["name"];
  echo "Active Page Label: ".$activePageLabel."\r\n";


  //:::::::::::::: Send one request to determine total number of records in the response header (Total-Count) ::::::::::::::
  $recordListUrl = "https://" . $server . ".iformbuilder.com/exzact/api/v60/profiles/$profileId/pages/$activePage/records?$fieldGrammar&limit=1&access_token=" . $token;

  // Parse the response headers and figure out how many records we need to process
  $recordRequestHeaders = (get_headers($recordListUrl,1));
  $finalRecordCount = $recordRequestHeaders["Total-Count"];

  echo("Number of Records:" . $finalRecordCount . "\r\n");

  // Determine how many times we have to iterate through the record list if over 1000
  $recordLimit=500;
  $timesToRun = $finalRecordCount/$recordLimit;
  $offset = 0;
  echo "Number of loops required: " . $timesToRun . "\r\n";

  for ($i=0;$i<$timesToRun;$i++)  {

    //::::::::::::::  FETCH ACCESS TOKEN   ::::::::::::::
    // Couldn't wrap method call in PHP 5.3 so this has to become two separate variables
    $tokenFetcher = new iFormTokenResolver($tokenUrl, $client, $secret);
    $token = $tokenFetcher->getToken();

    print_r($token."\r\n\r\n");

    //:::::::::::::: Delete the most recent list of records for the active form ::::::::::::::
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $server . ".iformbuilder.com/exzact/api/v60/profiles/$profileId/pages/$activePage/records?$fieldGrammar&limit=$recordLimit");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Bearer $token"
      ));

      $response = curl_exec($ch);
      print_r($response . "\r\n");
      if(curl_errno($ch))
          echo 'Curl error: '.curl_error($ch);
      curl_close($ch);

      // Add to the offset to keep working through the records not yet processed
      $offset=($i+1)*$recordLimit;
      echo "Records Completed: " . $offset . "\r\n\r\n";
    }
}
?>
