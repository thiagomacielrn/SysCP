<?php
/**
 * filename: $Source$
 * begin: Friday, Aug 13, 2004
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version. This program is distributed in the
 * hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * Most elements are taken from the phpBB (www.phpbb.com)
 * installer, (c) 1999 - 2004 phpBB Group.
 *
 * @author Florian Lippert <flo@redenswert.de>
 * @copyright (C) 2003-2004 Florian Lippert
 * @package Panel
 * @version $Id$
 */

	if(file_exists('../lib/userdata.inc.php'))
	{
		die('Sorry, SysCP is already configured...');
	}

	/**
	 * Include the functions
	 */
	require('../lib/functions.php');

	/**
	 * Include the MySQL-Connection-Class
	 */
	require('../lib/class_mysqldb.php');

	/**
	 * Include the MySQL-Table-Definitions
	 */
	require('../lib/tables.inc.php');

	/**
	 * BEGIN FUNCTIONS -----------------------------------------------
	 */

	function page_header()
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>SysCP Installation</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
<link href="../style/main.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#ffffff">
<!--
	We request you retain the full copyright notice below including the link to www.syscp.de.
	This not only gives respect to the large amount of time given freely by the developers
	but also helps build interest, traffic and use of SysCP. If you refuse
	to include even this then support on our forums may be affected.
	Florian Lippert : 2004
// -->
<table width="750" border="0" cellspacing="0" cellpadding="0"> 
  <tr> 
    <td colspan="3"><img src="../images/syscp_top_left.gif" width="20" height="131"><img src="../images/syscp_top_right.jpg" width="730" height="131" border="0" usemap="#Map"></td> 
  </tr> 
  <tr> 
    <td width="1" valign="top" background="../images/syscp_content_line_vt.gif"> 
    <td width="748" valign="top" bgcolor="#F0F0F0">
     <table width="100%"  border="0" cellspacing="0" cellpadding="4"> 
      <tr> 
       <td>
<?php
	}

	function page_footer()
	{
?>
       </td> 
      </tr> 
     </table> 
    </td> 
    <td width="1" background="../images/syscp_content_line_vt.gif"></td> 
  </tr> 
  <tr> 
   <td height="1" colspan="3" valign="bottom" background="../images/syscp_content_line_hz.gif"></td> 
  </tr> 
</table> 
<map name="Map"> 
  <area shape="rect" coords="535,101,724,117" href="http://www.syscp.de" target="_blank" alt="SysCP.de"> 
</map> 
</body>
</html>
<?php
	}

	function status_message($case, $text)
	{
		if($case == 'begin')
		{
			echo "\t\t<tr>\n\t\t\t<td class=\"maintable\">$text";
		}
		else
		{
			echo " <span style=\"color:$case;\">$text</span></td>\n\t\t</tr>\n";
		}
	}
	
	//
	// remove_remarks will strip the sql comment lines out of an uploaded sql file
	//
	function remove_remarks($sql)
	{
		$lines = explode("\n", $sql);

		// try to keep mem. use down
		$sql = "";

		$linecount = count($lines);
		$output = "";

		for ($i = 0; $i < $linecount; $i++)
		{
			if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0))
			{
				if (substr($lines[$i], 0, 1) != "#")
				{
					$output .= $lines[$i] . "\n";
				}
				else
				{
					$output .= "\n";
				}
				// Trading a bit of speed for lower mem. use here.
				$lines[$i] = "";
			}
		}
		return $output;
	}

	//
	// split_sql_file will split an uploaded sql file into single sql statements.
	// Note: expects trim() to have already been run on $sql.
	//
	function split_sql_file($sql, $delimiter)
	{
		// Split up our string into "possible" SQL statements.
		$tokens = explode($delimiter, $sql);

		// try to save mem.
		$sql = "";
		$output = array();

		// we don't actually care about the matches preg gives us.
		$matches = array();

		// this is faster than calling count($oktens) every time thru the loop.
		$token_count = count($tokens);
		for ($i = 0; $i < $token_count; $i++)
		{
			// Don't wanna add an empty string as the last thing in the array.
			if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
			{
				// This is the total number of single quotes in the token.
				$total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
				// Counts single quotes that are preceded by an odd number of backslashes, 
				// which means they're escaped quotes.
				$escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

				$unescaped_quotes = $total_quotes - $escaped_quotes;
				
				// If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
				if (($unescaped_quotes % 2) == 0)
				{
					// It's a complete sql statement.
					$output[] = $tokens[$i];
					// save memory.
					$tokens[$i] = "";
				}
				else
				{
				// incomplete sql statement. keep adding tokens until we have a complete one.
					// $temp will hold what we have so far.
					$temp = $tokens[$i] . $delimiter;
					// save memory..
					$tokens[$i] = "";
					
					// Do we have a complete statement yet? 
					$complete_stmt = false;
					
					for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
					{
						// This is the total number of single quotes in the token.
						$total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
						// Counts single quotes that are preceded by an odd number of backslashes, 
						// which means they're escaped quotes.
						$escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

						$unescaped_quotes = $total_quotes - $escaped_quotes;
						
						if (($unescaped_quotes % 2) == 1)
						{
							// odd number of unescaped quotes. In combination with the previous incomplete
							// statement(s), we now have a complete statement. (2 odds always make an even)
							$output[] = $temp . $tokens[$j];

							// save memory.
							$tokens[$j] = "";
							$temp = "";
							
							// exit the loop.
							$complete_stmt = true;
							// make sure the outer loop continues at the right point.
							$i = $j;
						}
						else
						{
							// even number of unescaped quotes. We still don't have a complete statement. 
							// (1 odd and 1 even always make an odd)
							$temp .= $tokens[$j] . $delimiter;
							// save memory.
							$tokens[$j] = "";
						}
						
					} // for..
				} // else
			}
		}
		return $output;
	}

	/**
	 * END FUNCTIONS ---------------------------------------------------
	 */



	/**
	 * BEGIN VARIABLES ---------------------------------------------------
	 */

	//guess Servername
	if(!empty($_POST['servername']))
	{
		$servername = addslashes($_POST['servername']);
	}
	else
	{
		if(!empty($_SERVER['SERVER_NAME']))
		{
			$servername = addslashes($_SERVER['SERVER_NAME']);
		}
		else
		{
			$servername = '';
		}
	}

	//guess serverip
	if(!empty($_POST['serverip']))
	{
		$serverip = addslashes($_POST['serverip']);
	}
	else
	{
		if(!empty($_SERVER['SERVER_ADDR']))
		{
			$serverip = addslashes($_SERVER['SERVER_ADDR']);
		}
		else
		{
			$serverip = '';
		}
	}

	if(!empty($_POST['mysql_host']))
	{
		$mysql_host = addslashes($_POST['mysql_host']);
	}
	else
	{
		$mysql_host = 'localhost';
	}

	if(!empty($_POST['mysql_database']))
	{
		$mysql_database = addslashes($_POST['mysql_database']);
	}
	else
	{
		$mysql_database = 'syscp';
	}

	if(!empty($_POST['mysql_unpriv_user']))
	{
		$mysql_unpriv_user = addslashes($_POST['mysql_unpriv_user']);
	}
	else
	{
		$mysql_unpriv_user = 'syscp';
	}

	if(!empty($_POST['mysql_unpriv_pass']))
	{
		$mysql_unpriv_pass = addslashes($_POST['mysql_unpriv_pass']);
	}
	else
	{
		$mysql_unpriv_pass = '';
	}

	if(!empty($_POST['mysql_root_user']))
	{
		$mysql_root_user = addslashes($_POST['mysql_root_user']);
	}
	else
	{
		$mysql_root_user = 'root';
	}

	if(!empty($_POST['mysql_root_pass']))
	{
		$mysql_root_pass = addslashes($_POST['mysql_root_pass']);
	}
	else
	{
		$mysql_root_pass = '';
	}

	if(!empty($_POST['admin_user']))
	{
		$admin_user = addslashes($_POST['admin_user']);
	}
	else
	{
		$admin_user = 'admin';
	}

	if(!empty($_POST['admin_pass1']))
	{
		$admin_pass1 = addslashes($_POST['admin_pass1']);
	}
	else
	{
		$admin_pass1 = '';
	}

	if(!empty($_POST['admin_pass2']))
	{
		$admin_pass2 = addslashes($_POST['admin_pass2']);
	}
	else
	{
		$admin_pass2 = '';
	}

	if(!empty($_POST['documentroot_prefix']))
	{
		$documentroot_prefix = makeCorrectDir(addslashes($_POST['documentroot_prefix']));
	}
	else
	{
		$documentroot_prefix = '/var/kunden/webs/';
	}

	if(!empty($_POST['logfiles_directory']))
	{
		$logfiles_directory = makeCorrectDir(addslashes($_POST['logfiles_directory']));
	}
	else
	{
		$logfiles_directory = '/var/kunden/logs/';
	}

	if(!empty($_POST['mailsdir']))
	{
		$mailsdir = makeCorrectDir(addslashes($_POST['mailsdir']));
	}
	else
	{
		$mailsdir = '/var/kunden/mail/';
	}

	if(!empty($_POST['mails_uid']))
	{
		$mails_uid = addslashes($_POST['mails_uid']);
	}
	else
	{
		$mails_uid = '2000';
	}

	if(!empty($_POST['mails_gid']))
	{
		$mails_gid = addslashes($_POST['mails_gid']);
	}
	else
	{
		$mails_gid = '2000';
	}

	if(!empty($_POST['apache_configdir']))
	{
		$apache_configdir = makeCorrectDir(addslashes($_POST['apache_configdir']));
	}
	else
	{
		$apache_configdir = '/etc/apache/';
	}

	if(!empty($_POST['apache_reloadcommand']))
	{
		$apache_reloadcommand = addslashes($_POST['apache_reloadcommand']);
	}
	else
	{
		$apache_reloadcommand = '/etc/init.d/apache reload';
	}

	if(!empty($_POST['bind_configdir']))
	{
		$bind_configdir = makeCorrectDir(addslashes($_POST['bind_configdir']));
	}
	else
	{
		$bind_configdir = '/etc/bind/';
	}

	if(!empty($_POST['bind_reloadcommand']))
	{
		$bind_reloadcommand = addslashes($_POST['bind_reloadcommand']);
	}
	else
	{
		$bind_reloadcommand = '/etc/init.d/bind9 reload';
	}

	if(!empty($_POST['bind_defaultzone']))
	{
		$bind_defaultzone = addslashes($_POST['bind_defaultzone']);
	}
	else
	{
		$bind_defaultzone = 'default.zone';
	}

	if(!empty($_POST['accountprefix']))
	{
		$accountprefix = addslashes($_POST['accountprefix']);
	}
	else
	{
		$accountprefix = 'web';
	}

	if(!empty($_POST['catchallkeyword']))
	{
		$catchallkeyword = addslashes($_POST['catchallkeyword']);
	}
	else
	{
		$catchallkeyword = 'catchall';
	}

	if(!empty($_POST['ftpprefix']))
	{
		$ftpprefix = addslashes($_POST['ftpprefix']);
	}
	else
	{
		$ftpprefix = 'ftp';
	}

	if(!empty($_POST['sqlprefix']))
	{
		$sqlprefix = addslashes($_POST['sqlprefix']);
	}
	else
	{
		$sqlprefix = 'sql';
	}

	if(!empty($_POST['documentrootstyle']))
	{
		$documentrootstyle = addslashes($_POST['documentrootstyle']);
		if($documentrootstyle != 'domain')
		{
			$documentrootstyle = 'customer';
		}
	}
	else
	{
		$documentrootstyle = 'customer';
	}

	if(!empty($_POST['loginnamestyle']))
	{
		$loginnamestyle = addslashes($_POST['loginnamestyle']);
		if($loginnamestyle != 'dynamic')
		{
			$loginnamestyle = 'static';
		}
	}
	else
	{
		$loginnamestyle = 'static';
	}

	/**
	 * END VARIABLES ---------------------------------------------------
	 */




	/**
	 * BEGIN INSTALL ---------------------------------------------------
	 */

	if(isset($_POST['installstep']) && $_POST['installstep'] == '1' && $admin_pass1 == $admin_pass2 && $admin_pass1 != '' && $admin_pass2 != '' && $mysql_unpriv_pass != '' && $mysql_root_pass != '' && $servername != '' && $serverip != '')
	{
		page_header();
?>
	<table celllpadding="5" cellspacing="0" border="0" align="center" class="maintable">
		<tr>
			<td class="maintable" align="center" style="font-size: 18pt;">SysCP Installation</td>
		</tr>
<?php
		//first test if we can access the database server with the given root user and password
		status_message('begin', 'Teste, ob die MySQL-Root-Benutzerdaten richtig sind...');
		$db_root = new db($mysql_host, $mysql_root_user, $mysql_root_pass, '');
		//ok, if we are here, the database class is build up (otherwise it would have already die'd this script)
		status_message('green', 'OK');

		//so first we have to delete the database and the user given for the unpriv-user if they exit
		status_message('begin', 'Entferne alte Datenbank...');
		$db_root->query("DELETE FROM `mysql`.`user` WHERE `User` = '$mysql_unpriv_user' AND `Host` = '$mysql_host';");
		$db_root->query("DELETE FROM `mysql`.`db` WHERE `User` = '$mysql_unpriv_user' AND `Host` = '$mysql_host';");
		$db_root->query("DELETE FROM `mysql`.`tables_priv` WHERE `User` = '$mysql_unpriv_user' AND `Host` = '$mysql_host';");
		$db_root->query("DELETE FROM `mysql`.`columns_priv` WHERE `User` = '$mysql_unpriv_user' AND `Host` = '$mysql_host';");
		$db_root->query("DROP DATABASE IF EXISTS `$mysql_database` ;");
		$db_root->query("FLUSH PRIVILEGES;");
		status_message('green', 'OK');

		//then we have to create a new user and database for the syscp unprivileged mysql access
		status_message('begin', 'Erstelle Datenbank und Benutzer...');
		$db_root->query("CREATE DATABASE `$mysql_database`;");
		$db_root->query("GRANT ALL PRIVILEGES ON `$mysql_database`.* TO $mysql_unpriv_user@$mysql_host IDENTIFIED BY 'password';");
		$db_root->query("SET PASSWORD FOR $mysql_unpriv_user@$mysql_host = PASSWORD('$mysql_unpriv_pass');");
		$db_root->query("FLUSH PRIVILEGES;");
		status_message('green', 'OK');

		//now a new database and the new syscp-unprivileged-mysql-account have been created and we can fill it now with the data.
		status_message('begin', 'Teste, ob die Datenbank und Passwort korrekt angelegt wurden...');
		$db = new db($mysql_host, $mysql_unpriv_user, $mysql_unpriv_pass, $mysql_database);
		status_message('green', 'OK');

		status_message('begin', 'Importiere Daten in die MySQL-Datenbank...');
		$db_schema = './syscp.sql';
		$sql_query = @fread(@fopen($db_schema, 'r'), @filesize($db_schema));
		$sql_query = remove_remarks($sql_query);
		$sql_query = split_sql_file($sql_query, ';');

		for ($i = 0; $i < sizeof($sql_query); $i++)
		{
			if (trim($sql_query[$i]) != '')
			{
				$result = $db->query($sql_query[$i]);
			}
		}
		status_message('green', 'OK');

		//now let's chenage the settings in our settings-table
		status_message('begin', 'Passe importierten die Daten an...');
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = 'admin@$servername' WHERE `settinggroup` = 'panel' AND `varname` = 'adminmail'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = 'http://$servername/phpmyadmin' WHERE `settinggroup` = 'panel' AND `varname` = 'phpmyadmin_url'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$accountprefix' WHERE `settinggroup` = 'customer' AND `varname` = 'accountprefix'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$catchallkeyword' WHERE `settinggroup` = 'email' AND `varname` = 'catchallkeyword'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$ftpprefix' WHERE `settinggroup` = 'customer' AND `varname` = 'ftpprefix'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$sqlprefix' WHERE `settinggroup` = 'customer' AND `varname` = 'mysqlprefix'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$documentroot_prefix' WHERE `settinggroup` = 'system' AND `varname` = 'documentroot_prefix'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$logfiles_directory' WHERE `settinggroup` = 'system' AND `varname` = 'logfiles_directory'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$serverip' WHERE `settinggroup` = 'system' AND `varname` = 'ipaddress'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$apache_configdir' WHERE `settinggroup` = 'system' AND `varname` = 'apacheconf_directory'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$apache_reloadcommand' WHERE `settinggroup` = 'system' AND `varname` = 'apachereload_command'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$bind_configdir' WHERE `settinggroup` = 'system' AND `varname` = 'bindconf_directory'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$bind_reloadcommand' WHERE `settinggroup` = 'system' AND `varname` = 'bindreload_command'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$bind_defaultzone' WHERE `settinggroup` = 'system' AND `varname` = 'binddefaultzone'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$servername' WHERE `settinggroup` = 'system' AND `varname` = 'hostname'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$mailsdir' WHERE `settinggroup` = 'system' AND `varname` = 'vmail_homedir'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$mails_uid' WHERE `settinggroup` = 'system' AND `varname` = 'vmail_uid'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$mails_gid' WHERE `settinggroup` = 'system' AND `varname` = 'vmail_gid'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$loginnamestyle' WHERE `settinggroup` = 'panel' AND `varname` = 'loginnamestyle'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$documentrootstyle' WHERE `settinggroup` = 'system' AND `varname` = 'documentrootstyle'");
		$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value` = '$version' WHERE `settinggroup` = 'panel' AND `varname` = 'version'");
		status_message('green', 'OK');

		//last but not least create the main admin
		status_message('begin', 'F&uuml;ge den Admin-Benutzer hinzu...');
		$db->query("INSERT INTO `".TABLE_PANEL_ADMINS."` (`loginname`, `password`, `name`, `email`, `customers`, `customers_used`, `customers_see_all`, `domains`, `domains_used`, `domains_see_all`, `change_serversettings`, `diskspace`, `diskspace_used`, `mysqls`, `mysqls_used`, `emails`, `emails_used`, `email_forwarders`, `email_forwarders_used`, `ftps`, `ftps_used`, `subdomains`, `subdomains_used`, `traffic`, `traffic_used`, `deactivated`) VALUES ('admin', '".md5($admin_pass1)."', 'Siteadmin', 'admin@$servername', -1, 0, 1, -1, 0, 1, 1, -1024, 0, -1, 0, -1, 0, -1, 0, -1, 0, -1, 0, -1048576, 0, 0);");
		status_message('green', 'OK');

		//now we create the userdata.inc.php with the mysql-accounts
		status_message('begin', 'Erstelle Konfigurationsdatei...');
		$userdata="<?php\n";
		$userdata.="//automatically generated userdata.inc.php for SysCP\n";
		$userdata.="\$sql['host']='$mysql_host';\n";
		$userdata.="\$sql['user']='$mysql_unpriv_user';\n";
		$userdata.="\$sql['password']='$mysql_unpriv_pass';\n";
		$userdata.="\$sql['db']='$mysql_database';\n";
		$userdata.="\$sql['root_user']='$mysql_root_user';\n";
		$userdata.="\$sql['root_password']='$mysql_root_pass';\n";
		$userdata.="?>";

		//we test now if we can store the userdata.inc.php in ../lib
		if($fp = @fopen('../lib/userdata.inc.php', 'w'))
		{
			$result = @fputs($fp, $userdata, strlen($userdata));
			@fclose($fp);
			status_message('green', 'OK, userdata.inc.php wurde in lib/ gespeichert.');
			chmod('../lib/userdata.inc.php', 0440);
		}
		elseif($fp = @fopen('/tmp/userdata.inc.php', 'w'))
		{
			$result = @fputs($fp, $userdata, strlen($userdata));
			@fclose($fp);
			status_message('orange', 'Datei wurde in /tmp/userdata.inc.php gespeichert, bitte nach lib/ verschieben.');
			chmod('/tmp/userdata.inc.php', 0440);
		}
		else
		{
			status_message('red', 'Konnte lib/userdata.inc.php nicht erstellen, bitte manuell mit folgendem Inhalt anlegen:');
			echo "\t\t<tr>\n\t\t\t<td class=\"maintable\"><p style=\" margin-left:150px;  margin-right:150px; padding: 9px; border:1px solid #999;\">".nl2br(htmlspecialchars($userdata))."</p></td>\n\t\t</tr>\n";
		}
?>
		<tr>
			<td class="maintable" align="center"><br />SysCP wurde erfolgreich installiert.<br /><a href="../index.php">Hier geht es weiter zum Login-Fenster.</a></td>
		</tr>
	</table><br />
<?php
		page_footer();
	}
	else
	{
		page_header();
?>
	<table celllpadding="5" cellspacing="0" border="0" align="center" class="maintable">
		<tr>
			<td class="maintable" align="center" style="font-size: 18pt;">Willkommen zur SysCP Installation</td>
		</tr>
		<tr>
			<td class="maintable">Vielen Dank dass Sie sich f&uuml;r SysCP entschieden haben. Um Ihre Installation von SysCP zu starten, f&uuml;llen Sie bitte alle Felder unten mit den geforderten Angaben. <b>Hinweis:</b> Eine eventuell bereits existierende Datenbank, die den selben Namen hat wie den, den Sie unten eingeben werden, wird mit allen enthaltenen Daten gel&ouml;scht!</td>
		</tr>
	</table><br />
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
	<table celllpadding="3" cellspacing="1" border="0" align="center" class="maintable">
		<tr>
		 <td class="maintable" colspan="2" align="center" style="font-size: 15px; padding-top: 3px;"><b>Datenbank</b></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">MySQL-Hostname:</td>
		 <td class="maintable"><input type="text" name="mysql_host" value="<?php echo $mysql_host; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">MySQL-Datenbank:</td>
		 <td class="maintable"><input type="text" name="mysql_database" value="<?php echo $mysql_database; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Benutzername f&uuml;r den unpreviligierten MySQL-Account:</td>
		 <td class="maintable"><input type="text" name="mysql_unpriv_user" value="<?php echo $mysql_unpriv_user; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && $mysql_unpriv_pass == '') ? ' style="color:red;"' : ''); ?>>Passwort f&uuml;r den unpreviligierten MySQL-Account:</td>
		 <td class="maintable"><input type="password" name="mysql_unpriv_pass" value="<?php echo $mysql_unpriv_pass; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Benutzername f&uuml;r den MySQL-Root-Account:</td>
		 <td class="maintable"><input type="text" name="mysql_root_user" value="<?php echo $mysql_root_user; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && $mysql_root_pass == '') ? ' style="color:red;"' : ''); ?>>Passwort f&uuml;r den MySQL-Root-Account:</td>
		 <td class="maintable"><input type="password" name="mysql_root_pass" value="<?php echo $mysql_root_pass; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" colspan="2" align="center" style="font-size: 15px; padding-top: 7px;"><b>Admin-Zugang</b></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Administrator-Benutzername:</td>
		 <td class="maintable"><input type="text" name="admin_user" value="<?php echo $admin_user; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && ($admin_pass1 == '' || $admin_pass1 != $admin_pass2)) ? ' style="color:red;"' : ''); ?>>Administrator-Passwort:</td>
		 <td class="maintable"><input type="password" name="admin_pass1" value="<?php echo $admin_pass1; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && ($admin_pass2 == '' || $admin_pass1 != $admin_pass2)) ? ' style="color:red;"' : ''); ?>>Administrator-Passwort (Best&auml;tigung):</td>
		 <td class="maintable"><input type="password" name="admin_pass2" value="<?php echo $admin_pass2; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" colspan="2" align="center" style="font-size: 15px; padding-top: 7px;"><b>Servereinstellungen</b></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && $servername == '') ? ' style="color:red;"' : ''); ?>>Servername (FQDN):</td>
		 <td class="maintable"><input type="text" name="servername" value="<?php echo $servername; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right"<?php echo ((!empty($_POST['installstep']) && $serverip == '') ? ' style="color:red;"' : ''); ?>>Serverip:</td>
		 <td class="maintable"><input type="text" name="serverip" value="<?php echo $serverip; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Documentdir aller Kunden:</td>
		 <td class="maintable"><input type="text" name="documentroot_prefix" value="<?php echo $documentroot_prefix; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Logfile-Verzeichnis des Apache:</td>
		 <td class="maintable"><input type="text" name="logfiles_directory" value="<?php echo $logfiles_directory; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Mailsdir des Postfix/Courier:</td>
		 <td class="maintable"><input type="text" name="mailsdir" value="<?php echo $mailsdir; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Mails-UserID:</td>
		 <td class="maintable"><input type="text" name="mails_uid" value="<?php echo $mails_uid; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Mails-GroupID:</td>
		 <td class="maintable"><input type="text" name="mails_gid" value="<?php echo $mails_gid; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Apache-Configdir:</td>
		 <td class="maintable"><input type="text" name="apache_configdir" value="<?php echo $apache_configdir; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Apache-Reloadcommand:</td>
		 <td class="maintable"><input type="text" name="apache_reloadcommand" value="<?php echo $apache_reloadcommand; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Bind-Configdir:</td>
		 <td class="maintable"><input type="text" name="bind_configdir" value="<?php echo $bind_configdir; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Bind-Reloadcommand:</td>
		 <td class="maintable"><input type="text" name="bind_reloadcommand" value="<?php echo $bind_reloadcommand; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Bind-Defaultzone:</td>
		 <td class="maintable"><input type="text" name="bind_defaultzone" value="<?php echo $bind_defaultzone; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Kundenprefix:</td>
		 <td class="maintable"><input type="text" name="accountprefix" value="<?php echo $accountprefix; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">FTP-Prefix:</td>
		 <td class="maintable"><input type="text" name="ftpprefix" value="<?php echo $ftpprefix; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">SQL-Prefix:</td>
		 <td class="maintable"><input type="text" name="sqlprefix" value="<?php echo $sqlprefix; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Catchall-Keyword:</td>
		 <td class="maintable"><input type="text" name="catchallkeyword" value="<?php echo $catchallkeyword; ?>"></td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Documentroot-Style:</td>
		 <td class="maintable"><input type="radio" name="documentrootstyle" value="customer" <?php echo ($documentrootstyle == 'customer' ? 'checked' : '') ?>> customer &nbsp; <input type="radio" name="documentrootstyle" value="domain" <?php echo ($documentrootstyle == 'domain' ? 'checked' : '') ?>> domain </td>
		</tr>
		<tr>
		 <td class="maintable" align="right">Loginname-Style:</td>
		 <td class="maintable"><input type="radio" name="loginnamestyle" value="static" <?php echo ($loginnamestyle == 'static' ? 'checked' : '') ?>> static &nbsp; <input type="radio" name="loginnamestyle" value="dynamic" <?php echo ($loginnamestyle == 'dynamic' ? 'checked' : '') ?>> dynamic </td>
		</tr>
		<tr>
		 <td class="maintable" align="right" colspan="2" style=" padding-top: 10px;"><input type="hidden" name="installstep" value="1"><input type="submit" name="submitbutton" value="Fortfahren"></td>
		</tr>
	</table>
	</form><br />
<?php
		page_footer();
	}

	/**
	 * END INSTALL ---------------------------------------------------
	 */

?>