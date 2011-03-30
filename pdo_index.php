<?php
if (isset($_SERVER["HTTP_ACCEPT"]) && stristr( $_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
{
    header("Content-type: application/xhtml+xml");
}
else
{
    header("Content-type: text/html; charset=UTF-8");
}

session_start();
/* ------------ BSD LICENSE -----------
Copyright (c) 2008, Theodore R. Smith
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED 
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT 
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT 
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;  LOSS OF USE, 
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


function makeSiteKey($url, $redditKey)
{
        $host = parse_url($url, PHP_URL_HOST);
        $key = $host . '_' . $redditKey;

        return $key;
}

// --- Configuration ---
define('REDDITMIRROR_TTL', 86400);
$update = false;

require_once('lastRSS.inc');
require_once('.dbcreds');                        // Contains class DBConfig

$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s', DBConfig::$host, DBConfig::$db), DBConfig::$user, DBConfig::$pass);

$grabFromDB = false;

if (isset($_GET['source']) && $_GET['source'] == 'db')
{
        $grabFromDB = true;
}

if (isset($_GET['startDate']))
{
        $grabFromDB = true;
        $timestamp = strtotime($_GET['startDate']) + 86400;
        $startDate = date('c', $timestamp);
}
else
{
        $startDate = date('c');
}

// Grab grabbed sites record...
$dblink = null;
/*
if (isset($_GET['grabAll']))
{
        $qs = 'SELECT url, title, published, redditKey, UNIX_TIMESTAMP(last_fetched) last_fetched, commentLink FROM GrabbedURLs ORDER BY published';

// So you see that mysql_query($qs)? Replace w/ $pdo->prepare($qs)
// Change $qq to $stmt (means $statement)
        $stmt = $pdo->prepare($qs);
// Change the mysql_fetch_object($qq) to $stmt->fetchObject()
// What's happening is that we're using the OOP PDO instead of mysql_functions.
// ok skip to line 105
        while ($qr = $stmt->fetchObject())
        {
                $key = makeSiteKey($qr->url, $qr->redditKey);
                echo '<div>Grabbing ' . $qr->title . ' -> ' . $key . '</div>' . "\n";
                flush();
                exec("httrack --timeout=30 --continue --robots=0 --mirror '" . $qr->url . "' --depth=2 '-*' '+*.css' '+*.js' '+*.jpg' '+*.gif' '+*.png' '+*.ico' -O cache/websites/" . $key);
        }

        exit;
}
*/

ob_start('ob_gzhandler');
//if ($grabFromDB === true || ($grabbed_sites = apc_fetch('redditmirror.cc grabbed_sites')) === false)
{
        // Looks stale, get it from the database
        $grabbed_sites = array();
// Line 105 seems unnecessary as we already connect way up at the top. So get rid of 105 and 106.
// We do NOT want to have variables inside our SQL string, really ever.
// So replace variables like ' . $startDate . ' with just ?.
        $qs = 'SELECT * FROM vw_RedditLinks 
                 WHERE published BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND ? 
                 ORDER BY published';
// and the 2nd one.. and make it "AND ? ORDER" e.g. remove the ' . and the first one too
        $stmt = $pdo->prepare($qs);
		$stmt->execute(array($startDate, $startDate));


        while ($qr =  $stmt->fetch())
        {
                $key = makeSiteKey($qr['url'], $qr['redditKey']);
                $grabbed_sites[$qr['url']] = array('title' => $qr['title'],
                                                   'last updated' => $qr['last_fetched'],
                                                   'pubDate' => $qr['published'],
                                                   'key' => $key,
                                                   'comments' => $qr['commentLink']);
                if (isset($_GET['debug']))
                {
                        $url = $qr['url'];
                        print("httrack --timeout=30 --continue --robots=0 --mirror '" . $url . "' --depth=2 '-*' '+*.css' '+*.js' '+*.jpg' '+*.gif' '+*.png' '+*.ico' -O cache/websites/" . $key);
//                        echo '<pre>' . print_r($grabbed_sites[$qr['url']], true) . '</pre>';
                        flush();
                        exec("httrack --continue --robots=0 timeout=30 --mirror '" . $url . "' --depth=2 '-*' '+*.css' '+*.js' '+*.jpg' '+*.gif' '+*.png' '+*.ico' -O cache/websites/" . $key);
                }
        }
}

$rss = new LastRSS();
$rss->cache_dir = 'cache';
$rss->cache_time = 600; // 5 minutes
$rss_url = 'http://www.reddit.com/.rss';

if (!$grabFromDB && (isset($_GET['secret']) && $_GET['secret'] == 'asdf2223') && $results = $rss->Get($rss_url))
{
        trigger_error('omg', E_USER_WARNING);
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
                error_log('Running httrack!!');
                exec("httrack --continue --timeout=30 --robots=0 --mirror '" . $url . "' --depth=2 '-*' '+*.flv' '+*.css' '+*.js' '+*.jpg' '+*.gif' '+*.png' '+*.ico'  --user-agent 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.6 (KHTML, like Gecko) Chrome/7.0.508.0 Safari/534.6' -O cache/websites/" . $key);
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

/*
whats the $pdo thing for mysql_real_escape?
Good question!  PDO uses the alternative to escaping, called prepared statements.
$pdo->prepare($sql) prepares it and $stmt->execute() exceutes them.
Instead of doing
     $sql = "SELEcT * FROM Foo WHERE value= ' mysql_real_escape_string($value);
     mysql_query($sql);
you'd do:
     $sql = "SELECT * FROM Foo WHERE value=?";
     $stmt = $pdo->prepare($sql);
     $stmt->execute(array($value));
ok? ok
// hey.... is this what we want? PDOStatement->fetchAll — Returns an array containing all of the result set rows
No i'm looking for a number ;-) search for "number" and "row".
// what about rowCount ? Preeecisseely
*/
// Go to http://us3.php.net/pdo and find the equivalent function to return # of rows a query returns.
// So mysql_num_rows() would be changed to...? and then delete the $q1q
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
                        $stmt-execute(array($redditor));
                        
                        $redditorID = $pdo->lastInsertID();
                }
                else
                {
                        $redditorID = $stmt->fetchColumn(0);
                }

                $q3s = 'INSERT INTO GrabbedURLs (url, first_added, last_fetched) ' .
                            'VALUES (?, NOW(), FROM_UNIXTIME(?))';
                $pdo->prepare($q3s);
                $pdo->execute(array($url, $time));

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
                        $pdo->rollback();
                }
                else
                {
                        $pdo->commit();
                }
        }

        if ($grabFromDB === false)
        {
                apc_store('redditmirror.cc: grabbed_sites', $grabbed_sites, 600);
        }
//        apc_delete('redditmirror.cc: grabbed_sites');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
                <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
                <title>The Reddit Mirror | redditmirror.cc</title>
                <link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'/>
                <link rel="stylesheet" href="style.css" />
        </head>
        <body>
<!--[if IE]>
<style type="text/css">
    body { margin: 0; }
</style>
<div style="margin: 0px 0 0 -10px; width:100%; border-bottom: 2px solid red; background: yellow; padding: 10px;">
<strong>Warning</strong>: You are using Internet Explorer.  This site will <strong>*NOT*</strong> look right in it.
Please upgrade to <strong><a style="color: blue;" href="http://getfirefox.com/">Firefox</a> or something else</strong>.
</div>
<br />
<![endif]-->
                <div id="main_content">
                        <h1 style="text-align: center">The Reddit Mirror Archive</h1>
                        <p>This is an app for mirroring sites that make the front page of <a href="http://www.reddit.com/">reddit.com</a>.</p>
                        <p>
                                This web application is coded in <a href="http://www.php.net/">PHP 5</a> via an <a href="http://httpd.apache.org/">Apache 2 server</a>
                                running on <a href="http://www.gentoo.org/">Gentoo Linux</a> box hosted by <a href="http://www.serverloft.com/">ServerLoft.com</a>.  It uses
                                the wonderful PHP RSS parser library <a href="http://lastrss.oslab.net/">LastRSS</a> and <a href="http://www.httrack.com/">HTTrack</a>.
                        </p>
                        <p><a href="http://www.redditmirror.cc/stats/overview.html">Redditmirror web stats</a> provided by <a href="http://www.summary.net/?referrer=redditmirror.cc">Summary.net Plus</a>.</p>
                        <p>I'm currently experimenting with the <em>FREE</em> <strong><a href="http://www.coralcdn.org/">Coral Content Delivery Network</a></strong>.</p>
<?php
if ($grabFromDB === true)
{
?>
            <p><em>Using an archived list from the database...</em></p>
<?php
}
?>
                        <ol id="links">
<?php
        foreach ($grabbed_sites as $url => $site)
        {
                $title[$url] = $site['title'];
                $lastUpdated[$url] = $site['last updated'];
                $pubDate[$url] = $site['pubDate'];
        }

        if (isset($_GET['sort']) && $_GET['sort'] == 'pubDate')
        {
                array_multisort($pubDate, SORT_ASC, $grabbed_sites);
        }
        else
        {
                array_multisort($lastUpdated, SORT_DESC, $grabbed_sites);
        }

        foreach ($grabbed_sites as $url => $data)
        {
?>
                                <li>
                                        <span>
                                                <!-- <a href="cache/websites/<?php echo htmlspecialchars($data['key']); ?>"></a> -->
                                                <a href="cache/consolidated/<?php echo htmlspecialchars(urlencode($data['key'])); ?>.htmlz"><?php echo htmlspecialchars($data['title']); ?></a>
                                        </span>
                                        <ul>
                                                <li><a href="<?php echo htmlspecialchars($data['comments']); ?>">[comments]</a></li>
                                                <li><a href="<?php echo htmlspecialchars($url); ?>">[direct link]</a></li>
                                                <li>Published at <?php echo date('r', strtotime($data['pubDate'])); ?></li>
                                                <li>Fetched at <?php echo date('r', $data['last updated']); ?></li>
                                                <!-- <li><a href="webpage_consolidator/?base=<?php echo htmlspecialchars(urlencode($data['key'])); ?>">[consolidate files]</a></li> -->
                                                <li><a href="cache/websites/<?php echo htmlspecialchars($data['key']); ?>">[original mirror]</a></li>
                                        </ul>
                                </li>
<?php
        }
?>
                        </ol>
                        <br style="clear: left;"/>
                        <p id="todo">
                                TODO:
                        </p>
                        <ol>
                                <li>Search DB by date</li>
                                <li>Choose results order</li>
                                <li>More stats/reports</li>
                        </ol>
                        <ul id="badges">
                                <li>
                                        <a href="http://validator.w3.org/check?uri=referer"><img
                                           src="http://www.w3.org/Icons/valid-xhtml10-blue"
                                           alt="Valid XHTML 1.0 Strict" height="31" width="88" /></a>
                                </li>
                                <li>
                                        <a href="http://www.mozilla.com/firefox?from=sfx&amp;uid=0&amp;t=305"><img alt="Spreadfirefox Affiliate Button" src="/misc/110x32_best-yet.png" /></a>
                                </li>
                                <li>
                                        <a href="http://www.prchecker.info/"><img style="border:0px" src="http://pr.prchecker.info/getpr.php?codex=aHR0cDovL3d3dy5yZWRkaXRtaXJyb3IuY2Mv&amp;tag=1" alt="Page Rank Check"/></a>
                                </li>
                                <li>
                                    <a href="http://www.siteuptime.com/" onmouseover="this.href='http://www.siteuptime.com/statistics.php?Id=90539&amp;UserId=108574';"><img width="85" height="16" alt="website uptime" src="http://btn.siteuptime.com/genbutton.php?u=108574&amp;m=90539&amp;c=blue&amp;p=total" style="border: 0"/></a><noscript><div><a href="http://www.siteuptime.com/">website monitoring</a></div></noscript>
                                </li>
                        </ul>
                </div>
        </body>
</html>
