<?php
if (!isset($_POST['url'])) { exit; }
session_start();

require '../.dbcreds';
$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

set_time_limit(180);

$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);

$matches = array();
preg_match('|https?://([^/]+)|', $url, $matches);
$domain = $matches[1];
//print_r($matches); exit;


$userID = $_SESSION['userID'];

try
{
	$pdo->beginTransaction();

	$stmt = $pdo->prepare('INSERT INTO CachedDomains (name, firstGrabbed, isAlive) VALUES (?, NOW(), 1)');
	$stmt->execute(array($domain));
	$domainID = $pdo->lastInsertId('id');
	unset($stmt);

	$stmt = $pdo->prepare('INSERT INTO GrabbedURLs (url, last_fetched, domainID) VALUES (?, NOW(), ?)');
	$stmt->execute(array($url, $domainID));
	$urlID = $pdo->lastInsertId('id');
	unset($stmt);

	$stmt = $pdo->prepare('INSERT INTO UserURLs (userID, urlID, title) VALUES (?, ?, ?)');
	$stmt->execute(array($userID, $urlID, $title));

	$pdo->commit();
}
catch(PDOException $e)
{
	echo 'PDO Exception: ' . $e->getMessage();
	$pdo->rollback();
	exit;
}

$path = "/var/www/redditmirror.cc/users/grabbed_urls/{$domain}_{$urlID}";
$command = '/usr/local/bin/mirror_page.php ' . $url . ' ' . $path;
//echo "Command: $command"; exit;
$status = exec($command);
if ($status == false) { exit; }
header('Location: http://' . $_SERVER['HTTP_HOST'] . '/users/profile.php?secret=' . $_SESSION['secret']);
