# iformbuilder-record-deletion
A simple utility designed to loop through a number of forms, deleting all the records which match the field grammar criteria. You need to add one file to the **auth** folder (keys.php).

The keys.php file should look like the example below. The server name, client key and secret needs to be associated to a user which has read access for the list of forms.

You can use the `$fieldGrammar` parameter to supply a filter which records you need to delete. The filter will be reused for all the forms defined in the page array. Make sure to URLEncode the filter you pass in.

```php
<?php
//::::::::::::::  SET STATIC VARIABLES   ::::::::::::::
$server = '#####'; //apple
$client = '#####'; //abc123
$secret = '#####'; //abc123
$profileId = '#####'; //123456
$pageArray = ["#####",["#####"]];
$fieldGrammar = 'fields=id(!="0")';
?>
