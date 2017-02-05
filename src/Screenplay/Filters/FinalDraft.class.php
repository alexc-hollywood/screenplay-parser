<?php
/**
* Filter/parser for Final Draft Pro (.fdx) XML files
*
* Opens an FDX file, traverses it using simplexml_load_file(), then provides a
* data breakdown of all relevant items.
*
* LICENSE: MIT
*
* @category   PHP
* @package    Screenplay
* @subpackage Filters
* @author     Alex Coppen (azcoppen@protonmail.com)
* @copyright  Copyright (c) 2016 Alex Coppen
* @license    http://framework.zend.com/license BSD License
* @version    $Id:$
* @since      File available since Release 1.0
*/

namespace Screenplay\Filters;

use Screenplay\ExtractorInterface;
use \Exception;

/**
 * Filter definition to parse Final Draft XML and return the data items.
**/

class FinalDraft implements ExtractorInterface {

	/**
  * Path to the file we need to open.
  * @var string
  */
	private $file = '';

	/**
  * Minimum Final Draft version that uses XML.
  * @var int
  */
	protected $version = 8;

	/**
  * Holds the contents of the file opened.
  * @var string
  */
	private $content = '';

	/**
  * Structure to hold the parsed characters.
  * @var array
  */
	private $characters = [];

	/**
  * Structure to hold the parsed scenes.
  * @var array
  */
	private $scenes = [];

	/**
  * Structure to hold the CAPITALIZED items (emphasized items).
  * @var array
  */
	private $uppercase = [];

	/**
  * Regex to find capitalized words.
  * @var string
  */
	private $uppercase_regex = '/\b([A-Z0-9\s]{2,}+)\b/';

	/**
   * Retrieves a list of character listed items in the document.
   *
   * Traverses the XML DOM to find cast members and paragraphs identified as Character elements.
   *
   * @author Alex Coppen (acamerondev@protonmail.com)
   * @return array|boolean A list of characters, or false.
   */
		public function parse_characters() {
			if( isset( $this->content->Cast ) ) {

				foreach( $this->content->Cast->Member AS $member ) {
					$attrs = $member->attributes();
					array_push($this->characters, strval($attrs['Character']));
				}

				// CHECK CAST LIST: go through all dialogue elements to check for missing characters that haven't been catalogued
				foreach( $this->content->Content->Paragraph AS $p ) {
					$attrs = $p->attributes();
					if( isset($attrs['Type']) &&  $attrs['Type'] == 'Character') {
						if( !empty(strval($p->Text)) ) {
							if( !in_array(strval($p->Text), $this->characters) && stristr(strval($p->Text), '(') === FALSE ) {
								array_push($this->characters, strtoupper(strval($p->Text)));
							}
						}
					}

				}

				sort($this->characters);
				return $this->characters;

			} else {
				return false;
			}
		}

		/**
	   * Retrieves a list of scene items in the document.
	   *
	   * Traverses the XML DOM to find scene headings.
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of scenes.
	   */
		public function parse_scenes() {
			foreach( $this->content->Content->Paragraph AS $p ) {
				$attrs = $p->attributes();
				if( isset($attrs['Type']) &&  $attrs['Type'] == 'Scene Heading') {
					if( !empty(strval($p->Text)) ) {
						array_push($this->scenes, strval($p->Text));
					}
				}

			}
			return $this->scenes;
		}

		/**
	   * Retrieves a list of CAPITALIZED items needing emphasis.
	   *
	   * Traverses the XML DOM to preg_match all UPPERCASE words in Action elements.
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of capitalized words.
	   */
		public function parse_capitalized() {
			foreach( $this->content->Content->Paragraph AS $p ) {
				$attrs = $p->attributes();
				if( isset($attrs['Type']) &&  $attrs['Type'] == 'Action') {

					 preg_match_all($this->uppercase_regex, strval($p->Text), $matches);

					 if( count($matches) && isset($matches[0]) ) {
					 	if( count($matches[0]) ) {
					 		foreach($matches[0] As $match) {
					 			$match = trim($match);
					 			if( !empty($match) && $match != 'A' ) {
					 				if( !in_array($match, $this->uppercase) && !in_array($match, $this->characters) ) {
					 					array_push($this->uppercase, strtoupper($match));
					 				}
					 			}
					 		}
					 	}
					 }

				}
			}

			sort($this->uppercase);
			return $this->uppercase;
		}

		/**
	   * Class constructor.
	   *
	   * Opens a specified file and loads its contents into an internal container.
	   *
		 * @param string $file_path Full system path to the file to open.
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return void
		 * @throws \Exception
	   */
		public function __construct( $file_path ) {
			if( file_exists($file_path) && is_readable($file_path) ) {

				set_time_limit(180);

				$this->file = $file_path;
				$file_parts = pathinfo($this->file);

				switch( $file_parts['extension']  ) {
					case 'fdr':
						$this->version = 7;
						throw new Exception('Final Draft 7 (FDR) files are not supported.');
					break;

					case 'fdx':
						$this->version = 9;
						$this->content = simplexml_load_file($this->file);
					break;

					default:
						$this->version = -1;
						throw new Exception('Unknown file format.');
					break;
				}
			}

		}
	}
