<?php
/**
 * This file contains the BaoXML parser class.
 * @author Ivan Georgiev
 * @package BaoXML
 * @link http://devcorner.georgievi.net/
 * @link http://devcorner.georgievi.net/phplib/baoxmllib/
 */

/**
 * Array key to be used for attributes.
 * @var string
 */ 
if (!defined('BAOXML_ATTRIBUTES')) define('BAOXML_ATTRIBUTES', '-a');

/**
 * Array key to be used for value.
 * @var string
 */ 
if (!defined('BAOXML_VALUE')) define('BAOXML_VALUE', '-v');

// Avoid redefinition.
if (in_array('baoxml', get_declared_classes())) return;

/**
 * XML to Array Parser
 * @author Ivan Georgiev
 * @package BaoXML
 */
class BaoXML {


	/**
	 * Parses XML data into an associative array.
	 *
	 * Each node is represented by an associative array.
	 * - Attributes can be found under the BAOXML_ATTRIBUTES key as an associative array.
	 * - The node value can be found under the BAOXML_VALUE key.
	 * - Children elements are grouped into arrays by tag. I.e. each tag has corresponding key.
	 *
	 * Example:
	 * $xml = <<<EOXML
	 *     <family name="Smith">
	 *         They make nice family.
	 *        <father>
	 *           <name>John Doe</name>
	 *           <age>40</age>
	 *        </father>
	 *        <mother>
	 *           <name>Joan Doe</name>
	 *           <age>unknown</age>
	 *        </mother>
	 *        <son>
	 *           <name>Bill</name>
	 *           <age>12</age>
	 *        </son>
	 *        <son>
	 *           <name>George</name>
	 *           <age>10</age>
	 *        </son>
	 *   </family>
	 * EOXML;
	 * echo("<xmp>");
	 * BaoXML::printa($tree);
	 * echo("</xmp><hr>");
	 *
	 * This will produce the following output:
	 *    [family] [0] [-a] [name] = Smith
	 *    [family] [0] [-v] =  They make nice family.
	 *    [family] [0] [father] [0] [name] [0] [-v] = John Doe
	 *    [family] [0] [father] [0] [age] [0] [-v] = 40
	 *    [family] [0] [mother] [0] [name] [0] [-v] = Joan Doe
	 *    [family] [0] [mother] [0] [age] [0] [-v] = unknown
	 *    [family] [0] [son] [0] [name] [0] [-v] = Bill
	 *    [family] [0] [son] [0] [age] [0] [-v] = 12
	 *    [family] [0] [son] [1] [name] [0] [-v] = George
	 *    [family] [0] [son] [1] [age] [0] [-v] = 10
	 *
	 * @param string $data XML Data to parse.
	 * @param boolean $case_folding [true] Whether tag names should be converted to uppercase.
	 * @param boolean $skip_white [true] Skip values consisting of whitespace characters.
	 * @param string $source_encoding The xml data encoding.
	 * @param string $target_encoding The target encoding used by XML parser. Supported target encodings are ISO-8859-1, US-ASCII and UTF-8.
	 * @static
	 */
	function parseContent($data, $case_folding=null, $skip_white=null, $source_encoding='', $target_encoding='') {
		if (!isset($case_folding)) $case_folding = true;
		if (!isset($skip_white)) $skip_white = true;
		$parser = xml_parser_create($source_encoding);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, $case_folding ? 1 : 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $skip_white ? 1 : 0);
		if (!empty($target_encoding))
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $target_encoding);
		xml_parse_into_struct($parser, $data, $vals, $tags);
		xml_parser_free($parser);
		
		$tree = array(); 
		$i = 0;

		/* @todo I've got a feeling that this can be optimized */
		if (isset($vals[$i]['attributes'])) {
			$tree[$vals[$i]['tag']][][BAOXML_ATTRIBUTES] = $vals[$i]['attributes']; 
			$index = count($tree[$vals[$i]['tag']])-1;
			$tree[$vals[$i]['tag']][$index] =  array_merge($tree[$vals[$i]['tag']][$index], BaoXML::getChildren($vals, $i));
		}
		else
			$tree[$vals[$i]['tag']][] = BaoXML::getChildren($vals, $i); 
		
		return $tree; 
	}
	
	function parseFile($file_name) {
		return BaoXML::parseContent(join('', file($file_name)));
	}
	
	/**
	 * @static
	 * @access private
	 */
	function getChildren($vals, &$i) 
	{ 
		$children = array();     // Contains node data
		
		/* Node has CDATA before it's children */
		if (isset($vals[$i]['value'])) 
			$children[BAOXML_VALUE] = $vals[$i]['value']; 
		
		/* Loop through children */
		while (++$i < count($vals))
		{ 
		switch ($vals[$i]['type']) 
		{ 
		  /* Node has CDATA after one of it's children 
			(Add to cdata found before if this is the case) */
		  case 'cdata': 
			if (isset($children[BAOXML_VALUE]))
			  $children[BAOXML_VALUE] .= $vals[$i]['value']; 
			else
			  $children[BAOXML_VALUE] = $vals[$i]['value']; 
			break;
		  /* At end of current branch */ 
		  case 'complete': 
			if (isset($vals[$i]['attributes'])) {
			  $children[$vals[$i]['tag']][][BAOXML_ATTRIBUTES] = $vals[$i]['attributes'];
			  $index = count($children[$vals[$i]['tag']])-1;
		
			  if (isset($vals[$i]['value'])) 
				$children[$vals[$i]['tag']][$index][BAOXML_VALUE] = $vals[$i]['value']; 
			  else
				$children[$vals[$i]['tag']][$index][BAOXML_VALUE] = ''; 
			} else {
			  if (isset($vals[$i]['value'])) 
				$children[$vals[$i]['tag']][][BAOXML_VALUE] = $vals[$i]['value']; 
			  else
				$children[$vals[$i]['tag']][][BAOXML_VALUE] = ''; 
			}
			break; 
		  /* Node has more children */
		  case 'open': 
			if (isset($vals[$i]['attributes'])) {
			  $children[$vals[$i]['tag']][][BAOXML_ATTRIBUTES] = $vals[$i]['attributes'];
			  $index = count($children[$vals[$i]['tag']])-1;
			  $children[$vals[$i]['tag']][$index] = array_merge($children[$vals[$i]['tag']][$index],BaoXML::getChildren($vals, $i));
			} else {
			  $children[$vals[$i]['tag']][] = BaoXML::getChildren($vals, $i);
			}
			break; 
		  /* End of node, return collected data */
		  case 'close': 
			return $children; 
		} 
		} 
	} 	
	/**
	 * Debug helper method to dump arrays in easy to read format.
	 */
	function printa($obj) {
		global $__level_deep;
		if (!isset($__level_deep)) $__level_deep = array();
		
		if (is_object($obj))
			print '[obj]';
		elseif (is_array($obj)) {
			foreach(array_keys($obj) as $keys) {
			  array_push($__level_deep, "[".$keys."]");
			  BaoXML::printa($obj[$keys]);
			  array_pop($__level_deep);
			}
		}
		else print implode(" ",$__level_deep)." = $obj\n";
	}	
}

?>