<?php

define('GRABBED_SITES_KEY', 'redditmirror.cc: grabbed_sites');

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

ob_start('ob_gzhandler');
if ($grabFromDB === true || ($grabbed_sites = apc_fetch(GRABBED_SITES_KEY)) === false)
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
        }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
                <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
                <title>The Reddit Mirror | redditmirror.cc</title>
                <link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'/>
				M
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
                                                <a href="cache/consolidated/<?php echo date('Y/m/d', strtotime($data['pubDate'])); ?>/<?php echo htmlspecialchars(urlencode($data['key'])); ?>.htmlz"><?php echo htmlspecialchars($data['title']); ?></a>
                                        </span>
                                        <ul>
                                                <li><a href="<?php echo htmlspecialchars($data['comments']); ?>">[comments]</a></li>
                                                <li><a href="<?php echo htmlspecialchars($url); ?>">[direct link]</a></li>
                                                <li>Published at <?php echo date('r', strtotime($data['pubDate'])); ?></li>
                                                <li>Fetched at <?php echo date('r', $data['last updated']); ?></li>
                                                <li><a href="cache/websites/<?php echo date('Y/m/d', strtotime($data['pubDate'])); ?>/<?php echo htmlspecialchars($data['key']); ?>">[original mirror]</a></li>
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
								<li>
<!--									<a href="http://www.mishmomo.com/"><img src="/files/handcodedBlue.png"/></a>-->
									<a href="http://twitter.com/share" class="twitter-share-button" data-url="http://www.redditmirror.cc/" data-count="vertical" data-via="RedditMirror">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script> 
								</li>
                        </ul>
                </div>
        </body>
</html>
