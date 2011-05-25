=== Counterize II ===
Contributors: Gabriel Hautclocq, Steffen Forkmann
Donate link: http://www.gabsoftware.com/donate/
Tags: statistics, admin, stats, counter, browser, os, operating, system
Requires at least: 3.0.0
Tested up to: 3.1.2
Stable tag: 3.0.1

== Description ==

<p>Simple counter-plugin with no external libs based on Counterize II by Steffen Forkmann
(<a href="http://www.navision-blog.de/counterize">http://www.navision-blog.de/counterize</a>)</p>
<p>Saves timestamp, visited URl, referring URl and browserinformation in database,
and can display total hits, unique hits and other statistics in WordPress webpages.</p>
<p>Admin-interface available with detailed information.</p>
<p><b>Counterize II 2.13 doesn't store any IP information, because this is illegal in some countries (e.g. Germany).
Counterize stores only a small hash to distingiush between different users and to get information about the user count.</b></p>
<p>External stats page to display in blog posts - visit the <a href="http://www.gabsoftware.com/products/scripts/counterize/">sample</a>.</p>
<p>Version 3.0.0 supports Wordpress 3.x.x.</p>
<p><strong>Important note: </strong>The next versions of Counterize will be available at <a href="http://wordpress.org/extend/plugins/counterize/">sample</a></p>

== Installation ==

1. Unzip the package and upload the folder **counterizeii** to your **/wp-content/plugins/** folder.
1. Activate the plugin through the **Plugins** menu in WordPress.
1. Go to **Counterize II** on **Options** page, configurate your settings. *Save the settings*

== Update ==

1. Deactive **Counterize II** on the **Plugins** menu in WordPress
1. Unzip the package and upload the folder **counterizeii** to **/wp-content/plugins/**.
1. Activate the plugin through the **Plugins** menu in WordPress.
1. Go to **Counterize II** on **Options** page, configurate your settings. *Save the settings*

== Functions ==

After you have installed the Counterize II Plugin, you can see a lot of diagrams on the Counterize II stats page (Dashboard/Counterize II).

Most likely you'd like to have a counter somewhere on your pages, showing XX hits or something like that. Here's an overview of the functions which can be used in your Wordpress sidebar.

New: &lt;!-- counterize_stats --&gt; - Shows a complete stats page in one of your blog posts.

echo counterize_getuniqueurl() : Returns amount of unique URl's that have been shown

echo counterize_getamount() : Returns total hits seen by Counterize.

echo counterize_gethitstoday() : Returns total hits registered today.

echo counterize_getuniquebrowsers() : Returns amount of unique browser-strings that have visited.

echo counterize_getuniquereferers() : Returns amount of unique referers that's registered.

echo counterize_getlatest7days() : Returns total amount of hits from the last 7 days.

echo counterize_getuniqueamount() : Returns total unique IP's that's been seen.

echo counterize_getfromcurrentip() : Returns total hits from the IP that's visiting.

echo counterize_getuniquehitstoday() : Returns the number of different IP's registered today.

echo counterize_gethitstodayfromcurrentip() : Returns daily hits from the IP that's visiting.

echo counterize_return_first_hit() : Returns the date of the first registrated entry in the database.

counterize_most_visited_pages() : Create graph of most visited pages.

counterize_most_visited_ips() : Create graph of most active IPs.

counterize_most_used_browsers() : Create graph of most seen useragents.

counterize_most_used_browsers_without_version() : Create graph of most seen useragents without version.

== Changelog ==

= 3.0.1 =
* Corrected installation issue. This should be the last update by me for Counterize II.

= 3.0.0 =
* Development is now continued by Gabriel Hautclocq (me)
* Chrome is now recognized as well as plenty of other browsers
* Newer versions of Windows recognized
* Many other OS have also been added
* Updated the bot exclude list
* Many OS version added
* Updated some old icons
* Several other improvements have been made to browsniff.php
* Added a button in the admin interface to refresh the user-agent table (useful if you modify browsniff.php yourself)
* Distinction between 32 and 64 bits versions of the OS, as well as ARM and PowerPC  versions
* Browsers and OS charts now display a link to the product
* Fixed garbage alt attribute of the chart bars
* Cleaner PHP code
* Cleaner code output (indentation...)
* Wordpress 3.x compliant code
* Wordpress 3.x compliant Readme
* Unfortunately, most translations should to be updated to reflect the changes. Please allow some time to the translation authors to update it.

= 2.14.1 =
* German translation corrected (thanks to Johannes Parb)

= 2.14.0 =
* Dashboard for WP 2.5

= 2.13.0 =
* no more ip-logging (this is illegal in some countries e.g. Germany)
 counterize stores only a small hash to distingiush between two users
 and to get information about the user count

= 2.12.7 =
* logging postID for later analysis (thanks to Alfred likemind.co.uk)

= 2.12.6 =
* counterize_get_online_users() fixed (thanks to Robbix wombat-tour.de)

= 2.12.4 =
* 2 new functions (thanks to KnickerBlogger.net)

= 2.12.3 =
* fixed installtion

= 2.12.1 / 2.12.2 =
* fixed folder

= 2.12 =
* lots of sql fixing (thanks to Eric blogs.jobdig.com/wwds)

= 2.11 =
* XHTML valid (thanks to Julia http://julialoeba.de)
* Japanese version (thanks to Urepko http://wppluginsj.sourceforge.jp/)
* exclude new bots (thanks to lacroa.altervista.org - Emanuele)
* Exclude if referer is the same as url (thanks to lacroa.altervista.org - Emanuele)
* GeoIpTool with new URL geoiptool.com/en/ ==> geoiptool.com/ (thanks to lacroa.altervista.org - Emanuele)
* Mass-Deletion (thanks to lacroa.altervista.org - Emanuele)
* Bot Yandex added
* Spanish translation (thanks to Sandra)
* Database-flush deletes all keywords

= 2.10 =
* Italian version (thanks to Emanuele)
* Russian version (thanks to Ivan http://shadow-blub.livejournal.com/)
* Small installation bugfixes (thanks to Thorsten http://www.siteartwork.de/)

= 2.08 =
* %-Bug fixed (% in graphs on new line)
* max-Width in Settings (width-fix for firefox)
* users online
* browser icons
* operating systems

= 2.06 =
* Minor compatibility bugfixes for Wordpress 2.2

= 2.05 =
* small Bugfixes
* All TableNames replaced with function calls
* LocalizationFramework used
* DayOfWeek-Bug fixed
* display only EXTERNAL referers (Thanks to eric)
* extremly speed up sql queries
* stats moved back to Manage-Page (only for admins visible)
* colors in all stats
* separate statistics page for blog entries
* Microsoft URL Control-bot excluded

= 2.04 =
* custom whois-server
* moved all files to own folder
* separate settings/admin page
* stats moved to doashboard-subpage
* ref-analyzer (http://nopaste.easy-coding.de/?id=146) for keywords
* keyword-stats (alpha)
* mysql-version independent table structure
* don't show stats, when db is empty (DivByZero)

= 2.03 =
* Installprocess for mysql version 4
* Filter and MapView for most visited ips

= 2.02 =
* BugFix - UserAgent

= 2.01 =
* New Author: Steffen Forkmann
* New Table Structure (saves a lot of space and mostly time)
* Redesigning some functions

= 0.53 =
* By mistake (during test) commented out the exclude-function. Once again re-enabled.
* Added the feature "top referers" in the admin-interface
* Added the feature to manually select amount to show in the bar-graphs, instead of default 15
* Minor stuff, changed text, thicker bars in graphs, now "unique hits last 7 days" also shown in admin-interface,

= 0.52 =
* More bots
* New function called counterize_getuniquelatest7days() (Curtis)
* Exclusion of most common images and RSS feeds (SHRIKEE)
* Don't use the now() insert call when inserting entries. Use gmdate() instead
