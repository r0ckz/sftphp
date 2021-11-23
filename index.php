<?php
# Tested with phpseclib1.0.19, which is a dependency. Unpack it, put this script in the phpseclib folder and run it from there.
# Other options without phpseclib include SSH2 for PHP (https://windows.php.net/downloads/pecl/releases/ssh2/ > php_ssh2-XXX-PHPVERSION-ts-x64.zip for XAMPP, put in php/ext and put or uncomment 'extension=php_ssh2.dll' in php.ini. Or 'sudo apt-get install libssh2–1-dev libssh2–1' for Debian-based systems). This is an extension rather than a library, but is much more of a hassle to setup and loop through. Especially detecting directories is why I chose phpseclib.
#
# Usage: http://localhost/index.php?user=USERNAME&pass=PASSWORD&ip=IPADDRESS&port=PORT
# Optional: &dir= to specify start dir. Defaults to '/'.
#
include('Net/SFTP.php');

$sftp = new Net_SFTP($_GET['ip'] . ':' . $_GET['port']); // We specify the login details with $_GET vars. Alternatively, we could do $_SESSION vars or hardcode the ip/port/username/pass in this script (by replacing $_GET['ip'] with '127.0.0.1' for example).
if (!$sftp->login($_GET['user'], $_GET['pass'])) {
    exit('SFTP connection could not be established.'); // If $_GET details are wrong.
}

if(!isset($_GET['dir'])){ $_GET['dir'] = ''; } // Set dir variable (empty) if not set, to prevent script errors. This will default to '/'.
$_SERVER['REQUEST_URI'] = str_replace('%20', ' ', $_SERVER['REQUEST_URI']); // Replace the %20 from URL in REQUEST_URI if folder name contains spaces, to prevent problems with links in these folders.
$urlwithoutdir = str_replace('&dir='.$_GET["dir"], '', $_SERVER['REQUEST_URI']); // We strip the dir from the url for later use to navigate to other dirs (back and forth).

// Page start
echo '<html>
<head>
	<title>Index of '.($_GET['dir'] == '' ? '/' : $_GET['dir']).'</title>
	<style>
		table, th, td, tr {border:0px;}
		table {width:500px;table-layout:fixed;}
		th, td {text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
	</style>
</head>
<body>
<h1>Index of '.($_GET['dir'] == '' ? '/' : $_GET['dir']).'</h1>
<pre><a href="'.$urlwithoutdir.'&dir='.str_replace('/'.basename($_GET['dir']), '', $_GET['dir']).'">[To Parent Directory]</a><br><br><table><tr><th colspan="3"><hr></th></tr><tr><th>Name</th><th>Size</th><th>Last modified</th></tr>'; // Table stuff and link to previous folder: strip the basename (current directory name) of the full $_GET['dir'] path.

if ($sftp->is_dir($_GET['dir'])) {
foreach($sftp->rawlist($_GET['dir']) as $filename => $file) {
    if ($file['type'] == NET_SFTP_TYPE_REGULAR) { // File? Link SFTP url, show filename, filesize and modified date.
        echo '<tr><td><a href="sftp://'.$_GET['user'].':'.$_GET['pass'].'@'.$_GET['ip'].':'.$_GET['port'].$_GET['dir'].'/'.$filename.'">' . $filename . '</a></td><td>' .$file['size'].'</td><td>'.date('d-m-Y H:i:s', $file['mtime']).'</td>';
    }
    if ($file['type'] == NET_SFTP_TYPE_DIRECTORY && $filename !== '.' && $filename !== '..') { // Directory? Link to browse, show filename and modified date.
        echo '<tr><td><a href="'.$urlwithoutdir.'&dir='.$_GET["dir"].'/'.$filename.'">' . $filename . '</a></td><td>-</td><td>'.date('d-m-Y H:i:s', $file['mtime']).'</td>';
    }
}
} else { echo '<td colspan="3">Directory not found.</th>'; }

echo '<tr><th colspan="3"><hr></th></tr></table></pre><address>Server at '.$_GET['ip'].' Port '.$_GET['port'].'</address></body></html>'; // Just for fun, not even a real FTP thing but based on what HTTP servers used to have on the bottom (and some still do).
?>