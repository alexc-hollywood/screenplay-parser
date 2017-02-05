<?php
/**
* Filter/parser for Rich Text (.rtf) screenplay files
*
* Opens a rich text (rtf) script export, parses the markdown structure, then provides a
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
 * Filter definition to parse rich text and return the data items.
**/

class RTF implements ExtractorInterface {

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
  * Regex to find scene headings in the RTF encoding.
  * @var string
  */
	private $scene_string   = '/\{\\\pard\\\plain \\\ql \\\caps\\\sb480\\\f469\\\fs24\\\sl200\\\s2\\\fi0\\\ri1100\\\li2160(.*?)\\\par \}/is';

	/**
  * Regex to find character elements in the RTF encoding.
  * @var string
  */
	private $char_string 	= '/\{\\\pard\\\plain \\\ql \\\caps\\\sb240\\\f469\\\fs24\\\sl200\\\s4\\\fi0\\\ri1460\\\\li5040(.*?)\\\par \}/is';

	/**
  * Regex to find action elements in the RTF encoding.
  * @var string
  */
	private $action_string 	= '/\{\\\pard\\\plain \\\ql \\\sb240\\\f469\\\fs24\\\sl200\\\s3\\\fi0\\\ri1100\\\li2160(.*?)\\\par \}/is';


	/**
   * Retrieves a list of character listed items in the document.
   *
   * Traverses the RTF text to find cast members and paragraphs identified as character strings.
   *
   * @author Alex Coppen (acamerondev@protonmail.com)
   * @return array|boolean A list of characters, or false.
   */
		public function parse_characters() {
			 preg_match_all($this->char_string, $this->content, $matches);
			 //var_dump($matches);
			 if( count($matches) && isset($matches[1]) ) {
				if( count($matches[1]) ) {
					foreach($matches[1] As $match) {

						$match = trim($match);

						if( !empty($match) && $match != 'A' ) {
							if( !in_array($match, $this->characters) && stristr($match, '(') === FALSE ) {
								array_push($this->characters, strtoupper($match));
							} // if not in array
						} // match isn't A

					} // foreach matches
				} // if count
			 } // end if count

			 sort($this->characters);
			 return $this->characters;
		}

		/**
	   * Retrieves a list of scene items in the document.
	   *
	   * Traverses the RTF text to preg_match scene headings.
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of scenes.
	   */
		public function parse_scenes() {
			 preg_match_all($this->scene_string, $this->content, $matches);

			 if( count($matches) && isset($matches[1]) ) {
				if( count($matches[1]) ) {
					foreach($matches[1] As $match) {

						$match = trim($match);

						if( !empty($match) && $match != 'A' ) {
							if( !in_array($match, $this->scenes) ) {
								array_push($this->scenes, strtoupper($match));
							} // if not in array
						} // match isn't A

					} // foreach matches
				} // if count
			 } // end if count
			 return $this->scenes;
		}

		/**
	   * Retrieves a list of CAPITALIZED items needing emphasis.
	   *
	   * Traverses the RTF text to preg_match all UPPERCASE words in action elements.
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of capitalized words.
	   */
		public function parse_capitalized() {
			 preg_match_all($this->action_string, $this->content, $matches);

			 if( count($matches) && isset($matches[1]) ) {
				if( count($matches[1]) ) {
					foreach($matches[1] As $match) {

						 preg_match_all($this->uppercase_regex, strval($match), $capped);

						 if( count($capped) && isset($capped[0]) ) {
							if( count($capped[0]) ) {
								foreach($capped[0] As $cap) {

									$cap = trim($cap);

									if( !empty($cap) && $cap != 'A' ) {
										if( !in_array($cap, $this->uppercase) && !in_array($cap, $this->characters) ) {
											array_push($this->uppercase, $cap);
										} // if not in array
									} // match isn't A

								} // foreach matches

							} // if count
						 } // end if count



					} // foreach matches
				} // if count
			 } // end if count

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

				$this->content = file_get_contents($file_path);

			} else {
				throw new Exception($file_path.' not found.');
			}
		}
	}
