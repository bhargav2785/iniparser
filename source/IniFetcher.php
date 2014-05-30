<?php

require_once 'IniParser.php';

/**
 * @author      Bhargav Vadher
 * @version     V1.0 5/18/14 11:02 PM
 *              Initial version
 * @copyright   http://bhargavvadher.com
 *
 * A wrapper class around IniParser.php. This class makes it easy to use IniParser without
 * writing your own accessor. Internally it calls IniParser and does the same thing as
 * the actual IniParser class but on top of that it provides public accessor that maps
 * key with dots to the decoded IniParser array.
 */
class IniFetcher
{

	private static $_params = array();
	private static $self = null;

	private function __construct() {
	}

	/**
	 * @param array $params
	 */
	private static function setParams( array $params ) {
		self::$_params = $params;
	}

	/**
	 * @return array
	 */
	public static function getParams() {
		return self::$_params;
	}

	/**
	 * @param String $key key of ini value delimited by `.`
	 *
	 * @return array|null
	 */
	public static function getParam( $key ) {
		return self::get($key);
	}

	/**
	 * @param String $iniPath path of the ini file
	 *
	 * @return IniFetcher
	 * @throws InvalidArgumentException
	 *
	 * Namespace based factory. One factory for each ini file. This is particularly useful
	 * when we have multiple ini file to be decoded in single php process.
	 */
	public static function getInstance( $iniPath ) {

		if( !is_null($iniPath) ){
			if( is_null(self::$self[$iniPath]) ){
				self::$self[$iniPath] = new self();
				$iniParser            = new IniParser($iniPath);
				self::setParams($iniParser->parse());
			}
			else {
				return self::$self[$iniPath];
			}
		}
		else {
			throw new InvalidArgumentException('Variable iniPath can not be null');
		}

		return self::$self[$iniPath];
	}

	/**
	 * @param String $key key of ini value delimited by `.`
	 *
	 * @return array|null
	 *
	 * Get the ini property value given the namespaced key
	 */
	public static function get( $key ) {
		$params = self::getParams();
		$parts  = explode('.', $key);

		/**
		 * IF multi-key is requested like a.b.c.d then make
		 * sure each sub-array is available.
		 */
		if( count($parts) > 1 ){
			$value = $params;
			foreach( $parts as $subKey ){
				if( isset($value[$subKey]) ){
					$value = $value[$subKey];
				}
				else {
					return null;
				}
			}

			return $value;
		}
		else if( !empty($params[$key]) ){
			return $params[$key];
		}

		return null;
	}
} 