<?php
/**
 * tapas.io_downloader.php
 *
 * Tapas.io webcomic downloader with auto resume and other features.
 *
 * Original author/version by https://github.com/TilCreator
 *
 * @author     l1m3r
 * @copyright  2019 l1mr3
 * @license    https://github.com/l1m3r/tapas.io-phpDLer/blob/master/LICENSE
 * @link       https://github.com/l1m3r/tapas.io-phpDLer
 */

// Constants
$baseULR='https://m.tapas.io/episode/';
$sitePref='?site_preference=mobile';
$globFnDiv1 = '-';  // Two filename dividers - used in regex and file_put_contents.
$globFnDiv2 = ' ';
$globStoreC_widht = 4;  // width of the first filename part - the +1 counter.
$exitWT = 0;	// wait time in seconds "after" exit.

$globStoreCnt = 0;
$epC_existingObjC = 0;


// --------------------- Define needed functions ---------------------
// get # of next episode
function get_next_EPn(string $string) {
	global $epC_nmbr;
	// OLD Variant (~pre march 2020): $ERL = preg_match('|<a href="/episode/([0-9]+)" class="cell next|U', $string, $nextEPn);
	$ERL = preg_match('|data-id="([0-9]+)">Next<i class="sp-ico-arrow-next">|U', $string, $nextEPn);
	if(!$ERL || !$nextEPn[1]){
		echo("\nNo episode number in/after #".$epC_nmbr." found.\n");
		return null;
	}
	// var_dump($nextEPn);
	$nextEPn = $nextEPn[1];
	return $nextEPn;
}

// generate path+filename(+ext)
function get_cFileName(int $nmbr, int $cEPn, $ext = '') {
	global $targetPath, $globStoreC_widht, $globFnDiv1;
	$fileN = sprintf('%0'.$globStoreC_widht.'d', $nmbr).$globFnDiv1.$cEPn;
	$fullN = $targetPath.$fileN.$ext;
	return $fullN;
}

// exit_wait
function exit_wait(int $time, $var, $text = ''){
	echo($text);
	sleep($time);
	exit($var);
}
// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


// --------------------- read opts from argv
$optind = 0;
$myargs = getopt( "e:p:", array () , $optind);
// Exit if not (2optsWithArgs + 1or2 = 5or6) or if $myargs.count!=2.
if ($optind < 5 or $optind > 6 or count($myargs) != 2) {
	exit_wait( $exitWT, +('1'.$optind.count($myargs)), "wrong number/kind of arguments given.\n
	Required are
		-e <# of first episode>
		-p <path2dir were to save>
	
	Note that -e <#> is ignored when the -p path is already properly filled.\n\n");
	// Number on the end of the URL, if you open the first page of the comic. (Example: 'https://tapas.io/episode/2141' => '2141')
}
// -------------------------- check given option e if int.
$epC_nmbr = $myargs['e'];
if (is_numeric($epC_nmbr)) {
	$epC_nmbr = +$epC_nmbr;
	if (!is_int($epC_nmbr)) $epC_nmbr = false;
} else $epC_nmbr = false;
if (!$epC_nmbr or (''.$epC_nmbr) !== (''.$myargs['e'])) { // to catch leading zeros like 01
	exit_wait( $exitWT, 100, '"'.$myargs['e'].'"'." is not an int.\n");
}

// ------------     check for illegal chars in $targetPath (maybe the DOS/ANSI/ASCII/whatever conversion is missing).
$targetPath = $myargs['p'];
if (preg_filter( array('#[\n\*\?\|\<\>]#'), array(''), $targetPath)) exit_wait( $exitWT, 101, 'Illegal chars in "'.$targetPath.'"'.".\n");

// make sure $targetPath ends in DIRECTORY_SEPARATOR - needs to come before cleanup!
if (substr($targetPath, -1) != DIRECTORY_SEPARATOR) $targetPath .= DIRECTORY_SEPARATOR;

// cleanup of $targetPath
$pattern = array('#\\\\#', '#/#', '#['.DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.']{2,}#'); // Dunno if this will work in Linux
$replace = array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);  // apparently PHP cant handle " in filenames...
$targetPath = preg_filter( $pattern, $replace, $targetPath);

// -------------------------  Check if target path exist and create it if it doesn't.
if (!file_exists($targetPath)) {
	if (!mkdir($targetPath, 0777, true)) exit_wait( $exitWT, 102, 'Couldn\'t create target dir "'.$targetPath.'"'."\n");
	echo($targetPath." created.\n");
} else {
	//	----------------  enumerate last saved episode and store it in $epC_nmbr
	$lntd = null;
	$lntd = scandir($targetPath, SCANDIR_SORT_DESCENDING);
	if (count($lntd) > 2 ) { // skip if only . and .. exist.
		// extract all episode numbers from filenames in descending order (the first entry is the last episode.
		$globStoreCnt = preg_match_all('|([0-9]{'.$globStoreC_widht.'})'.$globFnDiv1.'([0-9]+)('.$globFnDiv2.'.*)?\..+|U', implode("\n", $lntd) , $lntd, PREG_PATTERN_ORDER);
		if ($globStoreCnt) {
			$epC_nmbr = $lntd[2][0] +0; // store last episodes #.
			$epC_existingObjC = count( array_keys( $lntd[2], $epC_nmbr)); // count stored objects from last episode.
			
			$ERL = !($globStoreCnt == $lntd[1][0]); // compare number of found files with globcnt of last found file.
			for ($cI = 0; $cI < $epC_existingObjC; $cI++) {
				// Sanity check if $epC_nmbr is actually the episode# of all the last $epC_existingObjC files.
				$ERL = ( $ERL || !($lntd[2][$cI] == $epC_nmbr) );
				if (($cI + 1) < $epC_existingObjC) {
					// Sanity check if the "globStoreCnt"# of those files are correctly ascending.
					$ERL = ($ERL || !(1 == ($lntd[1][$cI] - $lntd[1][($cI+1)]) ) );
				}
				if ($ERL) break;
			}
			if ($ERL) exit_wait( $exitWT, 10, "Something with the existing files is fubar (cEP=".$epC_nmbr.", cEPc=".$epC_existingObjC.", ".$lntd[1][$cI]."-".$lntd[1][($cI+($globStoreCnt > 1 ? 1 : 0))]."=1?).\n");
		}
	}
}

echo("\n --- Downloading to '".$targetPath."' ---\n");

// --------------------- Main loop ---------------------
while (!empty($epC_nmbr)) {
	$imgUrls = null;
	
	// Get HTML site of current EP
	echo("\ngetting page of episode #".$epC_nmbr.".\n");
	$site = file_get_contents($baseULR.$epC_nmbr.$sitePref); // MISSING - Catch errors... , don't overwrite etc...
	
	// Write img-urls of current episode to array $imgUrls & count them to $epImgNmbr
	// Old pre march 2020 version?: $epImgNmbr = preg_match_all('|<img class="art-image" src="(.*)" width="|U', $site , $imgUrls);
	$epImgNmbr = preg_match_all('|<img src="(http.*)" class="content__img">|U', $site , $imgUrls);
	if (!$epImgNmbr) if ($epC_existingObjC) {
		// do not save M-URL if the M-file already exist.
		$epImgNmbr = $epC_existingObjC;
	} else {
		// save URL to M-EP for manual download.
		echo("Couldn't find any images in Episode ".$epC_nmbr." (".$epImgNmbr.")\n".
			"Maybe it's marked as mature -> creating URL-File & skipping to the next episode.\n"
		);
		if (!file_put_contents(get_cFileName(++$globStoreCnt, $epC_nmbr, $globFnDiv2.'M-Flagged-IMG_DL-Manually.URL'),
			'[InternetShortcut]'."\n".
			'URL='.$baseULR.$epC_nmbr.$sitePref."\n")) exit_wait( $exitWT, 51, 'Error saving file for mEP"'.$globStoreCnt.'".'."\n");
		
		// get # of next episode
		$epC_nmbr = get_next_EPn($site);
		$epC_existingObjC = 0;
		continue;
	}
	$imgUrls = $imgUrls[1]; //remove dimension - just keep the clean URLs.
	
	// check stored and actual cEP-imgCount against each other.
	if ($epC_existingObjC > $epImgNmbr) {
		exit_wait( $exitWT, 4, "The current episode has now less objects online then previously downloaded?!\n");
	} elseif ($epC_existingObjC == $epImgNmbr) {
		echo("Last object from #".$epC_nmbr." exists. -> skipping to next episode.\n");
	} else {
		// ---- get title of current EP ---------
		// old pre march 2020 version?: $ERL = preg_match('|<h1 class="episode-title">\s*((?U).+)\s*</h1>|s', $site, $epC_title);
		$ERL = preg_match('|<p class="info__title">((?U).+)</p>|s', $site, $epC_title);
		// var_dump($epC_title);
		if (!$ERL || !$epC_title[1]) {
			$epC_title = "";
		} else {
			// Clean up the extracted EP title.
			$epC_title = $globFnDiv2.html_entity_decode( $epC_title[1], (ENT_COMPAT | ENT_HTML401), ini_get("ISO8859-1"));
			$pattern = array('#[\n\*\?\|\<\>\/\\\\]#', '#\:#', '#( ){2,}#', '#"#'); 
			$replace = array(' ',                        ';',    ' ', "'");  // apparently PHP cant handle " in filenames...
			$temp = preg_filter( $pattern, $replace, $epC_title);
			if ($temp) $epC_title = $temp;
		}
		// iterate over all found $imgURLs and save the objects/images.
		for ($cI = $epC_existingObjC; $cI < $epImgNmbr; $cI++) {
			// extract extension of object/image-URL
			if( !preg_match('|\.[^\.]+$|U', $imgUrls[$cI], $file_ext)) exit_wait($exitWT, 2, "Couldn't find file extension of current imgURL ".'"'.$imgUrls[$cI].'"'." (EP=".$epC_nmbr." imgC=".($cI+1)."/".$epImgNmbr.").\n");
			
			$file_fullN = get_cFileName(++$globStoreCnt, $epC_nmbr, $epC_title.$file_ext[0]);
			// download and save the actual object/image
			echo('saving "'.$file_fullN.'"'.".\n");
			$file_data = file_get_contents($imgUrls[$cI]);
			if (!file_put_contents($file_fullN, $file_data)) exit_wait( $exitWT, 51, 'Error saving "'.$file_fullN.'".'."\n");
		}
	}
	// Go to the next episode...
	$epC_nmbr = get_next_EPn($site);
	$epC_existingObjC = 0;
}

exit_wait( $exitWT, 0, "\n --- Finished! '".$targetPath."' ---\n\n");
