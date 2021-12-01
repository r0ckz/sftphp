<?php
# Tested with phpseclib1.0.19, which is a dependency. Unpack it, put this script in the phpseclib folder and run it from there.
# Other options without phpseclib include SSH2 for PHP (https://windows.php.net/downloads/pecl/releases/ssh2/ > php_ssh2-XXX-PHPVERSION-ts-x64.zip for XAMPP, put in php/ext and put or uncomment 'extension=php_ssh2.dll' in php.ini. Or 'sudo apt-get install libssh2–1-dev libssh2–1' for Debian-based systems). This is an extension rather than a library, but is much more of a hassle to setup and loop through. Especially detecting directories is why I chose phpseclib.
# Doesn't stream or serve files, only for browsing and getting SFTP links for use in applications like VLC - which was the purpose of this script. For playing or downloading files through the browser, there are enough alternatives or you can create an extra page to 'get' files with phpseclib and serve it over http. Change the url for files in the script and you're done.
#
# Usage: http://localhost/index.php?user=USERNAME&pass=PASSWORD&ip=IPADDRESS&port=PORT
# Optional: &dir= to specify start dir. Defaults to '/'.
#
include('Net/SFTP.php');

if (!isset($_GET['ip'], $_GET['port'], $_GET['user'], $_GET['pass'])) {
    exit('The connection settings (<i>host</i>, <i>port</i>, <i>username</i> and <i>password</i>) need to be specified or are invalid.<br>You can do that by adding the following parameters to the URL: <a href="?user=USERNAME&pass=PASSWORD&ip=IPADDRESS&port=PORT">?user=USERNAME&pass=PASSWORD&ip=IPADDRESS&port=PORT</a>'); // If required $_GET details are missing. Port will default to 22 when not or incorrectly specified, but the missing $_GET var would still cause PHP errors so instead of trying to catch that, we just require the $_GET var.
}

$sftp = new Net_SFTP($_GET['ip'] . ':' . $_GET['port']); // We specify the login details with $_GET vars. Alternatively, we could do $_SESSION vars or hardcode the ip/port/username/pass in this script (by replacing $_GET['ip'] with '127.0.0.1' for example).
if (!$sftp->login($_GET['user'], $_GET['pass'])) {
    exit('SFTP connection could not be established.'); // If one or more of the $_GET details are wrong. Alt: 'Unable to establish an SSH connection. Is the SSH server running?'
}

if(!isset($_GET['dir'])){ $_GET['dir'] = ''; } // Set dir variable (empty) if not set, to prevent script errors. This will default to '/'.
$_SERVER['REQUEST_URI'] = str_replace('%20', ' ', $_SERVER['REQUEST_URI']); // Replace the %20 from URL in REQUEST_URI if folder name contains spaces, to prevent problems with links in these folders.
$urlwithoutdir = str_replace('&dir='.$_GET['dir'], '', $_SERVER['REQUEST_URI']); // We strip the dir from the url for later use to navigate to other dirs (back and forth).
if($_GET['dir'] == '/'){ $_GET['dir'] = ''; } // Easy one-line solution to solve issues with '&dir=/', given that phpseclib defaults an unspecified path to '/'. We could change empty dir vars to '/' by default instead, but with the existing code that would mean the first '/' needs to be filtered out of the URLs and ONLY on the root page (because filenames aren't returned with a starting slash). Without that, it messes up the URLs and some other stuff. Easy to do but an unnecessary mess with "ifs" we don't strictly need, for getting the same result.

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
<pre>'.($_GET['dir'] == '' ? '' : '<a href="'.$urlwithoutdir.'&dir='.str_replace('/'.basename($_GET['dir']), '', $_GET['dir']).'">[To Parent Directory]</a><br><br>').'<table><tr><th colspan="3"><hr></th></tr><tr><th>Name</th><th>Size</th><th>Last modified</th></tr>'; // Table stuff and link to previous folder: strip the basename (current directory name) of the full $_GET['dir'] path.

if ($sftp->is_dir($_GET['dir'])) {
foreach($sftp->rawlist($_GET['dir']) as $filename => $file) {
    if ($file['type'] == NET_SFTP_TYPE_REGULAR) { // File? Link SFTP url, show filename, filesize and modified date.
        echo '<tr><td><a href="sftp://'.$_GET['user'].':'.$_GET['pass'].'@'.$_GET['ip'].':'.$_GET['port'].$_GET['dir'].'/'.$filename.'">' . $filename . '</a></td><td>' .$file['size'].'</td><td>'.date('d-m-Y H:i:s', $file['mtime']).'</td>';
    }
    if ($file['type'] == NET_SFTP_TYPE_DIRECTORY && $filename !== '.' && $filename !== '..') { // Directory? Link to browse, show filename and modified date. Hide . and .. because these aren't real folders and we already have a backlink to the previous folder (links to . and .. don't go back).
        echo '<tr><td><a href="'.$urlwithoutdir.'&dir='.$_GET['dir'].'/'.$filename.'">' . $filename . '</a></td><td>-</td><td>'.date('d-m-Y H:i:s', $file['mtime']).'</td>';
    }
}
} else { echo '<td colspan="3">Directory not found.</th>'; }

echo '<tr><th colspan="3"><hr></th></tr></table></pre><address>Server at '.$_GET['ip'].' Port '.$_GET['port'].'</address></body></html>'; // Just for fun, not even a real FTP thing but based on what HTTP servers used to have on the bottom (and some still do).
?>