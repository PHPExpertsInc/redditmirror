<?php

$dsn = "mysql:host=localhost;dbname=redditmirror";
$pdo = new PDO($dsn, 'redditmirror', '9iLKWn');

$stmt = $pdo->prepare('SELECT url FROM GrabbedSites');
$stmt->execute();

$hosts = array();
while (($result = $stmt->fetch(PDO::FETCH_ASSOC)))
{
	$url = $result['url'];
	$bits = parse_url($url);
	$host = $bits['host'];

	$hosts[$host] += 1;
}

asort($hosts);

foreach ($hosts as $host => $count)
{
	print "$host: $count\n";
}

