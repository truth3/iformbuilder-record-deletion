<?php

header('Access-Control-Allow-Methods: POST, PUT');
header("Access-Control-Allow-Headers: X-Requested-With");

require_once "../auth/iFormTokenResolver.php";
require_once "../auth/keys.php";
use iForm\Auth\iFormTokenResolver;

//:::::::::::::: Define the environment where we obtain an access token ::::::::::::::

$tokenUrl = 'https://' . $server . '/exzact/api/oauth/token';
$time_start = microtime(true);
$totalRecordCount = 0;
$totalProfileCount = sizeof($profileIdArray);
$totalPageCount = sizeof($pageArray)*$totalProfileCount;

//:::::::::::::: Need to get the name of the active page so we can show the progress. ::::::::::::::
foreach($profileIdArray as $activeProfile) {

echo "Active Profile ID: ".$activeProfile."\r\n";

  foreach($pageArray as $activePage) {

  //::::::::::::::  FETCH ACCESS TOKEN   ::::::::::::::
  // Couldn't wrap method call in PHP 5.3 so this has to become two separate variables
  $tokenFetcher = new iFormTokenResolver($tokenUrl, $client, $secret);
  $token = $tokenFetcher->getToken();

  echo "Active Page ID: ".$activePage."\r\n";

  if ($pageHostProfile!="") {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $server . "/exzact/api/v60/profiles/$pageHostProfile/pages/$activePage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Bearer $token"
      ));  }

      else if ($pageHostProfile =="") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $server . "/exzact/api/v60/profiles/$activeProfile/pages/$activePage");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Authorization: Bearer $token"
          ));  }

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
    $recordListUrl = "https://" . $server . "/exzact/api/v60/profiles/$activeProfile/pages/$activePage/records?$fieldGrammar&limit=1&access_token=" . $token;

    // Parse the response headers and figure out how many records we need to process
    $recordRequestHeaders = (get_headers($recordListUrl,1));

    if (array_key_exists("Total-Count",$recordRequestHeaders)){
      $finalRecordCount = $recordRequestHeaders["Total-Count"];
    } else {
      echo "Bad Request (Please verify Profile ID and PageID values)\r\n";
      $finalRecordCount = 0;
    }

    echo("Number of Records: " . $finalRecordCount . "\r\n");

    // Determine how many times we have to iterate through the record list if over 1000
    $recordLimit=1000;
    $timesToRun = $finalRecordCount/$recordLimit;
    $offset = 0;
    echo "Number of loops required: " . $timesToRun . "\r\n\r\n";

    for ($i=0;$i<$timesToRun;$i++)  {

      //::::::::::::::  FETCH ACCESS TOKEN   ::::::::::::::
      // Couldn't wrap method call in PHP 5.3 so this has to become two separate variables
      $tokenFetcher = new iFormTokenResolver($tokenUrl, $client, $secret);
      $token = $tokenFetcher->getToken();

      print_r($token."\r\n\r\n");

      //:::::::::::::: Delete the most recent list of records for the active form ::::::::::::::
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://" . $server . "/exzact/api/v60/profiles/$activeProfile/pages/$activePage/records?$fieldGrammar&limit=$recordLimit");
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
        echo "Records Completed: " . (count(json_decode($response)) + $offset) . "\r\n\r\n";
        $offset=($i+1)*$recordLimit;
      }
      // Track the total number of Records we delete accross all profiles and forms.
      $totalRecordCount = $totalRecordCount+$finalRecordCount;
    }
}

$time_end = microtime(true);
//dividing with 60 will give the execution time in minutes otherwise seconds
$execution_time = ($time_end - $time_start)/60;

echo "Total number of profiles with deleted records: " . $totalProfileCount . "\r\n";
echo "Total number of forms with deleted records accross all profiles: " . $totalPageCount . "\r\n";
echo "Total number of records deleted accross all profiles and all forms: " . $totalRecordCount . "\r\n";
echo "This tool just saved about " . round($execution_time) . " minutes\r\n\r\n";
?>
