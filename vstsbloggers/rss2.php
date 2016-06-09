<?php
header("Content-Type: application/rss+xml; charset=utf-8");
//error_reporting(E_ALL);

include('bao-rss.php');
// Make sure no errors will be shown.
include('bao-rss.php');
include('bao-rss.php');
include('bao-rss.php');


/*************************************************************/
/* BaoRSSMix Demo */
$rss_cache_path = "/tmp"; // set this to a writable directory to store the cache files in

$mix = new BaoRSSMix();
$mix->setCacheExpiry(30);
$mix->setCacheEnabled(true);
$mix->setCacheHandlers('cacheGet', 'cacheSet');
$mix->mix("http://blogs.msdn.com/robcaron/rss.aspx");
$mix->mix("http://teamsystemrocks.com/blogs/team_system_news/rss.aspx");
$mix->mix("http://teamsystemrocks.com/blogs/mickey_gousset/rss.aspx");
$mix->mix("http://blogs.vertigosoftware.com/teamsystem/rss.aspx");
$mix->mix("http://notgartner.com/Rss.aspx");
$mix->mix("http://blogs.msdn.com/elyasse/rss.aspx");
$mix->mix("http://blogs.msdn.com/buckh/rss.aspx");
$mix->mix("http://blogs.msdn.com/csell/rss.aspx");
$mix->mix("http://blogs.msdn.com/jmanning/rss.aspx");
$mix->mix("http://feeds.feedburner.com/MartinWoodward");
$mix->mix("http://teamsystemrocks.com/blogs/grant_archibald/rss.aspx");
$mix->mix("http://msmvps.com/blogs/joesango/rss.aspx");
$mix->mix("http://blogs.msdn.com/ericlee/rss.aspx");
$mix->mix("http://blogs.msdn.com/vstsue/rss.aspx");
$mix->mix("http://blogs.msdn.com/jeffbe/rss.aspx");

/************ Simple File Cache Implementation *****************/
function cacheFileName($key) {
	return '/tmp/vstsbloggers_rss_slurp_cache_'.md5($key).'.rss';
}

function cacheGet($key, $expiry) {
	$filename = cacheFileName($key);
	if (!is_file($filename)) return false;
	if (filemtime($filename)+$expiry < time()) return false;
	if (!($fp = @fopen($filename, 'rb'))) return false;
	$data = unserialize(fread($fp, filesize($filename)));
	fclose($fp);
	return $data;
}


function cacheSet($key, $content) {
	$filename = cacheFileName($key);
    if (!($fp = @fopen($filename, 'wb'))) return false;
	fwrite($fp, serialize($content));
	fclose($fp);
}
echo ("<?xml version=\"1.0\" encoding=\"utf-8\"?>");
?>

<rss version="2.0" 
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:wfw="http://wellformedweb.org/CommentAPI/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:icbm="http://postneo.com/icbm">

<channel>
<title>VSTS Bloggers</title>
<link>http://www.teamsystemrocks.com/</link>
<description></description>
<dc:language>en-us</dc:language>

<dc:creator>vstsbloggers@woodwardweb.com</dc:creator>
<dc:date>2006-02-15T14:56:45+00:00</dc:date>
<admin:generatorAgent rdf:resource="http://www.woodwardweb.com/vstsbloggers/" />
<sy:updatePeriod>hourly</sy:updatePeriod>
<sy:updateFrequency>1</sy:updateFrequency>
<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>

<?php
while ($item = $mix->fetchItem(30, 30)) {
	$channel = $item['channel'];
	echo("<item><title>$item[title]</title><link>$item[link]</link><description><![CDATA[$channel[title]]]></description></item>");
}

?>

</channel>
</rss>