<?php
/**
* Filter/parser for FadeIn (.fadein) XML archive, which uses Open Screenplay Format (OSF)
*
* Opens an FadeIn OSF archive file, traverses the contents using simplexml_load_file(), then provides a
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

/**
 * Filter definition to parse FadeIn XML archive and return the data items.
**/

class OSF implements ExtractorInterface {

	/**
  * Container for the internal XML document
  * @var string
  */
	private $document_xml;

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
   * Traverses the XML DOM to find cast members and paragraphs identified as Character classes.
   *
   * @author Alex Coppen (acamerondev@protonmail.com)
   * @return array|boolean A list of characters, or false.
   */
		public function parse_characters() {
			if( isset( $this->content->lists->characters ) ) {

				foreach( $this->content->lists->characters->character AS $member ) {
					$attrs = $member->attributes();

					if( !in_array(strtoupper(strval($attrs['name'])), $this->characters) ) {
						array_push($this->characters, strtoupper(strval($attrs['name'])));
					}

				}

				// CHECK CAST LIST: go through all dialogue elements to check for missing characters that haven't been catalogued
				if( isset( $this->content->paragraphs ) ) {
					foreach( $this->content->paragraphs->para AS $s ) {
						$t = $s->style->attributes()['basestylename'];

						if( strval($t) == 'Character' ) {
							if( !empty(strval($s->text)) ) {
								if( !in_array(strval($s->text), $this->characters) && stristr(strval($s->text), '(') === FALSE ) {
									array_push($this->characters, strtoupper(strval($s->text)));
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
	   * Traverses the HTML DOM to find paragraph headings with class "Scene Heading"
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of scenes.
	   */
		public function parse_scenes() {
			if( isset( $this->content->paragraphs ) ) {
				foreach( $this->content->paragraphs->para AS $s ) {

					$t = $s->style->attributes()['basestylename'];

					if( strval($t) == 'Scene Heading' ) {
						if( !empty(strval($s->text)) ) {
							array_push($this->scenes, trim(strval($s->text)));
						}
					}

				}
			}
			return $this->scenes;
		}

		/**
	   * Retrieves a list of CAPITALIZED items needing emphasis.
	   *
	   * Traverses the XML DOM to preg_match all UPPERCASE words in action paragraphs with class "Action".
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of capitalized words.
	   */
		public function parse_capitalized() {
			if( isset( $this->content->paragraphs ) ) {
				foreach( $this->content->paragraphs->para AS $s ) {
					$t = $s->style->attributes()['basestylename'];

					if( strval($t) == 'Action' ) {
						 preg_match_all($this->uppercase_regex, strval($s->text), $matches);

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
					}
				}
			}
			return $this->uppercase;
		}

		/**
	   * Class constructor.
	   *
	   * Opens a specified file, unzips it, loads its contents into internal containers.
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

				if( $file_parts['extension'] == 'fadein' ) {
					$zip = zip_open($this->file);
					if( !$zip || !is_resource($zip) ) {
						throw new \Exception('Unable to open Fadein file.');
					} else {

						while ( $zip_entry = zip_read($zip) ) {

							if( zip_entry_name($zip_entry) == 'document.xml' ) {
								if (zip_entry_open($zip, $zip_entry)) {
									$this->document_xml = zip_entry_read($zip_entry, 1000000);
								}
							}
							zip_entry_close($zip_entry);
						}
						$this->content = simplexml_load_string($this->document_xml);
					}

				}
				else if( $file_parts['extension'] == 'xml' ) {
					$this->content = simplexml_load_file($file_path);
				}
				else {
					throw new Exception('Unknown file format.');
				}

			} else {
				throw new Exception($file_path.' not found.');
			}
		}
	}
