# iformbuilder-record-deletion
A simple utility designed to loop through a number of forms and profiles, deleting all the records which match the field grammar criteria. You need to add one file to the **auth** folder (keys.php).

The keys.php file should look like the example below. The server name, client key and secret needs to be associated to a user which has **company admin** access if running for a single profile, or **server admin** access if running for multiple profiles.

The **$pageHostProfile** variable should be used if you are trying to delete records from shared forms, so the utility can print all the log information correctly. If you are not deleting records from shared forms, please keep the value set to empty string as shown in the example below.

You can use the `$fieldGrammar` parameter to supply a filter which records you need to delete. The filter will be reused for all the forms defined in the page array. Make sure to URLEncode the filter you pass in.

```php
<?php
//::::::::::::::  SET STATIC VARIABLES   ::::::::::::::
$server = '#####'; //apple.iformbuilder.com / support1.zerionsandbox.com
$client = '#####'; //abc123
$secret = '#####'; //abc123
$pageHostProfile = ''; // '123456' / '' if not used
$profileIdArray = ["#####", "#####"]; //123456
$pageArray = ["#####", "#####"]; // ["123456", "78910"];
$fieldGrammar = 'fields=id(!=%220%22)';
?>
