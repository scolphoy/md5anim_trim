<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <juhani.toivonen@cs.helsinki.fi> wrote this file. As long as you retain this 
 * notice you can do whatever you want with this stuff. If we meet some day, 
 * and you think this stuff is worth it, you can buy me a beer in return. 
 * 
 * - Juhani Toivonen
 * ----------------------------------------------------------------------------
 */

$allow_zip = true;


/* --- INPUT VALIDATION --- */
if (!is_numeric($_POST['scale'])) die("Scale is not numeric!\n");
if ($_POST['scale'] < 1) die("Scale is not greater than or equal to 1!\n");
if (empty($_FILES) || empty($_FILES['inputfile']) || !file_exists($_FILES['inputfile']['tmp_name'])) 
	die("Failed to read files. Did you use the field name 'inputfile'?\n");


/* --- FUNCTIONS --- */
/* Process the content of a single md5anim file. */
function process_md5anim_content($string) {
	$callback = function ($matches) {
		return round($matches[0], $_POST['scale']);
	};

	return preg_replace_callback('/[-]?[0-9]+\.[0-9]+\b/', $callback, $string);
}

/* Process single file: Read the whole input in and process it. */
function handle_single_file($file) {
	$filecontent = file_get_contents($file);

	/* Return the resulting md5anim-document. */
	header("Content-Type: text/plain");
	echo process_md5anim_content($filecontent);
}

/* Process a zip archive: Crawl through a zip archive files one by one, 
 * process all encountered .md5anim -files and build a new zip archive. */
function handle_zip_file($file) {
	$temp   = tempnam(sys_get_temp_dir(), "md5trim");
	$oldzip = new ZipArchive();
	$newzip = new ZipArchive();
	$newzip->open($temp, ZipArchive::CREATE);

	if (!$oldzip->open($file)) die("Could not open zip file\n");
 	for ($i = 0; $i < $oldzip->numFiles; $i++) {
		$filename = $oldzip->getNameIndex($i);
		if (substr_compare($filename, ".md5anim", -8, 8, true) == 0) {
			$filecontent      = $oldzip->getFromIndex($i);
			$processedcontent = process_md5anim_content($filecontent);
			$newzip->addFromString($filename, $processedcontent);
		}
	}

	$oldzip->close();
	$newzip->close();

	/* Return the resulting zip-file */
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=\"trimmed-".$_FILES['inputfile']['name']."\"");
	echo file_get_contents($temp);
}


/*  --- MAIN PROGRAM ---*/

$filetype = "-";
$zip_mimetypes = array("application/zip; charset=binary", 
                       "application/x-zip; charset=binary", 
                       "application/x-zip-compressed; charset=binary"
                       );

/* If support for zip archives is enabled, check mime-type. */
if ($allow_zip) {
	$finfo = finfo_open(FILEINFO_MIME);
	if (!$finfo) {die("Loading FileInfo failed. Is package installed?\n");}

	$filetype = finfo_file($finfo, $_FILES['inputfile']['tmp_name']);
}

/* If the file was identified as a zip archive, process it as such,
 * otherwise assume it's a single .md5anim file. */
if ($allow_zip && in_array($filetype, $zip_mimetypes)) {
	error_log("md5anim_trim: Identified ". $filetype ." as a zip archive");
	handle_zip_file($_FILES['inputfile']['tmp_name']);
} else {
	error_log("md5anim_trim: Identified ". $filetype ." as not a zip archive");
	handle_single_file($_FILES['inputfile']['tmp_name']);
}

?>
