<?php

require '../.dbcreds';

function getDomain($url)
{
	$parts = parse_url($url); // M: what's 'parse' mean? I see it a lot, but what is it?
	// M: "To parse" means to analyze and basically break into pieces; to analyze systematically.
	// parse_url() groks URLs and returns them in pieces. See http://php.net/parse_url for more.

	if ($parts === false) // M: what does the operator '===' me?
		                  // T: The '===' means "is true" *and* is a boolean datatype.
		                  // In PHP (and JavaScript), == will return true for true, '1', 1, 'any string except 0'.
		                  // Realistically, you should use === every time except when you're not sure what will be
		                  // returned exactly.
	{
		trigger_error("Could not parse URL: $url", E_USER_WARNING); // M: What's this? I know it's not an exception.
		                                                            // T: This is a user-generated Warning.  It basically
		                                                            // is used just for logging/notification purposes.
		                                                            // I would like to be aware of any URLs that can't be
		                                                            // parsed, but I don't want to hose the system up, esp.
		                                                            // since there are 65,000+ URLs.
		return false;

	}

	return $parts['host']; // M: so does this show the hostname portion of the URL?
	                           // T: Yes, more commonly known as the domain name.
	
}

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$stmt = $pdo->prepare('SELECT url, published, redditKey FROM vw_RedditLinks ORDER BY url'); // M: pulling data from DB?
$clearFetchedStmt = $pdo->prepare('UPDATE GrabbedURLs SET last_fetched=NULL WHERE url=?');
$stmt->execute();

while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) // M: what the hell?
	                                            // T: Code to fetch one row from the DB and stick it in $row variable
                                                // as an associative array (e.g. $row = array('url' => 'http://foo/', 'domain' => 'foo', ...)
{
	$domain = getDomain($row['url']);
	if ($domain === false) { continue; }
	$urlPartialPath = $domain . '_' . $row['redditKey']; // M: why does it look like there are too many ) in this line? 
	                                                                     // T: Becuase there are!!! The last one is superfluous. Good catch!
	                                                                     // M: Also, does this just pull the domain name from the url?
	                                                                     // T: getDomain() returns the domain name, this is combined with the redditkey
	                                                                     // to produce the redditmirror mirror_key of domain_redditKey.
	$filePath = '../cache/websites/' . $urlPartialPath . '/'; // what's this for?
	                                                          // I didn't want a magic constant (used line 49 and 54).

	if (!file_exists($filePath))
	{
		echo "URL NOT found: $row[url] - $filePath\n";
//		$clearFetchedStmt->execute(array($row['url']));
		continue; // continue to what?
	}

	$dateDirs = date('Y/m/d', strtotime($row['published'])); 
	$newFilePath = '../cache/websites/' . $dateDirs . '/' . $urlPartialPath; // M: is $row['published'] for a date?
	                                                                                                            // T: Yes, the date the URL made it onto reddit.

	// Make the date directories, if needed.
	echo("mkdir -p ../cache/websites/$dateDirs\n");
	exec("mkdir -p ../cache/websites/$dateDirs");
	echo("mv -v $filePath $newFilePath\n"); // M: what is the argument -v for?
	exec("mv -v $filePath $newFilePath"); // M: what is the argument -v for?
	                                      // T: "V" for "verbose". Shows the files as they're moved.
}

