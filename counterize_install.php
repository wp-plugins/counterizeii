<?php

// Used for first-time initialization
// create tables if not present...
function counterize_install()
{
	update_option('counterize_logbots', "disabled");

	$MajorVersion = get_option('counterize_MajorVersion', 1);
	$MinorVersion = get_option('counterize_MinorVersion', 0);
	$Revision = get_option('counterize_Revision', 0);

	global $wpdb;

	if($MajorVersion < 2)
	{
		$sql = 'SHOW TABLES LIKE \'' . counterize_logTable() . '\'';
		$results = $wpdb->query($sql);

		if ($results == 0)
		{
			// Update to Version 1
			$sql = "CREATE TABLE `". counterize_logTable() . "` (
				id INTEGER NOT NULL AUTO_INCREMENT,
				`IP` VARCHAR(16) NOT NULL,
				`timestamp` DATETIME NOT NULL,
				url VARCHAR(255) NOT NULL DEFAULT 'unknown',
				referer VARCHAR(255) NOT NULL DEFAULT 'unknown',
				useragent TEXT,
				PRIMARY KEY(id)
			)";

			$results = $wpdb->query($sql);
		}

		// update to Version 2
		$sql = "ALTER TABLE `" . counterize_logTable() . "` ADD `pageID` INT( 11 ) NOT NULL;";
		$wpdb->query($sql);

		$sql = "ALTER TABLE `" . counterize_logTable() . "` ADD `agentID` INT( 11 ) NOT NULL;";
		$wpdb->query($sql);

		$sql = "ALTER TABLE `" . counterize_logTable() . "` ADD `refererID` INT( 11 ) NOT NULL;";
		$wpdb->query($sql);

		$sql = "CREATE TABLE `" . counterize_pageTable() . "` (
			`pageID` INT(11) NOT NULL AUTO_INCREMENT,
			`url` VARCHAR(255) NOT NULL,
			`count` INT(11) NOT NULL DEFAULT '1',
			`postID` BIGINT(20) DEFAULT NULL,
			PRIMARY KEY (`pageID`),
			KEY `url` (`url`),
			KEY `count` (`count`)
			)";
		$wpdb->query($sql);

		$sql ="CREATE TABLE `" . counterize_refererTable() . "` (
			`refererID` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) NOT NULL,
			`count` INT(11) NOT NULL DEFAULT '1',
			PRIMARY KEY (`refererID`),
			KEY `name` (`name`),
			KEY `count` (`count`)
			)";
		$wpdb->query($sql);

		$sql = "CREATE TABLE `" . counterize_agentsTable() . "` (
			`agentID` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) NOT NULL,
			`count` INT(11) NOT NULL DEFAULT '1',
			PRIMARY KEY (`agentID`),
			KEY `name` (`name`),
			KEY `count` (`count`)
			) ";
		$wpdb->query($sql);

		$sql = "INSERT INTO `" . counterize_pageTable() . "` (url,count)
			SELECT
			url, COUNT(url)
			FROM `" . counterize_logTable() . "`
			GROUP BY url;";
		$wpdb->query($sql);

		$sql = "INSERT INTO `" . counterize_refererTable() . "` (name,count)
			SELECT
			referer, COUNT(referer)
			FROM `" . counterize_logTable() . "`
			GROUP BY referer;";
		$wpdb->query($sql);

		$sql = "INSERT INTO `" . counterize_agentsTable() . "` (name,count)
			SELECT
			useragent, COUNT(useragent)
			FROM `" . counterize_logTable() . "`
			GROUP BY useragent;";
		$wpdb->query($sql);

		$entries = $wpdb->get_results("SELECT * FROM " . counterize_logTable());
		foreach ($entries as $entry)
		{
			$pageID = $wpdb->get_var("SELECT pageID FROM `" . counterize_pageTable() . "` WHERE url='" . $entry->url . "'");
			$agentID = $wpdb->get_var("SELECT agentID FROM `" . counterize_agentsTable() . "` WHERE name='" . $entry->useragent . "'");
			$refererID = $wpdb->get_var("SELECT refererID FROM `" . counterize_refererTable() . "` WHERE name='" . $entry->referer . "'");
			if (!$pageID)
				$pageID = "null";
			if (!$agentID)
				$agentID = "null";
			if (!$refererID)
				$refererID = "null";
				$sql = "UPDATE `" . counterize_logTable() . "` SET pageID = {$pageID}, agentID = {$agentID}, refererID = {$refererID} WHERE id = " . $entry->id;
				$wpdb->query($sql);
		}

		$sql = "ALTER TABLE `" . counterize_logTable() . "` DROP `url`;";
		$wpdb->query($sql);

		$sql = "ALTER TABLE `" . counterize_logTable() . "` DROP `useragent`;";
		$wpdb->query($sql);

		$sql = "ALTER TABLE `" . counterize_logTable() . "` DROP `referer`;";
		$wpdb->query($sql);
	}

	if($MajorVersion < 3)
	{


		// now we have Version 2
		if($MinorVersion < 4)
		{
			update_option('counterize_whois', 'http://ws.arin.net/cgi-bin/whois.pl?queryinput=');

			$sql = "CREATE TABLE `" . counterize_keywordTable() . "` (
				`keywordID` INT(11) NOT NULL AUTO_INCREMENT,
				`keyword` VARCHAR(255) NOT NULL,
				`count` INT(11) NOT NULL DEFAULT '1',
				PRIMARY KEY (`keywordID`),
				KEY `keyword` (`keyword`)
				);";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_refererTable() . "` ADD `keywordID` INT( 11 ) NOT NULL ;";
			$wpdb->query($sql);

			$referers = $wpdb->get_results("SELECT * FROM `" . counterize_refererTable() . "`");
			foreach ($referers as $referer)
			{
				$keywordID = counterize_getKeywordID($referer->name);
				$wpdb->query("UPDATE " . counterize_refererTable() . " SET keywordID = {$keywordID} WHERE refererID=" . $referer->refererID);
				$wpdb->query("UPDATE " . counterize_keywordTable() . " SET count = count + " . $referer->count . " WHERE keywordID=" . $keywordID);
			}
		}

		if($MinorVersion < 8)
		{
			update_option('counterize_maxWidth', 50);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `browserName` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `browserCode` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `browserVersion` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `osName` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `osCode` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `osVersion` VARCHAR( 255 ) NOT NULL ;";
			$wpdb->query($sql);

			//counterize_update_all_userAgents();
		}

		if($minorVersion < 12)
		{
			$sql = "ALTER TABLE `" . counterize_logTable() . "` ADD INDEX ( `timestamp` );";
			$wpdb->query($sql);
		}

		if($minorVersion < 13)
		{
			$sql = "UPDATE `" . counterize_logTable() . "` SET IP=MD5(IP);" ;
			$wpdb->query($sql);
		}

		if($MinorVersion < 15)
		{

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `browserURL` VARCHAR( 255 ) NOT NULL AFTER `browserVersion` ;";
			$wpdb->query($sql);

			$sql = "ALTER TABLE `" . counterize_agentsTable() . "` ADD `osURL` VARCHAR( 255 ) NOT NULL AFTER `osVersion` ;";
			$wpdb->query($sql);

			counterize_update_all_userAgents();
		}

	}



	//now we have version 3!



	//force to update the user agents table
	counterize_update_all_userAgents();

	// Set new Version
	update_option('counterize_MajorVersion', 3);
	update_option('counterize_MinorVersion', 0);
	update_option('counterize_Revision', 1);

}
?>
