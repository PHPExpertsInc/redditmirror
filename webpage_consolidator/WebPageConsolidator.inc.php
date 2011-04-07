<?php

ini_set('display_errors', 0);

require_once 'simplehtmldom/simple_html_dom.php';
require_once 'WebpageCache.inc.php';

// Recusively delete empty directories.
function rrmdir($dir)
{ 
    if (is_dir($dir))
    {
        $objects = scandir($dir); 
        foreach ($objects as $object)
        {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); 
            } 
        } 
        reset($objects); 
        @rmdir($dir); 
    } 
} 

interface WebPageConsolidatorI
{
    public function consolidate($params);
    public function deleteEncodedFiles($basedir);
}

class WebpageConsolidator implements WebPageConsolidatorI
{
	const STATUS_BLANK_HTML = 101;

    protected $html_filename;
    protected $collection;
    
    private function findWebpageFiles($basedir)
    {
        // Sanity checks.
        if ($basedir == '')
        {
            throw new RuntimeException('Basedir cannot be blank.');
        }

        $domain = substr($basedir, 0, strrpos($basedir, '_'));
        //$basedir = $basedir;

		// See if absolute path works
        if (!file_exists($basedir))
        {
			// If not, try the relative path.
			if (file_exists(getcwd() . '/' . $basedir))
			{
				// Change file path.
				$basedir = getcwd() . '/' . $basedir;
			}
			else
			{
            	throw new RuntimeException('Basedir does not exist: ' . htmlentities($basedir));
			}
        }


        // Find the main HTML file.
        $html = file_get_html("$basedir/index.html");
        $hrefs = $html->find('a');
        $html_filename = $basedir . '/' . $hrefs[0]->href;
//        echo "HTML File name: $html_filename\n"; exit;
        //echo "HTML file name cmd: $html_filename_cmd\n";
//        $html_filename = trim(`$html_filename_cmd`);
        //echo "HTML file name: $html_filename\n"; exit;
        $this->html_filename = $html_filename;

        $collection = array();
        $types = array('ico', 'gif', 'jpg', 'png', 'css', 'js');
        foreach ($types as $type)
        {
            $cmd = "find $basedir -iname \*.$type";
            $output = trim(`$cmd`);

            if ($output == '')
            {
                // No files available: bail.
                continue;
            }

            $files = explode("\n", $output);

            $finfo = @new finfo(FILEINFO_MIME); 
            foreach ($files as $key => $file)
            {
                // Get file's mime type.
                $fres = @$finfo->file($file);
                if ($type == 'css') { $fres = 'text/css'; }
                if ($type == 'js') { $fres = 'text/javascript'; }
                // Strip charset info.
        //        $fres = substr($fres, 0, strpos($fres, '; charset'));
                $collection[basename($file)] = array('type' => $fres,
                                                     'filename' => $file,
                                                     'data' => base64_encode(file_get_contents($file)));
            }
            unset($files);
        }

        $this->collection = $collection;
    }

    private function encodeWebpageFiles(Simple_HTML_Dom $html, $cssSelector, $destAttrib)
    {
        $ret = $html->find($cssSelector);
        foreach ($ret as $r)
        {
            $newSrc = '';
            $basename = basename($r->$destAttrib);

            // Strip any ? params.
            if (($pos = strrpos($basename, '?')) !== false)
            {
                $basename = substr($basename, 0, $pos);
            }

            // URL Decode.
            $basename = urldecode($basename);

        //    echo "<pre>Basename: $basename\n";
            if (!isset($this->collection[$basename]))
            {
//                echo "Couldn't find: $basename\n";
                continue;
            }

            $newSrc = sprintf('data:%s;base64,%s',
                              $this->collection[$basename]['type'],
                              $this->collection[$basename]['data']);

            $r->$destAttrib = $newSrc;
        }
    }

    public function consolidate($params)
    {
		if (!isset($params['basedir'])) { throw new LogicException('Must have a basedir'); }

		$basedir = $params['basedir'];
		$outputDir = isset($params['outputDir']) ? $params['outputDir'] : $basedir;
		$preHTML = isset($params['preHTML']) ? $params['preHTML'] : '';
		$postHTML = isset($params['postHTML']) ? $params['postHTML'] : '';

        // See if the webpage is already consolidated.
        $cache = new WebPageCache;
 
		if (isset($cache[$outputDir]))
        {
            return $cache[$outputDir];
        }

        $this->findWebpageFiles($basedir);

        $html = file_get_html($this->html_filename);

        // Encode stylesheets.
        $this->encodeWebpageFiles($html, 'link', 'href');

        // Encode images.
        $this->encodeWebpageFiles($html, 'img', 'src');

        $output = $preHTML . $html->save() . $postHTML;
		if ($output == "$preHTML$postHTML")
		{
			return self::STATUS_BLANK_HTML;
		}

        // Cache output.
        $cache[$outputDir] = $output;

		return $output;
    }
    
    public function deleteEncodedFiles($basedir)
    {
        if (empty($this->collection))
        {
            $this->findWebpageFiles($basedir);
        }

        // Remove httrack specific files.
        unlink("$basedir/cookies.txt");
        unlink("$basedir/hts-log.txt");

        // If there is no HTML file, that means it's probably an image-only
        // web page, so we should bail now.
        if (is_null($this->html_filename))
        {
            return;
        }
        
        if (!empty($this->collection))
        {
            foreach ($this->collection as $file)
            {
                if (file_exists($file['filename']))
                {
                    //echo "Deleted {$file['filename']}\n";
                    unlink($file['filename']);
                }
            }
        }

        // If we got this far it's probably good to go.        
        rrmdir($basedir);
    }
}

