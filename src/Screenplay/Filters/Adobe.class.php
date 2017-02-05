<?php
/**
* Filter/parser for Adobe Story (.astx) XML file
*
* Opens an ASTX file, traverses it using simplexml_load_file(), then provides a
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
 * Filter definition to parse Adobe Story XML and return the data items.
**/

class Adobe implements ExtractorInterface {

	/**
  * Path to the file we need to open.
  * @var string
  */
	private $file = '';

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
			if( isset( $this->content->smartTypes->Cast ) ) {
				foreach( $this->content->smartTypes->Cast->Character AS $member ) {
					$attrs = $member->attributes();
					array_push($this->characters, strval($attrs['name']));
				}

				// CHECK CAST LIST: go through all dialogue elements to check for missing characters that haven't been catalogued
				if( isset( $this->content->document->stream[0]->section->scene ) ) {
					foreach( $this->content->document->stream[0]->section->scene AS $s ) {

						foreach( $s->paragraph AS $p ) {
							$attrs = $p->attributes();

							if( isset($attrs['element']) &&  $attrs['element'] == 'Character') {
								if( !empty(strval($p->textRun)) ) {
									if( !in_array(strval($p->textRun), $this->characters) && stristr(strval($p->textRun), '(') === FALSE ) {
										array_push($this->characters, strtoupper(strval($p->textRun)));
									}
								}
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
			if( isset( $this->content->document->stream[0]->section->scene ) ) {
				foreach( $this->content->document->stream[0]->section->scene AS $s ) {

					foreach( $s->paragraph AS $p ) {
						$attrs = $p->attributes();

						if( isset($attrs['element']) &&  $attrs['element'] == 'SceneHeading') {
							if( !empty(strval($p->textRun)) ) {
								array_push($this->scenes, strval($p->textRun));
							}
						}
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
			if( isset( $this->content->document->stream[0]->section->scene ) ) {
				foreach( $this->content->document->stream[0]->section->scene AS $s ) {

					foreach( $s->paragraph AS $p ) {
						$attrs = $p->attributes();
						if( isset($attrs['element']) &&  $attrs['element'] == 'Action') {

							foreach($p->textRun AS $t) {

								 preg_match_all($this->uppercase_regex, strval($t), $matches);

								 if( count($matches) && isset($matches[0]) ) {
									if( count($matches[0]) ) {
										foreach($matches[0] As $match) {
											$match = trim($match);
											if( !empty($match) && $match != 'A' ) {
												if( !in_array($match, $this->uppercase) && !in_array($match, $this->characters) ) {
												array_push($this->uppercase, $match);

												} // if not in array
											} // match isn't A
										} // foreach matches
									} // if count
								 } // end if count
							} // end textrun loop

						} // end if action
					} // end foreach
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
		public function __construct($file_path) {
			if( file_exists($file_path) && is_readable($file_path) ) {

				set_time_limit(180);

				$this->file = $file_path;
				$file_parts = pathinfo($this->file);

				try {
					$this->content = simplexml_load_file($this->file);
				} catch( \Exception $e) {

				}
			}
		}
	}
