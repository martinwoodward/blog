<?php
/**
 * This file contains BaoRSS and BaoRSSMix classes
 * @package BaoXML
 * @link http://devcorner.georgievi.net/
 * @link http://devcorner.georgievi.net/phplib/baoxmllib/
 */

require(dirname(__FILE__).'/bao-xml.php');

/**
 * Error message when unable to create file.
 */
if (!defined('BAORSS_ERR_NO_STREAM')) define('BAORSS_ERR_NO_STREAM', 'Could not open the specified file.  Check the path, and make sure that you have write permissions to this file.');

if (!defined('BAORSS_VERSION')) define('BAORSS_VERSION', '0');

// Avoid redeclaration
if (in_array('baorss', get_declared_classes())) return;

/**
 * RSS parser class
 * @author Ivan Georgiev
 * @package BaoXML
 * @uses BaoXML
 */
class BaoRSS {
	/**
	 * User agent to use for HTTP requests.
	 * @see setUserAgent()
	 * @access private
	 */
	var $agent;
	/**
	 * Parsed data.
	 * Associative array:
	 *   - uri
	 *   - type: rss
	 *   - version
	 *   - channel - channle information
	 *   - items - indexed array of items
	 * @access private
	 */
	var $rss_data;
	/**
	 * The current item index.
	 * @access private
	 */
	var $item_index;
	/**
	 * Debug mode
	 * @access private
	 */
	var $debug_mode = false;
	
	/**
	 * Cache enable flag.
	 * @var boolean
	 * @access private
	 */
	var $cache;
	/**
	 * Default expiration time.
	 * @see setExpireTime()
	 * @access private
	 */
	var $expiry;
	/**
	 * Cache get callback.
	 * @access private
	 */
	var $cache_get;
	/**
	 * Cache set callback.
	 * @access private
	 */
	var $cache_set;
	/**
	 * Cache gc callback.
	 * @access private
	 */
	var $cache_gc;
	/**
	 * @access private
	 */
	var $file_cache_dir = '/tmp';
	
	/**
	 * Backward compatibility constructor.
	 */
	function BaoRSS() { $this->__construct(); }
	
	/**
	 * Constructor.
	 */
	function __construct() {
		$this->agent = "Woodwardweb RSS Slurper (http://www.woodwardweb.com/vsts/000188.html)";
	}
	
	/**
	 * Parse RSS content.
	 * @param string $uri Uri of the feed.
	 * @param string $cache Override cache enabled flag. If null, the cache enabled flag is used. @see setCacheEnabled().
	 * @param int $ttl Time to live - cache expiration time override. If null, the value set by setCacheExpiry is used. @see setCacheExpiry().
	 * @reurn boolean True on success or false if failed.
	 */
	function parse($uri, $cache=null, $ttl=null) {
		$recache = false;
		$this->rss_data = array();
		$this->resetRssItems();
		
		if (!isset($cache)) $cache = $this->cache;
		if (!isset($ttl)) $ttl = $this->expiry;
		//
		if (!$cache ||
		   ($cache && !$this->cacheGet($uri, $ttl)))
		{
			if (!empty($this->agent)) ini_set('user_agent', $this->agent);
			if ($rss = BaoXML::parseFile($uri)) {
				$this->rss_data['uri'] = $uri;
				$this->rss_data['type'] = 'rss';
				$this->rss_data['version'] = $rss['RSS'][0][BAOXML_ATTRIBUTES]['VERSION'];
				// Get channel data
				foreach ($rss['RSS'][0]['CHANNEL'][0] as $tag => $item) {
					if ('ITEM'==$tag) continue;
					if (isset($item[0][BAOXML_VALUE]))
						$this->rss_data['channel'][strtolower($tag)] = $item[0][BAOXML_VALUE];
					else
						$this->rss_data['channel'][strtolower($tag)] = '';
				}
				// Now get items
				foreach ($rss['RSS'][0]['CHANNEL'][0]['ITEM'] as $item) {
					$newitem = array();
					foreach (array_keys($item) as $k) {
						$newitem[strtolower($k)] = trim($item[$k][0][BAOXML_VALUE]);
					}
					$newitem['pubstamp'] = (isset($newitem['pubdate']) ? strtotime($newitem['pubdate']) : 0);
						$this->rss_data['items'][] = $newitem;
				}
				$recache = true;
			} else
				return false;
		}
		if ($cache && $recache)
			$this->cache($uri);
		return true;
	}

	/**
	 * Get the channel property or all chanel properties in a single array.
	 * @param string $element Property name to retrive. If empty, all properties are returned in an associative array.
	 * @return mixed Property value or an associative array of all properties. If the property doesn't exist, returns false.
	 */
	function getChannel($element='') {
		if (empty($element))
			return $this->rss_data['channel'];
		else
			return (isset($this->rss_data['channel'][$element]) ? $this->rss_data['channel'][$element] : false);
	}
	
	/**
	 * Get the rss version
	 */
	function getVersion() {
		return $this->rss_data['version'];
	}

	/**
	 * Get the feed type.
	 */
	function getType() {
		return $this->rss_data['type'];
	}
	
	/**
	 * Move the internal item pointer used by iterative methods to the first item.
	 * @see fetchRssItem()
	 */
	function resetRssItems() {
		$this->item_index = -1;
	}

	/**
	 * Get the next item from the items' list.
	 *
	 * If $maxnum parameter is specified, this method returns up to the given
	 * number of items.
	 * 
	 * This method is useful for iterative item processing:
	 *     $rss = new BaoRSS();
	 *     $rss->parse('http://www.site.com/feed/');
	 *     while ($item = $rss->fetchRssItem()) {
	 *         echo("$item[title]\n");
	 *     }
	 *
	 * @param int $maxnum Maximum number of items returned by this method.
	 * @param int $maxdays Maximum item age in days.
	 * @return array Associative array of all item properties.
	 */
	function fetchRssItem($maxnum=0, $maxdays=0) {
		if ($maxnum>0 && $this->item_index+1>=$maxnum) return null;
		while ($item = $this->getRssItem('', ++$this->item_index)) {
			if ($maxdays<=0) break;
			if (time() <= $item['pubstamp']+$maxdays*3600*24)
				break;
		}
		return $item;
	}

	/**
	 * Get item's property or entire item.
	 * 
	 * There is an additional property - 'pubstamp' which is the pubDate converted to Unix timestamp.
	 * You can specify 'pubstamp' as property name to retrieve its value or it will exist in 
	 * property array returned when no property name is given.
	 *
	 * If the item or property doesn't exist, false is returned.
	 *
	 * @property string $element Name of the property to get. If empty, the whole item is returned.
	 * @property int $index Item index. If null, the current item is used.
	 * @return mixed Property value or an associative array with all item properties.
	 */
	function & getRssItem($element='', $index=null) {
		if (!isset($index)) $index = $this->item_index;
		if (!isset($this->rss_data['items'][$index]))
			return null;
		$item =& $this->rss_data['items'][$index];
		if (''==$element)
			return $item;
		else
			return (isset($item[$element]) ? $item[$element]: false);
	}

	/**
	 * Set cache handlers.
	 *    -  mixed cacheGet(string $key, int $expiry) - returns cached item content or false if not found.
	 *    - void cacheSet(string $key, mixed $content) - save item to cache.
	 *    - void cacheGC(int $expiry) - perform garbage collection on old items.
	 */
	function setCacheHandlers($cache_get, $cache_set, $cache_gc=null) {
		$this->cache_get = $cache_get;
		$this->cache_set = $cache_set;
		$this->cache_gc = $cache_gc;
	}
	
	/**
	 * Retrieve feed content from cache.
	 * @param string $uri Feed location.
	 * @param int $expiry Cached item time to live (expiration).
	 * @return boolean True if retrieved or false if unable to retrieve fresh data.
	 * @access private
	 */
	function cacheGet($uri, $expiry=null) {
		if (!isset($this->cache_get)) 
			$this->setCacheHandlers(
				array($this, 'fileCacheGet'),
				array($this, 'fileCacheSet')
				);
		$this->rss_data = call_user_func($this->cache_get, $uri, $expiry);
		return ($this->rss_data) ? true : false;
		return false;
	}
	
	/**
	 * Store feed data to cache.
	 * @param string $uri Feed location.
	 * @access private
	 */
	function cache($uri) {
		if (!isset($this->cache_set))
			$this->setCacheHandlers(
				array($this, 'fileCacheGet'),
				array($this, 'fileCacheSet')
				);
		call_user_func($this->cache_set, $uri, &$this->rss_data);
	}
	
	/**
	 * Sets the expiration time (TTL) for cache files.
	 * @param int $expiry The new value for the expiration time.
	 */
	function setCacheExpiry($expiry) {
		$this->expiry = $expiry;
	}
	
	/**
	 * Set cache enable flag.
	 * @param boolean $enabled Is cache enabled.
	 */
	function setCacheEnabled($enabled) {
		$this->cache = $enabled;
	}

	/**
	 * Sets the user agent used for HTTP requests.
	 * @param string $agent The new user agent.
	 */
	function setUserAgent($agent) {
		$this->agent = $agent;
	}


	/**
	 * Handle errors.
	 * @access private
	 */
	function raiseError($line, $err, $fatal=false) {
		if ($this->debug_mode)
			printf($this->error, $line, $err);
	}

	/**
	 * Simple file caching: Set cache dir.
	 * Default cache directory is /tmp
	 * @param string $dir New cache directory.
	 */
	function setCacheDir($dir) {
		$this->file_cache_dir = $dir;
	}
	
	/**
	 * Simple file caching: Get cache file name by URI.
	 * @param string $key URI to cache.
	 * @return string File name for the uri to be cached.
	 * @access private
	 */
	function cacheFileName($key) {
		return $this->file_cache_dir.'/cache_'.md5($key).'.rss';
	}
	
	/**
	 * Simple file caching: Retrieve cache from file
	 * @param string $key
	 * @param string $expiry Cache expiration.
	 * @return mixed Cached content or false if not found.
	 * @access private
	 */
	function fileCacheGet($key, $expiry) {
		$filename = $this->cacheFileName($key);
		if (!is_file($filename)) return false;
		if (filemtime($filename)+$expiry < time()) return false;
		if (!($fp = @fopen($filename, 'rb'))) return false;
		$data = unserialize(fread($fp, filesize($filename)));
		fclose($fp);
		return $data;
	}
	
	/**
	 * Simple file caching:  Save content to file cache.
	 * @param string $key
	 * @param mixed $content Content to save
	 * @access private
	 */
	function fileCacheSet($key, $content) {
		$filename = $this->cacheFileName($key);
		if (!($fp = @fopen($filename, 'wb'))) return false;
		fwrite($fp, serialize($content));
		fclose($fp);
	}
}

/**
 * BaoRSSMix RSS-mixer class.
 * @author Ivan Georgiev
 * @uses BaoRSS
 */
class BaoRSSMix extends BaoRSS {
	/**
	 * Mixed items list.
	 * @access private
	 */
	var $_items;
	/**
	 * Channels list.
	 * Used internally to refer channels by items.
	 * @access private.
	 */
	var $_channels;
	/**
	 * Current mixed item for iterative methods (@see fetchItem())
	 * @access private
	 */
	var $_index;
	
	
	/**
	 * Parse feed and add its items to the mix.
	 * @param string $uri Feed location.
	 * @param mixed $cache Cache file name (string). If true, the cache file name is calculated. Value of null or false means no caching.
	 * @return boolean Returns true upon success.
	 */
	function mix($uri, $cache=null, $ttl=null) {
		$this->_index = -1;
		if (!$this->parse($uri, $cache, $ttl)) return false;
		$channel = $this->getChannel();
		$this->_channels[] =& $channel;
		while ($item = $this->fetchRssItem()) {
			// Avoid duplicates
			if (isset($item['guid']) && $this->itemExists($item['guid'], 'guid'))
				continue;
			elseif (isset($item['link']) && $this->itemExists($item['link'], 'link'))
				continue;
			$item['channel'] =& $channel;
			$this->_items[] = $item;
		}
//		$this->sortItems();
		return false;
	}

	/**
	 * Move the internal mixed items pointer used by iterative methods to the first item.
	 * @see fetchItem()
	 */
	function resetItems() { $this->_index = -1; }

	/**
	 * Get the next item from the items' list.
	 *
	 * If $maxnum parameter is specified, this method returns up to the given
	 * number of items.
	 * 
	 * This method is useful for iterative item processing:
	 *     $rss = new BaoRSSMix();
	 *     $rss->mix('http://www.site.com/feed/');
	 *     $rss->mix('http://www.site1.com/feed/');
	 *     while ($item = $rss->fetchItem()) {
	 *         echo("$item[title]\n");
	 *     }
	 *
	 * @param int $maxnum Maximum number of items returned by this method.
	 * @param int $maxdays Maximum item age in days. 0 - all.
	 * @return array Associative array of all item properties.
	 */
	function fetchItem($maxnum=0, $maxdays=0) {
		if ($maxnum>0 && $this->_index+1>=$maxnum) return null;
		$item = $this->getItem('', ++$this->_index);
		
		while ($item = $this->getItem('', ++$this->_index)) {
			if ($maxdays<=0) break;
			if (time() <= $item['pubstamp']+$maxdays*3600*24)
				break;
		}
		return $item;
	}

	/**
	 * Get item's property or entire item.
	 * 
	 * If the item or property doesn't exist, false is returned.
	 *
	 * @property string $element Name of the property to get. If empty, the whole item is returned.
	 * @property int $index Item index. If null, the current item is used.
	 * @return mixed Property value or an associative array with all item properties.
	 */
	function & getItem($element='', $index=null) {
		if (!isset($index)) $index = $this->_index;
		if (!isset($this->_items[$index])) return false;
		
		$item =& $this->_items[$index];
		if (''==$element)
			return $item;
		else
			return (isset($item[$element]) ? $item[$element] : false);
	}


	/**
	 * Check wether an item with given value already exists.
	 * @param mixed $value Value to search for.
	 * @param string $key Item attribute name to use.
	 * @return boolean True if exists and false otherwise.
	 */
	function itemExists($value, $key='link') {
		for ($i=0; $i<count($this->_items); $i++) {
			if (/*isset($this->_items[$i][$key]) && */$value == $this->_items[$i][$key]) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Sort items in descending order by pubstamp.
	 */
	function sortItems() {
		usort($this->_items, array('BaoRSSMix', '_cmp'));
	}
	
	/**
	 * Callback method for items sorting.
	 * @access private
	 */
	function _cmp($a, $b) {
		if ($a['pubstamp'] == $b['pubstamp'])
			return 0;
		return ($a['pubstamp'] < $b['pubstamp'] ? 1 : -1);
	}
}

?>