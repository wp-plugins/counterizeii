<?php

# Small info on DashBoard-page
function counterize_dashboard()
{
  $admin = dirname($_SERVER['SCRIPT_FILENAME']);
  $admin = substr($admin, strrpos($admin, '/')+1);
	$count = counterize_getamount();
	$unique = counterize_getuniqueamount();
	$todaycount = counterize_gethitstoday();
	$online = counterize_get_online_users();
	$todayunique = counterize_getuniquehitstoday();
  if ($admin == 'wp-admin' && basename($_SERVER['SCRIPT_FILENAME']) == 'index.php')
  {
		$content = "<h3>" . _('Counterize II Status') . " <a href='edit.php?page=counterize/counterize.php'>&raquo;</a> </h3>";
		$content .= _('Total: ') . '<strong>' . $count . '</strong> ' . _('hits and ') . '<strong>' . $unique . '</strong>' . _(' unique.');
		$content .= "<p>". _('Today: ') . '<strong>' . $todaycount . '</strong> ' . _('hits and ') . '<strong>' . $todayunique . '</strong>' . _(' unique.') . "</p>";
		$content .= "<p>". _('Currently: ') . '<strong>' . $online . '</strong> ' . _(' users online.') . "</p>";

    print ' <script language="javascript" type="text/javascript"> var ele = document.getElementById("zeitgeist");
    if (ele)
    {
            var div = document.createElement("DIV");
            div.innerHTML = "'.$content.'";
            ele.appendChild(div);
    } </script> ';
	}
}

?>
