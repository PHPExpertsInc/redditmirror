<?php

require 'WebPageConsolidator.inc.php';
require_once dirname(__FILE__) . '/../.dbcreds';

// Command line stuff here
if (php_sapi_name() == 'cli')
{
    require_once "Console/Getopt.php";

    $cg = new Console_Getopt(); 
    $args = $cg->readPHPArgv();
    $params = array('suppress', 'delete');
    $ret = $cg->getopt($args, 's', $params);

    foreach ($ret[0] as $arg)
    {
        // Strip '--' from args:
        $param = preg_replace('/^--/', '', $arg[0]);
        if (in_array($param, $params))
        {
            $_GET[$param] = ($arg[1] != '') ? $arg[1] : true;
        }
    }

    if (isset($ret[1][0]))
    {
        $_GET['base'] = $ret[1][0];
    }
}

//print_r($_GET); exit;

if (!isset($_GET['base']))
{
	echo "ERROR: A directory to parse is required.\n";
    exit;
}

$orig_basedir = $basedir = (isset($argv[1])) ? 
                            $argv[1] :
							filter_var($_GET['base'], FILTER_SANITIZE_STRING);
//echo "Basedir: $orig_basedir";
if (!file_exists($basedir))
{
	if (file_exists('../cache/websites/' . $basedir))
	{
		$basedir = '../cache/websites/' . $basedir;
	}
	else
	{
		throw new RuntimeException('Cannot find basedir: ' . htmlentities($basedir));
	}
}

$redditKey = substr($orig_basedir, strrpos($orig_basedir, '_') + 1);

$pdo = new PDO('mysql:host=' . DBConfig::$host . ';dbname=' . DBConfig::$db, DBConfig::$user, DBConfig::$pass);
$stmt = $pdo->prepare('SELECT url FROM vw_RedditLinks WHERE redditKey=?');
$stmt->execute(array($redditKey));
$originalURL = $stmt->fetchColumn();

$outputDir = isset($argv[2]) ? $argv[2] : '../cache/consolidated/' . $orig_basedir;

$consolidator = new WebpageConsolidator;
$preHTML = '<h3 style="text-align: center; padding-bottom: 5px; margin-bottom: 5px; border-bottom: black 2px solid; width: 100%; height: 1em; background: white; text-transform: none">ORIGINAL URL: <a href="' . htmlspecialchars($originalURL) . '">' . htmlspecialchars($originalURL) . '</a></h3>';
$html = $consolidator->consolidate(array('basedir' => $basedir,
			                             'outputDir' => $outputDir,
										 'preHTML' => $preHTML));

if ($html == WebpageConsolidator::STATUS_BLANK_HTML)
{
	// Redirect to cache...
//	header('HTTP/1.1 301 Moved Permanently');
//	header('Location: http://' . $_SERVER['HTTP_HOST'] . '/cache/websites/' . $orig_basedir . '/');
	echo "<div style=\"text-align: center\"><h3>ORIGINAL URL: <a href=\"$originalURL\">$originalURL</a></h3>\n";
	$url = 'http://' . $_SERVER['HTTP_HOST'] . '/cache/websites/' . $orig_basedir . '/' . preg_replace('/http:\/\//', '', $originalURL);
    printf('<img src="%s" alt="%s"/></div>', $url, $originalURL); 
	exit;
}

if (isset($_GET['delete']))
{
    $consolidator->deleteEncodedFiles($basedir);
}

if (!isset($argv[1]) && !isset($_GET['suppress']))
{
    echo $html;
}

