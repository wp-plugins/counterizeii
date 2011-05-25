<?php
/*
 Plugin Name: Counterize II
 Plugin URI: http://www.gabsoftware.com/products/scripts/counterize/
 Description: Simple counter-plugin with no external libs based on Counterize II by Steffen Forkmann (http://www.navision-blog.de/counterize) - saves timestamp, visited URl, referring URl and browserinformation in database, and can display total hits, unique hits and other statistics in WordPress webpages. Admin-interface available with detailed information...
 Version: 3.0.1
 Author: Gabriel Hautclocq
 Author URI: http://www.gabsoftware.com/
*/

/* global variables */
$counterizeii_plugin_dir = WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__));
$counterizeii_plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__));

if(function_exists('load_plugin_textdomain'))
{
	load_plugin_textdomain( 'counterize', $counterizeii_plugin_dir, plugin_basename(dirname(__FILE__)));
}

include("browsniff.php");

// Counterize II-Tables
function counterize_agentsTable()
{
	global $table_prefix;
	return $table_prefix . "Counterize_UserAgents";
}

function counterize_logTable()
{
	global $table_prefix;
	return $table_prefix . "Counterize";
}

function counterize_pageTable()
{
	global $table_prefix;
	return $table_prefix . "Counterize_Pages";
}

function counterize_refererTable()
{
	global $table_prefix;
	return $table_prefix . "Counterize_Referers";
}

function counterize_keywordTable()
{
	global $table_prefix;
	return $table_prefix . "Counterize_Keywords";
}

function counterize_update_all_userAgents()
{
	global $wpdb;
	$agents = $wpdb->get_results("SELECT * FROM `" . counterize_agentsTable() . "`");
	foreach ($agents as $agent)
	{
		list(
			$browser_name, $browser_code, $browser_ver, $browser_url,
			$os_name, $os_code, $os_ver, $os_url,
			$pda_name, $pda_code, $pda_ver, $pda_url
		) = counterize_detect_browser($agent->name);

		$query = "update "		. counterize_agentsTable() . " SET "
			. " browserName = '"	. $wpdb->escape($browser_name) . "', "
			. " browserCode = '"	. $wpdb->escape($browser_code) . "', "
			. " browserVersion = '"	. $wpdb->escape($browser_ver) . "', "
			. " browserURL = '"		. $wpdb->escape($browser_url) . "', "
			. " osName = '"			. $wpdb->escape($os_name) . "', "
			. " osCode = '"			. $wpdb->escape($os_code) . "', "
			. " osVersion = '"		. $wpdb->escape($os_ver) . "', "
			. " osURL = '"			. $wpdb->escape($os_url) . "' "
			. "WHERE agentID="		. $agent->agentID;

		$wpdb->query($query);
	}
}

# get the major release number of the installed mysql-version
function mysqlMajorRelease()
{
	$version = explode(".",mysql_get_client_info());
	return $version[0];
}

# Returns how many entries there are in the DB.
function counterize_getamount($only_this_month = false)
{
	global $wpdb;
	$sql = 'SELECT COUNT(1) FROM ' . counterize_logTable();
	if($only_this_month)
	{
		$sql .= " WHERE timestamp >= '" . date("Y-m-01") . "'";
	}
	return $wpdb->get_var($sql);
}

function counterize_getkeywordamount()
{
	global $wpdb;
	$sql = "SELECT SUM(count) FROM " . counterize_keywordTable() . " WHERE keywordID <> 1";
	return $wpdb->get_var($sql);
}

# Return how many unique entries there are in the DB.
function counterize_getuniqueamount()
{
	global $wpdb;
	$sql = 'SELECT COUNT(DISTINCT IP) FROM ' . counterize_logTable();
	return $wpdb->get_var($sql);
}

// deletes a entry from the database
function counterize_killEntry($entryID)
{
	global $wpdb;
	$entries = counterize_getentries(1, $entryID);
	foreach($entries as $entry)
	{
		$sql = "DELETE FROM " . counterize_logTable() . " WHERE ID={$entry->id}";
		$num = $wpdb->query($sql);

		$sql = "UPDATE ".counterize_pageTable() . " SET count = count - 1 WHERE pageID={$entry->pageID}";
		$num = $wpdb->query($sql);

		$sql = "UPDATE ".counterize_refererTable() . " SET count = count - 1 WHERE refererID={$entry->refererID}";
		$num = $wpdb->query($sql);

		$sql = "UPDATE ".counterize_agentsTable() . " SET count = count - 1 WHERE agentID={$entry->agentID}";
		$num = $wpdb->query($sql);

		$sql = "UPDATE ".counterize_keywordTable() . " SET count = count - 1 WHERE keywordID={$entry->keywordID}";
		$num = $wpdb->query($sql);
	}
}

// flushes the db - be careful
function counterize_flush()
{
	global $wpdb;
	$sql = 'DELETE FROM ' . counterize_logTable();
	$num = $wpdb->query($sql);

	$sql = 'DELETE FROM ' . counterize_pageTable();
	$num = $wpdb->query($sql);

	$sql = 'DELETE FROM ' . counterize_agentsTable();
	$num = $wpdb->query($sql);

	$sql = 'DELETE FROM ' . counterize_refererTable();
	$num = $wpdb->query($sql);

	$sql = 'DELETE FROM ' . counterize_keywordTable();
	$num = $wpdb->query($sql);
}

// Returns amount of hits today.
function counterize_gethitstoday()
{
	global $wpdb;
	$today = date("Y-m-d");
	$sql = "SELECT COUNT(1) FROM " . counterize_logTable() . " WHERE timestamp >= '{$today}'";
	return $wpdb->get_var($sql);
}

// Returns amount of hits during the last 7 days.
function counterize_getlatest7days()
{
	global $wpdb;
	$sevendaysago = date("Y-m-d", time() - 86400 * 7);
	$sql = "SELECT COUNT(1) FROM " . counterize_logTable() . " WHERE timestamp >= '{$sevendaysago}'";
	return $wpdb->get_var($sql);
}

// From Curtis(http://www.graymattersonline.net/)
// Returns amount of unique IP's in the last 7 days
function counterize_getuniquelatest7days()
{
	global $wpdb;
	$sevendaysago = date("Y-m-d", time() - 86400 * 7);
	$sql = "SELECT COUNT(DISTINCT IP) FROM " . counterize_logTable() . " WHERE timestamp >= '{$sevendaysago}'";
	return $wpdb->get_var($sql);
}

function counterize_get_online_users()
{
	global $wpdb;
	$timestamp = gmdate("Y-m-d H:i:s",time() + (get_option('gmt_offset') * 60 * 60 ));
	$sql = "SELECT COUNT(DISTINCT IP) FROM " . counterize_logTable() . " WHERE timestamp > DATE_SUB('{$timestamp}', INTERVAL 5 MINUTE)";
	return $wpdb->get_var($sql);
}

// Returns amount of unique referer-URl's
function counterize_getuniquereferers()
{
	global $wpdb;
	$sql = 'SELECT COUNT(DISTINCT refererID) FROM ' . counterize_logTable();
	return $wpdb->get_var($sql);
}

// Returns amount of unique browser-strings in DB.
function counterize_getuniquebrowsers()
{
	global $wpdb;
	$sql = 'SELECT COUNT(1) FROM '. counterize_agentsTable();
	return $wpdb->get_var($sql);
}

// Returns amount on current article
function counterize_getHitsOnCurrentArticle()
{
	global $wpdb;
	if ($_SERVER['REQUEST_URI']){
		$requesturl = $wpdb->escape($_SERVER['REQUEST_URI']);
		$sql = "SELECT Count FROM " . counterize_pageTable() . " WHERE url = '{$requesturl}'";
		return $wpdb->get_var($sql);
	}
	return 0;
}

// Returns amount of unique URl's
function counterize_getuniqueurl()
{
	global $wpdb;
	$sql = 'SELECT COUNT(1) FROM ' . counterize_refererTable();
	return $wpdb->get_var($sql);
}

function counterize_return_first_hit($dateformat = "j/n-Y")
{
	global $wpdb;
	$sql = "SELECT MIN(timestamp) FROM " . counterize_logTable();
	$t = $wpdb->get_var($sql);
	return date($dateformat, strtotime($t));
}

// show the most visited pages
function counterize_most_visited_pages($number = 10, $width = 300)
{
	global $wpdb;
	$number = $wpdb->escape($number);
	$sql = "SELECT count AS amount , url AS url, url AS label, url AS label2 FROM " . counterize_pageTable() . " ORDER BY amount DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);
	counterize_renderstats_vertical($rows, __('Page', 'counterize'), $width, false);
}

function counterize_most_visited_pages24hrs($number = 10, $width = 300)
{
	global $wpdb;
	$onedayago = date("Y-m-d", time() - 86400);
	$sql = "SELECT count AS amount, p.url AS url, p.url AS label, p.url AS label2 "
		. " FROM " . counterize_logTable() . " m, " . counterize_pageTable() . " p "
		. " WHERE m.pageID = p.pageID AND "
		. " m.timestamp >= '{$onedayago}'"
		. " GROUP BY p.url "
		. " ORDER BY count DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);
	counterize_renderstats_vertical($rows, __('Referer', 'counterize'), $width);
}

function counterize_most_visited_referrers24hrs($number = 10, $width = 300)
{
	global $wpdb;
	$onedayago = date("Y-m-d", time() - 86400);
	$sql = "SELECT count AS amount, r.name AS label, r.name AS label2, r.name AS url "
		. " FROM " . counterize_logTable() . " m, ". counterize_refererTable() . " r "
		. " WHERE m.refererID = r.refererID AND "
		. " r.name 'unknown' AND "
		. " r.name NOT LIKE '" . $wpdb->escape(get_option("home")) . "%%' "
		. " AND r.name NOT LIKE '" . $wpdb->escape(get_option("siteurl")) . "%%' "
		. " AND m.timestamp >= '{$onedayago}'"
		. " GROUP BY r.name "
		. " ORDER BY count DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);
	counterize_renderstats_vertical($rows, __('Referer', 'counterize'), $width);
}


// New in 0.53
function counterize_most_visited_referrers($number = 10, $width = 300)
{
	global $wpdb;
	$sql = "SELECT count AS amount, name AS label, name AS label2, name AS url FROM ".counterize_refererTable()." WHERE "
		. " name <> 'unknown' AND "
		. " name NOT LIKE '" . $wpdb->escape(get_option("home")) . "%%' "
		. " AND name NOT LIKE '" . $wpdb->escape(get_option("siteurl")) . "%%' "
		. " ORDER BY count DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);
	counterize_renderstats_vertical($rows, __('Referer', 'counterize'), $width);
}


function counterize_most_searched_keywords($number = 10, $width = 300)
{
	global $wpdb;
	$number = $wpdb->escape($number);
	$sql = "SELECT count AS amount, keyword AS label, keyword AS label2 FROM " . counterize_keywordTable() ." WHERE keywordID <> 1 GROUP BY keyword ORDER BY count DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);
	counterize_renderstats_vertical($rows, __('Keyword', 'counterize'), $width);
}

function counterize_most_searched_keywords_today($number = 10, $width = 300)
{
	global $wpdb;
	$today = date("Y-m-d");
	$number = $wpdb->escape($number);
	$sql = "SELECT COUNT(1) AS amount, k.keyword AS label, k.keyword AS label2 FROM " . counterize_keywordTable() . " k "
		. ", " . counterize_logTable() . " l, " . counterize_refererTable() . " r "
		. " WHERE r.refererID = l.refererID AND r.keywordID = k.keywordID "
		. " AND k.keywordID <> 1 AND l.timestamp >= '{$today}' GROUP BY k.keyword ORDER BY amount DESC LIMIT {$number}";

	$rows = $wpdb->get_results($sql);

	counterize_renderstats_vertical($rows, __('Keyword','counterize'), $width);
}

function counterize_most_used_browsers_without_version($number = 10, $width = 300)
{
	global $wpdb;
	$number = $wpdb->escape($number);
	$sql = "SELECT SUM(count) AS amount, browserName AS label, browserName AS label2, browserCode, browserURL AS url FROM " . counterize_agentsTable()
		. " GROUP BY label "
		. " ORDER BY amount DESC LIMIT $number";
	$rows = $wpdb->get_results($sql);

	reset($rows);
	while (list($i, $r) = each($rows))
	{
		$row =& $rows[$i];
		if($row->label == " " || $row->label == "")
		{
			$row->label = __("unknown", "counterize");
		}
		else
		{
			$row->label = counterize_get_image_url ($row->browserCode, $row->label, $row->url) . " " . $row->label;
		}
	}

	counterize_renderstats_vertical($rows, __('UserAgent','counterize'), $width, true, "100%", false);
}

function counterize_most_used_browsers($number = 10, $width = 300)
{
	global $wpdb;
	$number = $wpdb->escape($number);
	$sql = "SELECT SUM(count) AS amount, CONCAT(CONCAT(browserName,' '),browserVersion) AS label, CONCAT(CONCAT(browserName,' '),browserVersion) AS label2, browserCode, browserURL AS url FROM " . counterize_agentsTable()
		. " GROUP BY label "
		. " ORDER BY amount DESC LIMIT {$number}";
	$rows = $wpdb->get_results($sql);

	reset($rows);
	while (list($i, $r) = each($rows))
	{
		$row =& $rows[$i];
		if($row->label == " " || $row->label == "")
		{
			$row->label = __("unknown", "counterize");
		}
		else
		{
			$row->label = counterize_get_image_url ($row->browserCode, $row->label, $row->url) . " " . $row->label;
		}
	}

	counterize_renderstats_vertical($rows, __('UserAgent','counterize'), $width, true, "100%", false);
}

function counterize_most_used_os($number = 10, $width = 300)
{
	global $wpdb;
	$number = $wpdb->escape($number);
	$sql = "SELECT SUM(count) AS amount, CONCAT(CONCAT(osName,' '),osVersion) AS label, CONCAT(CONCAT(osName,' '),osVersion) AS label2, osCode, osURL AS url FROM " . counterize_agentsTable()
		. " GROUP BY label "
		. " ORDER BY amount DESC LIMIT $number";
	$rows = $wpdb->get_results($sql);

	reset($rows);
	while (list($i, $r) = each($rows))
	{
		$row =& $rows[$i];
		if($row->label == " " || $row->label == "")
		{
			$row->label = __("unknown","counterize");
		}
		else
		{
			$row->label = counterize_get_image_url ($row->osCode, $row->label, $row->url) . " " . $row->label;
		}
	}

	counterize_renderstats_vertical($rows, __('Operating System','counterize'), $width, true, "100%", false);
}

function counterize_getdailystats($only_this_month = false)
{
	global $wpdb;

	$sql = "SELECT
			DAYOFMONTH(timestamp) AS label,
			DAYOFMONTH(timestamp) AS label2,
			COUNT(1) AS amount
			FROM " . counterize_logTable();

	if($only_this_month)
	{
		$sql .= " WHERE timestamp >= '" . date("Y-m-01") . "'";
	}
	$sql .= " GROUP BY label";
	return $wpdb->get_results($sql);
}

function counterize_getmonthlystats()
{
	global $wpdb;

	$sql = "SELECT
			CONCAT(CONCAT(SUBSTRING(monthname(timestamp),1,3),' '),SUBSTRING(YEAR(timestamp),3,2)) AS label,
			CONCAT(CONCAT(SUBSTRING(monthname(timestamp),1,3),' '),SUBSTRING(YEAR(timestamp),3,2)) AS label2,
			COUNT(1) AS amount,
			MONTH(timestamp) AS m,
			YEAR(timestamp) AS y
			FROM " . counterize_logTable() . " GROUP BY label order by y,m";

	return $wpdb->get_results($sql);
}

function counterize_getweeklystats()
{
	global $wpdb;
	$sql = "SELECT
			DAYNAME(timestamp) AS label,
			DAYNAME(timestamp) AS label2,
			COUNT(1) AS amount,
			DAYOFWEEK(timestamp) AS day
			FROM " . counterize_logTable() . " GROUP BY label order by day";
	return $wpdb->get_results($sql);
}

// This is function is still not done - needs more work...
function counterize_gethourlystats($hour = "undef", $type = both)
{
	global $wpdb;

	$sql = "SELECT
			HOUR(timestamp) AS label,
			HOUR(timestamp) AS label2,
			COUNT(1) AS amount
			FROM " . counterize_logTable();

	$sql .= " GROUP BY label";
	return $wpdb->get_results($sql);
}

function counterize_ref_analyzer($referer)
{
	$domain = explode('/', $referer);

	$source = array(
		array('google','q'),
		array('alltheweb','query'),
		array('altavista','q'),
		array('aol','query'),
		array('excite','search'),
		array('hotbot','query'),
		array('lycos','query'),
		array('yahoo','p'),
		array('live','q'),
		array('t-online','q'),
		array('msn','q'),
		array('netscape','search')
	);

	$keyword = "";
	for($i=0; $i<count($source); $i++)
	{
		if(eregi($source[$i][0], $referer))
		{
			$parse = parse_url($referer);
			parse_str($parse['query'], $output);
			$keyword = $output[$source[$i][1]];
			break;
		}
	}

	return array('domain' => str_replace('www.', '', $domain[2]), 'keyword' => strtolower($keyword));
}

// Returns amount of unique hits today
function counterize_getuniquehitstoday()
{
	global $wpdb;
	$today = date("Y-m-d");
	$sql = "SELECT COUNT(DISTINCT ip) FROM ".counterize_logTable()." WHERE timestamp >= '$today'";
	return $wpdb->get_var($sql);
}


// Fetch information matching ID in DB.
function counterize_getentries($amount = 50, $entryID = null)
{
	global $wpdb;
	$sql = 'SELECT id, ip, timestamp, p.url AS url, r.name AS referer, ua.name AS useragent, ';
	$sql .= 'm.refererID, m.agentID, m.pageID, k.keyword, k.keywordID ';
	$sql .= "FROM ".
		counterize_logTable() . " m, " .
		counterize_pageTable() . " p, " .
		counterize_agentsTable() . " ua, " .
		counterize_refererTable() . " r, " .
		counterize_keywordTable() . " k ";
	$sql .= "WHERE m.pageID = p.pageID AND m.agentID = ua.agentID AND m.refererID = r.refererID ";
	$sql .= " AND k.keywordID = r.keywordID AND ";

	if($_GET["urifilter"])
	{
		$sql .= " p.url = '" . $wpdb->escape($_GET["urifilter"]) . "' AND ";
	}
	if($_GET["refererfilter"])
	{
		$sql .= " r.name = '" . $wpdb->escape($_GET["refererfilter"]) . "' AND ";
	}
	if($_GET["agentfilter"])
	{
		$sql .= " ua.name = '" . $wpdb->escape($_GET["agentfilter"]) . "' AND ";
	}

	if(isset($entryID))
	{
		$sql .= " m.id = " . $wpdb->escape($entryID) . " AND ";
	}

	$sql .= ' 1 = 1 ORDER BY m.timestamp DESC';

	if ($amount == "")
	{
		$sql .= " LIMIT 50";
	}
	else if ($amount != 0 AND is_numeric($amount))
	{
		$sql .= " LIMIT $amount";
	}

	return $wpdb->get_results($sql);
}

// Append information to DB
function counterize_add()
{
	global $wpdb;
	global $user_ID;

	// Set to unknown, if we're unable to extract information below.
	$referer = $remoteaddr = $useragent = $requesturl = 'unknown';

	if ($_SERVER['REMOTE_ADDR'])
	{
		$remoteaddr = $_SERVER['REMOTE_ADDR'];
	}
	if ($_SERVER['HTTP_USER_AGENT'])
	{
		$useragent = $_SERVER['HTTP_USER_AGENT'];
	}
	if ($_SERVER['REQUEST_URI'])
	{
		$requesturl = $_SERVER['REQUEST_URI'];
	}
	if ($_SERVER['HTTP_REFERER'])
	{
		$referer = $_SERVER['HTTP_REFERER'];
	}

	$this_url = "http://" . $_SERVER['HTTP_HOST'] . $requesturl;
	// Check to see if we really want to insert the entry...
	$checkval = 0;

	//
	// Bots detected and excluded
	//
	// To add an entry to the array, simply create line looking like this:
	//			$botarray[] = "<text in user-agent string>";
	$botarray[] = "bot";
	$botarray[] = "Yahoo! Slurp";
	$botarray[] = "slurpy";
	$botarray[] = "agent 007";
	$botarray[] = "ichiro";
	$botarray[] = "ia_archiver";
	$botarray[] = "zyborg";
	$botarray[] = "linkwalker";
	$botarray[] = "crawl";
	$botarray[] = "python";
	$botarray[] = "perl";
	$botarray[] = "w3c_validator";
	$botarray[] = "Microsoft URL Control";
	$botarray[] = "almaden";
	$botarray[] = "topicspy";
	$botarray[] = "poodle predictor";
	$botarray[] = "link checker pro";
	$botarray[] = "xenu link sleuth";
	$botarray[] = "iconsurf";
	$botarray[] = "zoe indexer";
	$botarray[] = "grub-client";
	$botarray[] = "spider";
	$botarray[] = "pompos";
	$botarray[] = "Mediapartners";
	$botarray[] = "virus_detector";
	$botarray[] = "Nuhk";
	$botarray[] = "findlinks";
	$botarray[] = "larbin";
	$botarray[] = "Sphere Scout";
	$botarray[] = "Ask Jeeves";
	$botarray[] = "Yahoo-Blogs";
	$botarray[] = "Yandex";
	$botarray[] = "Pagebull";
	$botarray[] = "HTTrack";
	$botarray[] = "OffByOne";
	$botarray[] = "PrintSmart";
	$botarray[] = "Getleft";
	$botarray[] = "Indy Library";
	$botarray[] = "DE Slurp";
	$botarray[] = "compatible; ICS";
	$botarray[] = "Powermarks";
	$botarray[] = "C-CCK-MCD";
	$botarray[] = "depspid";
	$botarray[] = "Twingly Recon";
	$botarray[] = "Netcraft";
	$botarray[] = "Check&amp;Get";
	$botarray[] = "relevantnoise";
	$botarray[] = "LiteFinder";
	$botarray[] = "Gigamega";
	$botarray[] = "Wget";
	$botarray[] = "Java/1.5";
	$botarray[] = "publisher.yahoo.com/rssguide";
	$botarray[] = "picmole";
	$botarray[] = "facebookexternalhit";
	$botarray[] = "bnd";
	$botarray[] = "noop";
	$botarray[] = "ucak bileeti";
	$botarray[] = "proxy.brigar.ru";
	$botarray[] = "Voyager/1.0";
	$botarray[] = "Evrinid";
	$botarray[] = "cmsworldmap.com";
	$botarray[] = "metauri.com";
	$botarray[] = "binlar";
	$botarray[] = "postrank.com";
	$botarray[] = "Java/1.6.0";
	$botarray[] = "Java/1.4.1";
	$botarray[] = "Mozilla/4.0 (compatible;)";
	$botarray[] = "Butterfly/1.0";
	$botarray[] = "RPT-HTTPClient";
	$botarray[] = "unknown";
	//

	// Run through bot-array and see if there's anything we don't like...
	foreach ($botarray as $entry)
	{
		if (stristr($useragent, $entry))
		{
			$checkval = 1;
		}
	}

	// From SHRIKEE, don't count RSS and other stuff...
	// Exclude files from being counted
	if ($checkval == 0)
	{
		// Exclude if referer is the same as url
		if ($this_url==$referer )
		{
			$checkval = 1;
		}

		// Exclude RSS feeds (Both with and without permalinks)
		// Stating just feed would make it impossible to name a page or post 'feed'
		/* if (stristr($requesturl, "feed/"))
		$checkval = 1;
		if (stristr($requesturl, "feed="))
		$checkval = 1;
		*/
		// Exclude files which annoying browsers like safari and opera request on each page
		if (stristr($requesturl, "robots.txt"))
		{
			$checkval = 1;
		}
		elseif (stristr($requesturl, "favicon.ico"))
		{
			$checkval = 1;
		}

		// Exclude any admin or core files
		elseif (stristr($requesturl, "wp-includes/"))
		{
			$checkval = 1;
		}
		elseif (stristr($requesturl, "wp-admin/"))
		{
			$checkval = 1;
		}
		elseif (stristr($requesturl, "wp-content/"))
		{
			$checkval = 1;
		}
	}

	// more extensions?
	elseif (stristr($requesturl, ".jpg"))
	{
		$checkval = 1;
	}
	elseif (stristr($requesturl, ".bmp"))
	{
		$checkval = 1;
	}
	elseif (stristr($requesturl, ".png"))
	{
		$checkval = 1;
	}
	elseif (stristr($requesturl, ".gif"))
	{
		$checkval = 1;
	}


	// If not found anything unwanted yet, check to see if it's on the excludelist...
	if ($checkval == 0)
	{
		$tmp = get_option('counterize_excluded');
		$excludelist = preg_replace('/\s\s+/', ' ', $tmp);

		$tmp_array = explode(" ", $excludelist);
		$count = count($tmp_array);

		if ($excludelist != "" && $excludelist != " ")
		{
			for ($i=0; $i<$count; $i++)
			{
				if (strpos($remoteaddr, $tmp_array[$i]) === FALSE)
				{
					// Coming up...
				}
				else
				{
					// IP found on exclude-list - we don't want it!
					$checkval = 1;
				}
			}
		}
	}

	// DISABLED: This functionality is replaced with the admin functionality
	//			to enable/disable counting of certain users...
	//
	// let's check it is a logged in user.
	// If he's logged in, we don't count him.
	if ($checkval == 0)
	{
		$excluded_users = explode(",", get_option('counterize_excluded_users'));
		get_currentuserinfo();
		$tmp = count($excluded_users);
		if($user_ID != "" && $user_ID != " " && in_array($user_ID, $excluded_users))
		{
			$checkval = 1;
		}
	}

	// If checkval is still 0, then yes - we want to insert it...
	if ($checkval == 0)
	{
		// Replace %20's(spaces) in strings with a white-space
		// Man, someone should create a better checking-module... *sigh*
		$requesturl = str_replace("%20", " ", $requesturl);
		$referer = str_replace("%20", " ", $referer);
		$timestamp = gmdate("Y-m-d H:i:s", time() + ( get_option('gmt_offset') * 60 * 60 ));

		$agentID = counterize_getUserAgentID($useragent);
		$pageID = counterize_getPageID($requesturl);
		$refererID = counterize_getRefererID($referer);
		$keywordID = counterize_getKeywordID($referer);

		$sql = "INSERT INTO " . counterize_logTable() . " (IP, timestamp, pageID, refererID, agentID) VALUES (";
		$sql .= "'" . substr( md5($wpdb->escape( $remoteaddr ) ), 1, 16) . "',";
		$sql .= "'" . $timestamp . "', '";
		$sql .= $pageID . "', '";
		$sql .= $refererID . "', '";
		$sql .= $agentID . "')";

		$results = $wpdb->query($sql);

		counterize_AddUserAgentVisit($agentID);
		counterize_AddPageVisit($pageID);
		counterize_AddRefererVisit($refererID);
		counterize_AddKeywordVisit($keywordID);
	}
}

// gives the useragentID back
function counterize_getUserAgentID($useragent)
{
	global $wpdb;
	$sql = "SELECT COUNT(agentID) FROM " . counterize_agentsTable() . " WHERE name = '" . $wpdb->escape($useragent) . "'";

	if(!$wpdb->get_var($sql))
	{
		// create new agent
		list(
			$browser_name, $browser_code, $browser_ver, $browser_url,
			$os_name, $os_code, $os_ver, $browser_url,
			$pda_name, $pda_code, $pda_ver, $pda_url
		) = counterize_detect_browser($useragent);

		$sql = "INSERT INTO " . counterize_agentsTable() . " (name,count,browserName,browserCode,browserVersion,browserURL,osName,osCode,osVersion,osURL) VALUES (";
		$sql .= "'" . $wpdb->escape($useragent) . "',0,'" . $wpdb->escape($browser_name) . "','" . $wpdb->escape($browser_code) . "','" . $wpdb->escape($browser_ver) . "','" . $wpdb->escape($browser_url) . "','".$wpdb->escape($os_name) . "','" . $wpdb->escape($os_code) . "','" . $wpdb->escape($os_ver) . "','" . $wpdb->escape($os_url) . "')";
		$wpdb->query($sql);
	}

	$sql = "SELECT agentID FROM ".counterize_agentsTable()." WHERE name = '".$wpdb->escape($useragent)."'";
	return $wpdb->get_var($sql);
}

function counterize_AddUserAgentVisit($agentID)
{
	global $wpdb;
	if(is_numeric($agentID))
	{
		$sql = "update " . counterize_agentsTable() . " SET count = count + 1 WHERE agentID = {$agentID}";
		$wpdb->query($sql);
	}
}

function counterize_AddKeywordVisit($keywordID)
{
	global $wpdb;
	if(is_numeric($keywordID)){
		$sql = "update " . counterize_keywordTable() . " SET count = count + 1 WHERE keywordID = {$keywordID}";
		$wpdb->query($sql);
	}
}

// gives the pageID back
function counterize_getPageID($url)
{
	global $wpdb;
	global $post;
	$sql = "SELECT pageID FROM " . counterize_pageTable() . " WHERE url = '" . $wpdb->escape($url) . "'";
	$pageID = $wpdb->get_var($sql);
	if(!$pageID)
	{
		// create new page
		$post_id = is_single() || $post->post_type == 'page'? $post->ID: 'NULL';
		$sql = "INSERT INTO " . counterize_pageTable() . " (url,count,postID) VALUES ('" . $wpdb->escape($url) . "',0," . $post_id . ")";
		$wpdb->query($sql);
		return $wpdb->insert_id;
	}
	return $pageID;
}

function counterize_AddPageVisit($pageID)
{
	global $wpdb;
	if(is_numeric($pageID))
	{
		$sql = "update " . counterize_pageTable() . " SET count = count + 1 WHERE pageID = {$pageID}";
		$wpdb->query($sql);
	}
}

// gives the keywordID back
function counterize_getKeywordID($referer)
{
	global $wpdb;
	$ref = counterize_ref_analyzer($referer);
	$sql = "SELECT keywordID FROM " . counterize_keywordTable() . " WHERE keyword = '" . $wpdb->escape($ref['keyword']) . "'";
	$keywordID = $wpdb->get_var($sql);

	if(!$keywordID)
	{
		// create new keyword
		$sql = "INSERT INTO " . counterize_keywordTable() . " (keyword,count) VALUES ('" . $wpdb->escape($ref['keyword']) . "',0)";
		$wpdb->query($sql);
		return $wpdb->insert_id;
	}
	return $keywordID;
}

// gives the refererID back
function counterize_getRefererID($referer)
{
	global $wpdb;
	$sql = "SELECT refererID FROM " . counterize_refererTable() . " WHERE name = '" . $wpdb->escape($referer) . "'";
	$refererID = $wpdb->get_var($sql);
	if(!$refererID)
	{
		$keywordID = counterize_getKeywordID($referer);
		// create new referer
		$sql = "INSERT INTO ".counterize_refererTable()." (name,count,keywordID) VALUES (";
		$sql .= "'" . $wpdb->escape($referer) . "',0,{$keywordID})";
		$wpdb->query($sql);
		return $wpdb->insert_id;
	}
	return $refererID;
}

function counterize_AddRefererVisit($refererID)
{
	global $wpdb;
	if(is_numeric($refererID))
	{
		$sql = "update " . counterize_refererTable() . " SET count = count + 1 WHERE refererID = {$refererID}";
		$wpdb->query($sql);
	}
}

function counterize_pagefooter()
{
	?>
	<div class="wrap">
		<?php _e('<strong>Need help? </strong>Go to <a href="http://www.gabsoftware.com/products/scripts/counterize/">the Counterize II homepage</a> and search inside the comments. Others may have had the same question or problem as you...', 'counterize'); ?>
	</div>
	<?php
}

function counterize_copyright()
{
	?>
	<br />
	<br />
	<p style="text-align: center">
		<small><?php
			_e('Statistics recorded with <a href="http://www.gabsoftware.com/products/scripts/counterize/" title="Counterize II - Statistics-plugin for Wordpress by GabSoftware">Counterize II</a>', 'counterize');
			echo ' - ' . __('Version') . ' ' . get_option('counterize_MajorVersion') . "." . get_option('counterize_MinorVersion') . "." . get_option('counterize_Revision');
		?></small>
	</p>
	<p>
	<?php
}

function counterize_renderstats_vertical($rows, $header, $max_width = "500",
$nofollow = true, $maxwidth = "100%", $shorten = true)
{
	global $counterizeii_plugin_url;
	$max_label = get_option('counterize_maxWidth');
	foreach($rows as $row)
	{
		$items++;
		$complete_amount += $row->amount;
		if($row->amount > $max)
		{
			$max = $row->amount;
		}
	}

	?>

			<!-- Stats by Counterize (c) GabSoftware -->
			<table width="<?php echo $maxwidth; ?>">
				<tr class="alternate">
					<td style="width: 25%"><small><strong><?php _e($header,'counterize'); ?></strong></small></td>
					<td style="width: 10%"><small><strong><?php _e('Amount','counterize'); ?></strong></small></td>
					<td style="width: 65%"><small><strong><?php _e('Percentage','counterize'); ?></strong></small></td>
				</tr>

	<?php
	foreach($rows as $row)
	{
		$percent = round($row->amount / $complete_amount * 100, 2);

		if($row->amount)
		{
			$width = round($row->amount * $max_width / $max);
		}
		else
		{
			$width = 0;
		}

		$group = round($width / $max_width * 100);
		?>

				<tr<?php if($counter%2) { echo " class=\"alternate\""; } ?>>
					<td style="width: 25%" align="left">
						<small><?php
		if(strlen($row->label) > $max_label && $shorten == true)
		{
			$label = substr($row->label,0,$max_label) . '...';
		}
		else
		{
			$label = $row->label;
		}
		if($row->url)
		{
			echo "\n\t\t\t\t\t\t\t<a href=\"" . $row->url . "\"";
			if($nofollow)
			{
				echo " rel=\"nofollow\"";
			}
			echo ">\n\t\t\t\t\t\t\t\t" . $label . "\n\t\t\t\t\t\t\t</a>\n";
		}
		else
		{
			echo $label;
		}
		?>
						</small>
					</td>

					<td style="width: 10%">
						<small><?php echo $row->amount; ?></small>
					</td>

					<td style="width: 65%" align="left">
						<img	src="<?php echo $counterizeii_plugin_url;
				if ($group < 40)
						echo "/counterize_red.png";
				else if ($group < 80)
						echo "/counterize_yellow.png";
				else
						echo "/counterize_green.png";
				?>"
							style="height:8px; width:<?php echo $width ?>px; vertical-align:bottom"
							alt="<?php
				echo $header . ' - ' . htmlspecialchars($row->label2) . ' - ' . $row->amount . ' - ' . $percent . ' %';
				?>" />
						<small><strong><?php echo $percent; ?> %</strong></small>
					</td>
				</tr>

	<?php $counter++;
}
?>

			</table>

<?php
}


function counterize_renderstats($rows, $max_height = 80, $maxwidth = "100%")
{
	global $counterizeii_plugin_url;
	?>
	<table width="<?php echo $maxwidth; ?>">
		<tr>
	<?php

	foreach($rows as $row)
	{
		$items++;
		$complete_amount += $row->amount;
		if($row->amount > $max)
		{
			$max = $row->amount;
		}
	}

	foreach($rows as $row)
	{
		$percent = round($row->amount / $complete_amount * 100,2);

		if($row->amount)
		{
			$height = round($row->amount * $max_height / $max);
		}
		else
		{
			$height = 0;
		}

		$group = round($height / $max_height * 100);

		echo "<td style=\"width:3%\"";
		if($i%2)
		{
			print "class=\"alternate\"";
		}

		echo " align=\"center\" valign=\"bottom\"><small>";
		echo $row->amount;
		?>
		<br />
		<img src="<?php echo $counterizeii_plugin_url;
			if ($group < 40)
			{
				echo "/counterize_red.png";
			}
			else if ($group < 80)
			{
				echo "/counterize_yellow.png";
			}
			else
			{
				echo "/counterize_green.png";
			}
			?>" style="width:8px; height:<?php echo $height ?>px; vertical-align:bottom" alt="Statistics" />
			<?php
			echo "<br />{$percent}<br />%</small></td>";
			$i++;
}
?>
		</tr>
		<tr>
	<?php
	$i = 0;
	foreach($rows as $row)
	{
		echo "<td style=\"width:3%\"";
		if($i % 2)
		{
			echo "class=\"alternate\"";
		}
		echo " align=\"center\"><small><strong>{$row->label}</strong></small></td>";
		$i++;
	}
	?>
		</tr>
	</table>
	<?php
}

// Do the installation stuff, if the plugin is marked to be activated...
include("counterize_install.php");
$install = (basename($_SERVER['SCRIPT_NAME']) == 'plugins.php' && isset($_GET['activate']));
if ($install)
{
	counterize_install();
}

function counterize_show_history()
{
	$howmany = __("Latest entries",'counterize');

	$amount = get_option('counterize_amount');
	if ($amount == "" || $amount == " ")
	{
		$amount = 50;
	}

	$howmany = $howmany . " (" .$amount .")";
	$entries = counterize_getentries($amount);

	?>
	<div class="wrap">
	<h2><?php echo $howmany; ?></h2>

	<a href="edit.php?page=counterizeii/counterize.php"><?php _e("Reset Filters", 'counterize'); ?></a></br>
	</br>
	<form method="post"
		action="edit.php?page=counterizeii/counterize.php&amp;killmass=yes"
		name="tablesForm" id="tablesForm">
	<table width="100%" cellpadding="3" cellspacing="3">
		<tr class="alternate">
			<td scope="col" width="2%"><strong><?php _e("del", 'counterize'); ?></strong></td>
			<td scope="col" width="6%"><strong><?php _e("ID", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 14%"><strong><?php _e("Timestamp", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 20%"><strong><?php _e("URl", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 31%"><strong><?php _e("Referer", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 10%"><strong><?php _e("UserAgent", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 25%"><strong><?php _e("Keywords", 'counterize'); ?></strong></td>
			<td scope="col" style="width: 3%"><strong><?php _e("Kill", 'counterize'); ?></strong></td>
		</tr>

	<?php
	foreach($entries as $entry)
	{
		?>
		<tr <?php if($i%2) { print "class=\"alternate\""; } ?>>
			<td scope="col" width="6%"><small> <input type="checkbox"
				name='counterize_killemall[<?php echo $entry->id; ?>]'
				value="<?php echo $entry->id; ?>" /></td>
			<td><?php echo $entry->id; ?></small></td>
			<td scope="col" width="14%"><small><?php echo $entry->timestamp; ?> </small></td>
			<td scope="col" width="25%"><small><?php echo "<a href=\"" . $entry->url . "\">" . wordwrap($entry->url, 30, "\n", 1); ?>
			</a> (<a
				href="edit.php?page=counterizeii/counterize.php&urifilter=<?php echo $entry->url; ?>">F</a>)</small></td>
			<td scope="col" width="20%"><small> <?php
				if ($entry->referer != "unknown")
				{
					echo "<a href=\"" . $entry->referer . "\">" . wordwrap($entry->referer, 30, "\n", 1) . "</a>";
					?> (<a
					href="edit.php?page=counterizeii/counterize.php&refererfilter=<?php echo $entry->referer; ?>">F</a>)
					<?php
				}
				else
				{
					echo wordwrap($entry->referer, 30, "\n", 1);
				}
				?> </small>
			</td>
			<td scope="col" style="width: 25%"><small><?php echo counterize_browser_string($entry->useragent , true, '<br>'); ?></small>
			</td>
			<td><small><?php echo $entry->keyword; ?></small></td>
			<td scope="col" style="width: 5%"><a
				href="javascript:conf('edit.php?page=counterizeii/counterize.php&amp;kill=<?php echo $entry->id; ?>');">
			<font color="red" size="+1">X</font> </a></td>
			<?php
			$i++;
	}
	?>
		</tr>
	</table>

	<input type="button" name="CheckAll"
		value="<?php _e("Check All",'counterize'); ?>"
		onClick="checkAll(document.tablesForm)" /> <input type="button"
		name="UnCheckAll" value="<?php _e("Uncheck All", 'counterize'); ?>"
		onClick="uncheckAll(document.tablesForm)" /> <input type="SUBMIT"
		value="<?php _e("Delete selected Entries", 'counterize'); ?>" /></form>
	</div>

<?php

}

function counterize_updateText($text="Configuration updated", $color="red")
{
	echo "<div id=\"message\" class=\"updated fade\"><p><font color=\"{$color}\">";
	_e($text, 'counterize');
	echo "</font></p></div>";
}

function counterize_showStats($admin = false)
{
	if(!counterize_getamount())
	{
		_e("There's no data in the database - You can't see stats until you have data.", 'counterize');
		return;
	}
	?>
	</p>
	<div class="wrap">
	<h2><?php _e('Hit Counter', "counterize");?></h2>
	<table width="100%" cellpadding="3" cellspacing="3">
		<tr>
			<td scope="col" style="width: 15%" align="center"><?php _e("Total hits: ", 'counterize'); ?>
			</td>
			<td scope="col" style="width: 15%" align="center"><?php _e("Hits from unique IPs: ", 'counterize'); ?>
			</td>
			<td scope="col" style="width: 15%" align="center"><?php _e("Total hits, today: ", 'counterize'); ?>
			</td>
			<td scope="col" style="width: 20%" align="center"><?php _e("Hits from unique IPs, today: ", 'counterize'); ?>
			</td>
			<td scope="col" style="width: 15%" align="center"><?php _e("Hits, the last 7 days: ", 'counterize'); ?>
			</td>
			<td scope="col" style="width: 20%" align="center"><?php _e("Unique hits, the last 7 days: ", 'counterize'); ?>
			</td>
		</tr>
		<tr>
			<td align="center"><strong><?php echo counterize_getamount(); ?></strong></td>
			<td align="center"><strong><?php echo counterize_getuniqueamount(); ?></strong></td>
			<td align="center"><strong><?php echo counterize_gethitstoday(); ?></strong></td>
			<td align="center"><strong><?php echo counterize_getuniquehitstoday(); ?></strong></td>
			<td align="center"><strong><?php echo counterize_getlatest7days(); ?></strong></td>
			<td align="center"><strong><?php echo counterize_getuniquelatest7days(); ?></strong></td>
		</tr>
	</table>
	</div>

		<?php
		// Amount to pass as option to the graphs...
		$amount2 = get_option('counterize_amount2');
		if ($amount2 == "" || $amount2 == " ")
		{
			$amount2 = 5;
		}
		$width = 250;
		?>

	<div class="wrap">
	<h2><?php _e('Visits based on day of month','counterize');?></h2>
		<?php
		if($admin)
		{
			counterize_renderstats(counterize_getdailystats());
		}
		else
		{
			counterize_renderstats_vertical(counterize_getdailystats(), __("Day", 'counterize'), $width);
		}
		?></div>

	<div class="wrap">
	<h2><?php _e('Visits based on day of month (only this month)', 'counterize');?></h2>
		<?php
		if($admin)
		{
			counterize_renderstats(counterize_getdailystats(true));
		}
		else
		{
			counterize_renderstats_vertical(counterize_getdailystats(true), __("Day", 'counterize'), $width);
		}
		?></div>


	<div class="wrap">
	<h2><?php _e('Visits based on day of week','counterize');?></h2>
		<?php
		if($admin)
		{
			counterize_renderstats(counterize_getweeklystats());
		}
		else
		{
			counterize_renderstats_vertical(counterize_getweeklystats(), __("Day", 'counterize'), $width);
		}
		?></div>

	<div class="wrap">
	<h2><?php _e('Visits based on month','counterize');?></h2>
		<?php
		if($admin)
		{
			counterize_renderstats(counterize_getmonthlystats());
		}
		else
		{
			counterize_renderstats_vertical(counterize_getmonthlystats(), __("Month", 'counterize'), $width);
		}
		?></div>

	<div class="wrap">
	<h2><?php _e('Visits based on hour of day', 'counterize');?></h2>
		<?php
		if($admin)
		{
			counterize_renderstats(counterize_gethourlystats());
		}
		else
		{
			counterize_renderstats_vertical(counterize_gethourlystats(), __("Hour", 'counterize'), $width);
		}
		?></div>

	<div class="wrap">
	<h2><?php echo __("Most visited pages ", 'counterize') . "(" . $amount2 .")"; ?></h2>
		<?php counterize_most_visited_pages($amount2,$width); ?></div>
	<div class="wrap">
	<h2><?php echo __("Most seen referers ", 'counterize') . "(" . $amount2 . ")"; ?></h2>
	<?php counterize_most_visited_referrers($amount2,$width); ?></div>
	<div class="wrap">
	<h2><?php echo __("Most used browsers ", 'counterize') . "(" . $amount2 . ")";?></h2>
	<?php counterize_most_used_browsers_without_version($amount2, $width); ?>
	</div>

	<div class="wrap">
	<h2><?php echo __("Most used browsers versions ", 'counterize') . "(" . $amount2 . ")";?></h2>
	<?php counterize_most_used_browsers($amount2, $width); ?></div>

	<div class="wrap">
	<h2><?php echo __("Most used operating systems ",'counterize') . "(" . $amount2 . ")";?></h2>
	<?php counterize_most_used_os($amount2, $width); ?></div>

	<div class="wrap">
	<h2><?php echo __("Most searched keywords ",'counterize') . "(" . $amount2 . ")";?></h2>
	<?php counterize_most_searched_keywords($amount2, $width); ?></div>

	<div class="wrap">
	<h2><?php echo __("Most searched keywords today",'counterize') . "(" . $amount2 . ")";?></h2>
	<?php counterize_most_searched_keywords_today($amount2, $width); ?></div>

	<?php
}

function counterize_manage_page()
{
	?>
	<script language="javascript" type="text/javascript">
		function conf(url)
		{
				if (confirm('<?php _e('Are you sure that you want to delete this entry?','counterize'); ?>'))
				{
						self.location.href = url;
				}
		}

		<!--
		// by Nannette Thacker
		// http://www.shiningstar.net -->

		function checkAll(field)
		{
			for (i = 0; i < field.length; i++)
				field[i].checked = true ;
		}

		function uncheckAll(field)
		{
			for (i = 0; i < field.length; i++)
				field[i].checked = false ;
		}

	</script>

	<?php

	$updateText = "";
	if (isset($_GET['killmass']))
	{
		if ($_GET['killmass']=='yes')
		{
			if (isset($_POST['counterize_killemall']))
			{
				foreach ($_POST['counterize_killemall'] as $key => $val)
				{
					counterize_killEntry($val);
					$updateText .= __('Entry: '.$val.' removed<br />','counterize');
				}
				counterize_updateText($updateText);
			}
		}
	}

	// For the zap-an-entry-option
	if (isset($_GET['kill']))
	{
		counterize_killEntry($_GET['kill']);
		counterize_updateText(__("Deleting entry ", "counterize") . $_GET['kill']);
	}

	counterize_showStats(true);
	counterize_show_history();
	counterize_pagefooter();
}

function counterize_filter($data)
{
	$pattern = '/\<\!\-\-\s*counterize_stats\s*\-\-\>/';
	while(preg_match($pattern, $data, $matches))
	{
		ob_start();

		counterize_showStats();
		counterize_copyright();

		$content = ob_get_contents();
		ob_end_clean();
		$replace_pattern = $pattern;
		$data = preg_replace($replace_pattern, $content, $data);
	}
	return $data;
}

include("counterize_admin.php");
include("counterize_dashboard.php");

function counterize_add_pages()
{
	// Set it up... - add to Dashboard and options-page.
	add_action('activity_box_end', 'counterize_dashboard');
	add_submenu_page('edit.php',__('Counterize II','counterize'), __('Counterize II','counterize'), 8, __FILE__, 'counterize_manage_page');
	add_options_page(__('Counterize II Options','counterize'), __('Counterize II','counterize'), 8, basename(__FILE__), 'counterize_options_page');
}

add_action('admin_menu', 'counterize_add_pages');

// Create API hook instead of placing code in the header.php-file
add_action('wp_head', 'counterize_add', 1);
add_filter('the_content', 'counterize_filter');

?>
