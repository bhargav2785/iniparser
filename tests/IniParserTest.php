<?php
/**
 * @author      Bhargav Vadher
 * @version     1.0 01/08/14 11:55 PM
 *              Initial change
 */

require_once 'source/IniParser.php';

class IniParserTest extends PHPUnit_Framework_TestCase
{

	private $files = array(
		'simple'      => 'tests/ini_files/simple.ini',
		'global'      => 'tests/ini_files/global.ini',
		'comments'    => 'tests/ini_files/comments.ini',
		'inheritance' => 'tests/ini_files/inheritance.ini',
		'array'       => 'tests/ini_files/array.ini',
		'bad'         => 'tests/ini_files/bad.ini',
		'escape'      => 'tests/ini_files/escape.ini',
		'json'        => 'tests/ini_files/json.ini',
	);
	private $data;

	public function tearDown() {
		$this->data = array();
	}

	private function _setData( $data ) {
		$this->data = $data;
	}

	private function _getData() {
		return $this->data;
	}

	/**
	 * @group simple
	 */
	public function testSimpleDotIni() {
		$parser = $this->_getParser($this->files['simple']);
		$this->_setData($parser->parse());

		$this->assertFalse($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 3, "Section count is not 3");

		// prod
		$prod = $this->_getSection('prod');
		$this->assertArrayHasKey('system', $prod, "key 'system' not found.");
		$this->assertArrayHasKey('includePath', $prod['system'], "key 'includePath' not found.");
		$this->assertArrayHasKey('phpSettings', $prod['system'], "key 'phpSettings' not found.");
		$this->assertArrayHasKey('site', $prod, "key 'site' not found.");
		$url = $prod['site']['url'];
		$this->assertTrue(strlen(filter_var($url, FILTER_VALIDATE_URL)) > 0, "{$url} is not a url");
		$this->assertEquals($prod['system']['section'], 'prod', "expected key is not 'prod'");
		$prod = null;

		// test
		$test = $this->_getSection('test');
		$this->assertArrayHasKey('system', $test, "key 'system' not found.");
		$this->assertArrayNotHasKey('includePath', $test, "key 'includePath' not found.");
		$this->assertArrayNotHasKey('phpSettings', $test, "key 'phpSettings' not found.");
		$this->assertEquals($test['system']['section'], 'test', "expected key is not 'test'");
		$test = null;

		// dev
		$dev = $this->_getSection('dev');
		$this->assertArrayHasKey('system', $dev, "key 'system' not found.");
		$this->assertArrayNotHasKey('includePath', $dev, "key 'includePath' not found.");
		$this->assertArrayNotHasKey('phpSettings', $dev, "key 'phpSettings' not found.");
		$this->assertEquals($dev['system']['section'], 'dev', "expected key is not 'dev'");
		$dev = null;
	}

	/**
	 * @group global
	 */
	public function testGlobalDotIni() {
		$parser = $this->_getParser($this->files['global']);
		$this->_setData($parser->parse());

		$this->assertTrue($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 1, "Section count is not 1");
	}

	/**
	 * @group comment
	 */
	public function testCommentsDotIni() {
		$parser = $this->_getParser($this->files['comments']);
		$this->_setData($parser->parse());

		$this->assertFalse($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 3, "Section count is not 3");
	}

	/**
	 * @group inheritance
	 */
	public function testInheritanceDotIni() {
		$parser = $this->_getParser($this->files['inheritance']);
		$this->_setData($parser->parse());

		$this->assertFalse($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 6, "Section count is not 6");

		// common - parent class
		$common = $this->_getSection('common');
		$this->assertArrayHasKey('section', $common, "key 'section' not found.");
		$this->assertArrayHasKey('type', $common, "key 'type' not found.");
		$this->assertArrayHasKey('url', $common['site'], "key 'section' not found.");
		$url = $common['site']['url'];
		$this->assertTrue(strlen(filter_var($url, FILTER_VALIDATE_URL)) > 0, "{$url} is not a url");
		$common = null;

		// prod - child or common
		$prod = $this->_getSection('prod');
		$this->assertEquals($prod['section'], 'prod');
		$this->assertEquals($prod['type'], 'child');
		$this->assertArrayHasKey('url', $prod['site'], "key 'section' not found.");
		$url = $prod['site']['url'];
		$this->assertTrue(strlen(filter_var($url, FILTER_VALIDATE_URL)) > 0, "{$url} is not a url");
		$prod = null;

		// bhargav - child of dev
		$bhargav = $this->_getSection('bhargav');
		$this->assertEquals($bhargav['section'], 'bhargav');
		$this->assertEquals($bhargav['type'], 'child');
		$this->assertArrayHasKey('url', $bhargav['site'], "key 'section' not found.");
		$url = $bhargav['site']['url'];
		$this->assertTrue(strlen(filter_var($url, FILTER_VALIDATE_URL)) > 0, "{$url} is not a url");
		$bhargav = null;

		// lazydev - child/alias of dev
		$this->assertEquals($this->_getSection('dev'), $this->_getSection('lazydev'));
		$this->assertTrue($this->_getSection('dev') === $this->_getSection('lazydev'));
	}

	/**
	 * @group array
	 */
	public function testArrayDotIni() {
		$parser = $this->_getParser($this->files['array']);
		$this->_setData($parser->parse());

		$this->assertTrue($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 5, "Section count is not 5");

		$array = $this->_getSection('array');
		$this->assertArrayHasKey('system', $array, "key 'system' not found.");
		$this->assertTrue(is_array($array['system']));
		$this->assertArrayHasKey('users', $array['system'], "key 'users' not found in 'system'.");
		$this->assertArrayHasKey('section', $array['system'], "key 'section' not found in 'system'.");
		$this->assertArrayHasKey('admins', $array['system'], "key 'admins' not found in 'system'.");

		$this->assertCount(3, $array['system']['users'], "'users' count is not 3.");
		$this->assertCount(1, $array['system']['section'], "'section' count is not 1.");
		$this->assertCount(4, $array['system']['admins'], "'admins' count is not 4.");
	}

	/**
	 * @group bad
	 */
	public function testBadDotIni() {
		$parser = $this->_getParser($this->files['bad']);
		$this->_setData($parser->parse());

		$this->assertFalse($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 3, "Section count is not 3");

		$data = $this->_getData();
		$this->assertTrue(is_array($this->_getSection('good1')));
		$this->assertTrue(is_array($this->_getSection('good2')));
		$this->assertTrue(is_array($this->_getSection('good3')));

		$this->assertCount(1, $data['good2'], "section 'good2' needs to have 1 property only.");
		$this->assertArrayHasKey('good21', $data['good2'], "section 'good2' needs to have the key 'good21'");
	}

	/**
	 * @group json
	 */
	public function testJsonDotIni() {
		$parser = $this->_getParser($this->files['json']);
		$this->_setData($parser->parse());

		$this->assertFalse($this->_hasGlobalSection());
		$this->assertEquals($this->_getSectionCount(), 2, "Section count is not 2");

		$data = $this->_getData();
		$this->assertTrue(is_array($this->_getSection('json')));
		$this->assertTrue(is_array($this->_getSection('people')));

		$this->assertArrayHasKey('list', $data['json'], "missing property 'list' in 'json' section");
		$list = $data['json']['list'];
		$this->assertTrue(is_array($list));
		$this->assertCount(2, $list, "'list' needs to be of length 2");

		$this->assertArrayHasKey('colors', $list, "missing sub-property 'colors' in 'list' property");
		$this->assertArrayHasKey('creditcards', $list, "missing sub-property 'creditcards' in 'list' section");

		// colors
		$colors = $data['json']['list']['colors'];
		$this->assertTrue(is_array($colors));
		$this->assertCount(3, $colors, "'color' property should have 3 items in it");
		$this->assertTrue($colors[1]['colorName'] === 'green');
		$this->assertTrue($colors[1]['hexValue'] === '#0f0');

		// creditcards
		$cards = $data['json']['list']['creditcards'];
		$this->assertTrue(is_array($cards));
		$this->assertCount(17, $cards, "'creditcards' property should have 17 items in it");
		$this->assertArrayHasKey('visa', $cards, "'creditcards' should have 'visa' in it");
		$visa = $cards['visa'];
		$this->assertTrue(is_array($visa));
		$this->assertTrue($visa['name'] === 'Visa' && $visa['prefix'] == 4 && $visa['length'] == 16);
	}


	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ***************************************** UTILITY functions ***************************************** //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @param string $file :: path to the .ini file
	 *
	 * @return IniParser
	 */
	private function _getParser( $file ) {
		return new IniParser($file);
	}

	/**
	 * Get a number of section in .ini file
	 *
	 * @return int
	 */
	private function _getSectionCount() {
		$this->assertNotEmpty($this->_getData());

		return count($this->_getData());
	}

	/**
	 * Get a section's properties
	 *
	 * @param $section
	 *
	 * @return array
	 */
	private function _getSection( $section ) {
		$data = $this->_getData();
		$this->assertTrue(!empty($data[$section]), "'$section' not found");

		return $data[$section];
	}

	/**
	 * See if 'global' section is added or not
	 * @return bool
	 */
	private function _hasGlobalSection() {
		$data = $this->_getData();

		return !empty($data[IniParser::SECTION_GLOBAL]);
	}
}