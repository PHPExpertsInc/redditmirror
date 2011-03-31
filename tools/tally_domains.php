<?php

require '.dbcreds';

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$stmt = $pdo->prepare('SELECT DISTINCT(url), published FROM GrabbedSites_v2 ORDER BY id');
$stmt->execute();

$insertStmt = $pdo->prepare('INSERT INTO cached_domains (name, firstGrabbed, count) VALUES (?, ?, 1)');
$updateStmt = $pdo->prepare('UPDATE cached_domains SET count=count+1 WHERE name=?');

while (($row = $stmt->fetch(PDO::FETCH_ASSOC)))
{
    $matches = array();
    preg_match('|https?://([^/]+)|', $row['url'], $matches);
    $domain = $matches[1];

    if ($insertStmt->execute(array($domain, $row['published'])) === false)
    {
        // Probably already inserted, let's increment the count.
        $updateStmt->execute(array($domain));
    }
}
