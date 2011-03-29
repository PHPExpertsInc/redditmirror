<?php

// Awesome! multiple colors FTW!
/* here's the table creation sql:
Actually, i already have the domains. I'm just going to add another field for isAlive

ALTER TABLE CachedDomains ADD isAlive boolean;
*/ 
// Now i need a function to detect whether the site is alive or not.
// To determine this, we're going to use the CURL extension, which is useful
// for all sorts of file transfer needs.
define('HTTP_TIMEOUT', isset($argv[2]) ? $argv[2] : 2);

function isSiteAlive($domain)
{
    $url = "http://$domain/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // This has it return the data instead of just true/false.
    curl_setopt($ch, CURLOPT_TIMEOUT, HTTP_TIMEOUT); // Let's only wait 2 seconds before we determine it's dead.
    
    curl_exec($ch);
    
    /* This gets the HTTP response headers; we want 200 or 300 for success */
    $http_response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //if ($http_response >= 200 && $http_response < 300)
    if ($http_response != 0)
    {
        return true;
    }
    else
    {
        echo "($http_response) ";
        return false;
    }
}

// Accept user input for the domain prefix:
$domainPrefix = isset($argv[1]) ? $argv[1] : filter_input(INPUT_GET, 'prefix', FILTER_SANITIZE_STRING);

require '.dbcreds';

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$stmt = $pdo->prepare('SELECT name FROM CachedDomains WHERE name LIKE ? AND isAlive=false ORDER BY name');
$updateStmt = $pdo->prepare('UPDATE CachedDomains SET isAlive=? WHERE name=?');
$stmt->execute(array($domainPrefix . '%'));

while (($row = $stmt->fetch(PDO::FETCH_ASSOC)))
{
    echo "Checking $row[name] ...";
    flush();
    $isAlive = isSiteAlive($row['name']);
    $updateStmt->execute(array($isAlive, $row['name']));
    $siteStatus = ($isAlive == true) ? "is alive" : "is dead";
    
    echo "$siteStatus<br/>\n";
    flush();
}

