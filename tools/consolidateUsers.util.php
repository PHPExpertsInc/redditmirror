<?php

require '.dbcreds';

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

// Get duplicate user names.
$stmt = $pdo->prepare('SELECT r1.id AS orig, r1.name, r2.id AS dupe FROM redditors r1 CROSS JOIN redditors r2 WHERE r1.name != r2.name AND r1.name = TRIM(r2.name)');
$stmt->execute();
$pdo->beginTransaction();
$stmt2 = $pdo->prepare('UPDATE GrabbedSites SET RedditorID=? WHERE RedditorID=?');
$stmt3 = $pdo->prepare('DELETE FROM redditors WHERE id=?');

while (($row = $stmt->fetch(PDO::FETCH_ASSOC)))
{
	echo "Updating $row[name] from $row[dupe] to $row[orig]...\n"; flush();
	$stmt2->execute(array($row['orig'], $row['dupe']));
	$stmt3->execute(array($row['dupe']));
}

$pdo->commit();

