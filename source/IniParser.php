<?php
/**
 *
 * @author    Bhargav Vadher
 * @copyright http://bhargavvadher.com
 * @version   V1.0 01/06/2014 7:33 PM
 *            Initial version
 *
 */

/**
 * Class IniParser
 */
class IniParser
{
	const OUTPUT_FORMAT_JSON   = 'json';
	const OUTPUT_FORMAT_ARRAY  = 'array';
	const OUTPUT_FORMAT_OBJECT = 'arrayObject';
	const EXTENSION_DELIMITER  = ':';
	const KEY_DELIMITER        = '.';
	const SECTION_GLOBAL       = 'global';

	private $_file = null;
	private $_sections = array();
	private $_inheritedSections = array();
	private $_tree = array();
	private $format = self::OUTPUT_FORMAT_ARRAY;
	private $formatter = null;

	/**
	 * @param Formatter $formatter
	 */
	public function setFormatter( $formatter ) {
		$this->formatter = $formatter;
	}

	/**
	 * @return Formatter
	 */
	public function getFormatter() {
		if( is_null($this->formatter) ){
			$this->setFormatter(new Formatter());
		}

		return $this->formatter;
	}

	/**
	 * @param string|null $file
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $file = null ) {
		if( !is_null($file) ){
			$this->_setFile($file);
		}
		else {
			throw new InvalidArgumentException('Variable file can not be null');
		}
	}

	/**
	 * @param string $format
	 */
	public function setFormat( $format ) {
		$this->format = $format;
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * @param string $section
	 * @param array  $properties
	 *
	 * Set properties into the given inherited section
	 */
	private function _setInheritedSection( $section, $properties ) {
		$this->_inheritedSections[$section] = $properties;
	}

	/**
	 * @param string $section
	 *
	 * @return array
	 */
	public function getInheritedSection( $section ) {
		return isset($this->_inheritedSections[$section]) ? $this->_inheritedSections[$section] : array();
	}

	/**
	 * @param array $sections
	 */
	private function _setSections( $sections ) {
		$this->_sections = $sections;
	}

	/**
	 * @return array
	 */
	public function getSections() {
		return $this->_sections;
	}

	/**
	 * @return array
	 */
	public function getInheritedSections() {
		return !empty($this->_inheritedSections) ? $this->_inheritedSections : array();
	}

	/**
	 * @param string $section
	 *
	 * @return array
	 */
	public function getSection( $section ) {
		$sections = $this->getSections();

		return isset($sections[$section]) ? $sections[$section] : array();
	}

	/**
	 * @param null $file
	 */
	private function _setFile( $file ) {
		$this->_file = $file;
	}

	/**
	 * @return null
	 */
	private function _getFile() {
		return $this->_file;
	}

	/**
	 * @param string $sectionKey
	 *
	 * @return bool
	 */
	private function _isSectionSet( $sectionKey ) {
		return isset($this->_tree[$sectionKey]);
	}

	/**
	 * @param string $key
	 */
	private function _setEmptySection( $key ) {
		$this->_tree[$key] = array();
	}

	/**
	 * @return array
	 */
	private function _getTree() {
		return $this->_tree;
	}

	/**
	 * Idea is to remove comment lines from ini file and convert remaining
	 * lines into a string and use that string to parse ini with parse_ini_string()
	 *
	 * After that do inheritance and expansion of keys to convert keys into multi-
	 * dimensional array
	 *
	 * @return array|ArrayObject|string
	 *
	 * @throws Exception
	 */
	public function parse() {
		$fileLines     = file($this->_getFile(), FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
		$filteredLines = array();

		// anything that starts with 0 or more spaces followed by 1 or more # signs followed by anything
		$pattern = "/^(\s)*[#]+(.)*$/";

		foreach( $fileLines as $line ){
			if( preg_grep($pattern, array( $line ), PREG_GREP_INVERT) ){
				array_push($filteredLines, $line);
			}
		}

		$sections = parse_ini_string(join(PHP_EOL, $filteredLines), true);
		/**
		 * Before setting anything make sure the ini file is not for
		 * global properties i.e without any section name but with some
		 * properties defined in file
		 */
		$sections = $this->_addGlobalSection($sections);

		if( empty($sections) ){
			throw new Exception("{$this->_getFile()} is an empty ini file");
		}

		/**
		 * Process is done in two passes
		 *      1. Inheritance : Inherit from parent, if any
		 *      2. Expansion   : Expand key names to create a tree structure
		 */
		$this->_setSections($sections);
		$this->_inheritSections($sections);
		$this->_expandSections();

		return $this->_getOutput();
	}

	/**
	 * Global ini file is a file without any section name in it but it might have
	 * one or more properties in it. In this case, we will add the default 'global'
	 * section name for all properties
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	private function _addGlobalSection( array $sections ) {
		/**
		 * Check all first level values for KVPs and make sure two things
		 *      1. if value is an array its not global line
		 *      2. if value is not an array its global line
		 */
		$temp = array();
		foreach( $sections as $key => $value ){
			if( is_array($value) ){
				$temp[$key] = $value;
			}
			else {
				$temp[self::SECTION_GLOBAL][$key] = $value;
			}
		}

		return $temp;
	}

	/**
	 * @param $sections
	 *
	 * Inherit properties, if any. Starts combining properties from parent till all the way to
	 * child properties. At the end it does array recursive merge in a such way so that child
	 * property overrides parent property.
	 */
	private function _inheritSections( $sections ) {
		foreach( $sections as $key => $properties ){
			$keys        = explode(self::EXTENSION_DELIMITER, trim($key));
			$hostSection = trim($keys[0]); // get first/current section before we do sorting

			/**
			 * SORT $keys in reverse order while preserving key indexes because order DOES matter.
			 * This is because parent properties need to be set first if some child is extending that parent
			 */
			krsort($keys);

			// IF an independent section
			if( count($keys) === 1 ){
				$this->_setInheritedSection(trim($key), $properties);
			}
			// IF an extended section
			else {
				$tree = array();
				while( ($parentSection = array_shift($keys)) ){
					$parentProperties = $this->getInheritedSection(trim($parentSection));

					// IF it is the last section in the key, it must be the current one since we reverse sort it
					if( trim($parentSection) == $hostSection ){
						// MERGE properties from parent to child without overriding the child values
						// i.e. nothing on $properties will get override from $tree
						$this->_setInheritedSection($hostSection, ($properties + $tree));
					}
					else if( empty($parentProperties) ){
						error_log("No parent properties for {$parentSection}");
					}
					else {
						// PUSH $parentProperties properties to $tree recursively
						$tree = array_merge_recursive($tree, $parentProperties);
					}
				}
			}
		}
	}

	/**
	 * Expands all sections and its properties into the empty array.
	 */
	private function _expandSections() {
		foreach( $this->getInheritedSections() as $key => $properties ){
			$this->_addSectionProperties(trim($key), $properties);
		}
	}

	/**
	 * @param string $sectionName
	 * @param array  $properties
	 *
	 *
	 */
	private function _addSectionProperties( $sectionName, Array $properties ) {
		if( !$this->_isSectionSet($sectionName) ){
			$this->_setEmptySection($sectionName);
		}

		$this->_setSectionProperties($sectionName, $properties);
	}

	/**
	 * @param string $sectionName
	 * @param array  $properties
	 *
	 * Loads section by parsing its properties.
	 */
	private function _setSectionProperties( $sectionName, Array $properties ) {
		$this->_tree[$sectionName] = $this->_parseKeyValues($properties);
	}

	/**
	 * @param array $properties
	 *
	 * @return array
	 *
	 * Parses multi dimensional $properties array in such a way so that each key for each dimension
	 * creates a namespace and at the end it creates a final namespaced key which contains the value
	 * of the deepest array keys value.
	 */
	private function _parseKeyValues( Array $properties ) {
		$tree = array();
		foreach( $properties as $key => $value ){
			$keys    = explode(self::KEY_DELIMITER, $key);
			$subTree = array();

			/**
			 * $subTree and $current are the same thing pointing to
			 * the same memory/variable address (see that &).
			 *
			 * Use $current for the address manipulation/check variable and
			 * $subTree as a storage array
			 */
			$current =& $subTree;

			while( ($propKey = array_shift($keys)) ){
				if( !isset($current[$propKey]) ){
					$current[$propKey] = array();
					$current           =& $current[$propKey];
				}
			}
			$current = $this->_parseValue($value);
			$tree    = array_merge_recursive($tree, $subTree);
		}

		return $tree;
	}

	/**
	 * @param string $value actual value on the right side of the key-value pair
	 *
	 * @return array
	 *
	 * If value needs to be parsed, parse it otherwise return the
	 * value as it is.
	 */
	private function _parseValue( $value ) {
		$f = $this->getFormatter();
		if( $f->isArrayLiteral($value) ){
			return $f->convertToArray($value);
		}
		else if( $f->isJsonValue($value) ){
			return json_decode($value, $associative = true);
		}
		else {
			return $value;
		}
	}

	/**
	 * @return array|ArrayObject|string
	 *
	 * returns the response in requested format.
	 */
	private function _getOutput() {
		switch( $this->getFormat() ){
			case self::OUTPUT_FORMAT_JSON:
				return json_encode($this->_getTree(), JSON_FORCE_OBJECT);
				break;
			case self::OUTPUT_FORMAT_OBJECT:
				return new ArrayObject($this->_getTree(), ArrayObject::ARRAY_AS_PROPS);
				break;
			case self::OUTPUT_FORMAT_ARRAY:
			default:
				return $this->_getTree();
				break;
		}
	}
}

class Formatter
{
	/**
	 * @param string|number $value
	 *
	 * @return bool
	 *
	 * Is the right side of the line an array literal (like [1, 2, something])
	 */
	public function isArrayLiteral( $value ) {
		$value = trim($value);

		return strrpos($value, '[', 0) === 0 && strrpos($value, '[', -1) === 0;
	}

	/**
	 * @param string|number $string
	 *
	 * @return bool
	 *
	 * Is the right side of the line a json object
	 */
	public function isJsonValue( $string ) {
		return !is_null(json_decode($string));
	}

	/**
	 * @param string $value
	 *
	 * @return array
	 *
	 * Utility function that converts array literals into php array.
	 * Ex. [1,2,3] => array('1','2','3')
	 */
	public function convertToArray( $value ) {
		return explode(',', trim($value, '[]'));
	}
}