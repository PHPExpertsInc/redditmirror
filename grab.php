<?php

define('GRABBED_SITES_KEY', 'redditmirror.cc: grabbed_sites');

function makeSiteKey($url, $redditKey)
{
        $host = parse_url($url, PHP_URL_HOST);
        $key = $host . '_' . $redditKey;

        return $key;
}

$_GET['debug'] = isset($argv[1]) ? $argv[1] : '';

// --- Configuration ---
define('REDDITMIRROR_TTL', 86400);
$update = false;

require_once('lastRSS.inc');
require_once('.dbcreds');                        // Contains class DBConfig

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$grabFromDB = false;

// Grab grabbed sites record...

$rss = new LastRSS();
$rss->cache_dir = 'cache';
$rss->cache_time = 600; // 5 minutes
$rss_url = 'http://www.reddit.com/.rss';

$results = $rss->Get($rss_url);

if (empty($results) || !$results)
{
	throw new RuntimeException("Could not grab/parse Reddit's RSS.");
}

foreach($results['items'] as $site)
{
	if (isset($_GET['debug']) && $_GET['debug'] == 1)
	{
		echo '<pre>' . print_r($site, true) . '</pre>';
	}

	$link = $site['link'];
	$site['description'] = htmlspecialchars_decode($site['description']);

	// We only care about remote sites, not reddit ones...
	$matches = array();
	$status = preg_match('/submitted by [^>]+>([^<]+)<\/a>(?: to <a href="[^>]+"> ([^<]+)<\/a>)?.+<a href="(http[^"]+)">\[link\]<\/a>.+\[([0-9]+) comments]/', $site['description'], $matches);

	//                echo '<pre>matches: ', print_r($matches, true), '</pre>';
	if ($status == false || !isset($matches[1]))
	{
		// something went wrong or it's a reddit URL; skip it.
		continue;
	}

	$redditor = trim($matches[1]);
	$category = trim($matches[2]);
	$url = trim($matches[3]);
	$comments_count = trim($matches[4]);

	//                if (isset($grabbed_sites[$url]) && !($update && $grabbed_sites[$url]['last updated'] > time() - REDDITMIRROR_TTL))
	if (!isset($_GET['refresh']) && isset($grabbed_sites[$url]))
	{
		continue;
	}

	$matches = array();
	preg_match('/\/comments\/([a-z0-9]+)/', $site['link'], $matches);
	$redditKey = $matches[1];

	set_time_limit(0);
	$key = makeSiteKey($url, $redditKey);
	echo '<div>Fetching ' . $url . '...</div>' . "\n";
	flush();
	error_log(__LINE__ . ': Running httrack!!');

	$output_path = 'cache/websites/' . date('Y/m/d');

	error_log('MIRRORED ' . $url . ' AT ' . date('c'));
	//                exec("httrack --continue --timeout=30 --robots=0 --mirror '" . $url . "' --depth=2 '-*' '+*.flv' '+*.css' '+*.js' '+*.jpg' '+*.gif' '+*.png' '+*.ico'  --user-agent 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.6 (KHTML, like Gecko) Chrome/7.0.508.0 Safari/534.6' -O cache/websites/" . $key);
	$command = '/usr/local/bin/mirror_page.php ' . $url . ' ' . $output_path . '/' . $key;
	if ($_GET['debug'] == 1)
	{
		echo "EXEC: $command\n";
	}

	$status = exec($command);

	// Record time grabbed
	$time = time();
	$grabbed_sites[$url] = array('title' => $site['title'],
			'last updated' => $time,
			'pubDate' => $site['pubDate'],
			'key' => $key,
			'comments' => $site['link']);

	// I'm almost positive we can delete this if statement, as we connect above...
	//the "BEGIN" thing starts a SQL transaction. In PDO, you do this via:
	// $pdo->beginTransaction() instead of mysql_query();
	// you roll them back via $pdo->rollback() and you commit via $pdo->commit().
	// Go ahead and do that for everything b/w line 194 and 251
	$pdo->beginTransaction();
	// Find Subredit ID

	$q1s = 'SELECT id FROM Categories WHERE name=?';
	$stmt = $pdo->prepare($q1s);
	$stmt->execute(array($category));


	$q1s = 'SELECT id FROM Categories WHERE name=?';
	$stmt = $pdo->prepare($q1s);
	$stmt->execute(array($category));

	if ($stmt->rowCount() == 0)
	{
		// So change thish entire $qs string into the one w/ placeholders (?) instead of variables, and move the variables to $pdo->execute(array($var1, $var2, etc)) lemme go back up hang on yes stuck
		// We're going to 1. rip out sprintf(), 2. replace %s with ? and 3. move $category into the $stmt->execute(array($category)). CleaR? mhm go for it ;p what goes in place of sprintf? Nothing ;) It's the only way to have semi-decent security in the old days.
		// you can ask me to do this, if u want. I think I can do one or two things but the first part I don't get. So I replace %s with ? and have what? $qs = ?;


		$qs = 'NSERT INTO Categories (name) VALUES (?)'; 
		$stmt = $pdo->prepare($qs);
		$stmt->execute(array($category));
		echo '<div>', $qs, '</div>';

		$categoryID = $pdo->lastInsertID();
	}
	else
	{
		// oops we missed one... go up to line 195
		// find the PDO function that fetches only one column... for mysql_result()?
		// Um... Ther'es a PDO function that will return only one column instead of
		// an array like usual. search for "fetch" and "column". in php.net/pdo
		// I'm thinking $stmt->fetchColumn() 

		$categoryID = $stmt->fetchColumn(0);
	}

	// Find Redditor's ID
	$q2s = 'SELECT id FROM Redditors WHERE name=?';
	$stmt = $pdo->prepare($q2s); //? is that the v ariable yep. now execute it
	$stmt->execute(array($redditor));

	if ($stmt->rowCount() == 0)
	{
		$qs = 'INSERT INTO Redditors (name) VALUES (?)'; 
		$stmt = $pdo->prepare($qs);
		$stmt->execute(array($redditor));

		$qs = 'INSERT INTO Redditors (name) VALUES (?)';
		$stmt = $pdo->prepare($qs);
		$stmt->execute(array($redditor));

		$redditorID = $pdo->lastInsertID();
	}
	else
	{
		$redditorID = $stmt->fetchColumn(0);
	}

	//                $q3s = 'INSERT INTO grabbed_urls (url, first_added, last_fetched) ' .
	$q3s = 'INSERT INTO GrabbedURLs (url, first_added, last_fetched) ' .
		'VALUES (?, NOW(), to_timestamp(?))';
	$stmt = $pdo->prepare($q3s);
	$stmt->execute(array($url, $time));

	$siteID = $pdo->lastInsertID();

	// This is how I format stuff like this.  You're under no obligation to copy, tho it'd be pretty cool if you thought my standard was worth adopting ;)
	// I like it cause it's organized and easy to read
	// Yeah, you coudl say it's 'beautiful'. Add $status = before $stmt->execute

	$q3s = 'INSERT INTO RedditSubmissions ' . 
		'(redditKey, title, url,  grabbedURLID, redditorID, categoryID, comments_count, published) VALUES ' .
		'(?, ?,  ?,  ?  ?, ?,  ?,  ?)';
	$stmt = $pdo->prepare($q3s);
	$status = $stmt->execute(array($redditKey,
				$site['title'],
				$site['link'],
				$siteID,
				$redditorID,
				$categoryID,
				$comments_count,
				date('c', strtotime($site['pubDate']))
				)
			);

	if ($status === false)
	{
		error_log("SQL ERROR: " . print_r($stmt->errorInfo(), true));
		error_log("SQL: " . $stmt->debugDumpParams());
		$pdo->rollback();
	}
	else
	{
		$pdo->commit();
	}
}

