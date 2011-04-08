<?php

require '.dbcreds';

// Please fetch the 'source' GET parameter.
$source = filter_input(INPUT_GET, 'source', FILTER_SANITIZE_STRING);

$key = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
$redditKey = substr($key, strrpos($key, '_') + 1);

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$stmt = $pdo->prepare('SELECT * FROM vw_RedditLinks WHERE redditKey=?');
$stmt->execute(array($redditKey));

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row == false)
{
	header('HTTP/1.0 404 File Not Found');
	echo "<h2>404 File Not Found</h2>";
	exit;
}

// Please make an if statement that directs to either consolidated or websites...
if ($source == 'consolidated')
{
	echo('Location: http://' . $_SERVER['HTTP_HOST'] . '/cache/consolidated/' . date('Y/m/d', strtotime($row['published'])) . "/$key.htmlz");
}
else if ($source == 'websites')
{
	echo('Location: http://' . $_SERVER['HTTP_HOST'] . '/cache/websites/' . date('Y/m/d', strtotime($row['published'])) . "/$key/");
}
