<?php
/**
* Filter/parser for Celtx (.celtx) archive/RDF files
*
* Opens a Celtx file (zip format), extracts the RDF package contents, then provides a
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
use \DomDocument;

/**
 * Filter definition to parse Celtx RDF archive and return the data items.
**/

class Celtx implements ExtractorInterface {

	/**
  * Path to the file we need to open.
  * @var string
  */
	private $file = '';

	/**
  * Container for the internal RDF document
  * @var string
  */
	public $project_rdf;

	/**
  * Container for the screenplay internal HTML
  * @var string
  */
	public $script_html;

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
			if( is_object($this->content) ) {
				foreach( $this->content->getElementsByTagName('p') AS $p ) {

					$attrs = $p->attributes;

					foreach($attrs AS $attr) {

						if( $attr->name == 'class' && $attr->value == 'character' ) {

							if( !in_array(strtoupper(strval($p->nodeValue)), $this->characters) ) {

								// don't add anything with a ( character
								if( !stristr(strval($p->nodeValue), '(') ) {
									array_push($this->characters, strtoupper(strval($p->nodeValue)));
								} // end if no parentheses
							} // end not in array

						} // end if char
					} // end foreach attrs
				} //end foreach p

				sort($this->characters);
				return $this->characters;

			} else {
				return false;
			}
		}

		/**
	   * Retrieves a list of scene items in the document.
	   *
	   * Traverses the HTML DOM to find paragraph headings with class "sceneheading"
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of scenes.
	   */
		public function parse_scenes() {
			if( is_object($this->content) ) {
				foreach( $this->content->getElementsByTagName('p') AS $p ) {
					$attrs = $p->attributes;
					foreach($attrs AS $attr) {
						if( $attr->name == 'class' && $attr->value == 'sceneheading' ) {
							if( !in_array(strtoupper(strval($p->nodeValue)), $this->scenes) ) {
									array_push($this->scenes, strtoupper(strval($p->nodeValue)));
							} // end not in array

						} // end if char
					} // end foreach attrs
				} //end foreach p
				return $this->scenes;

			} else {
				return false;
			}
		}

		/**
	   * Retrieves a list of CAPITALIZED items needing emphasis.
	   *
	   * Traverses the XML DOM to preg_match all UPPERCASE words in action paragraphs with class "action".
	   *
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return array A list of capitalized words.
	   */
		public function parse_capitalized() {
			foreach($this->content->getElementsByTagName('p') AS $p ) {
				$attrs = $p->attributes;
				foreach($attrs AS $attr) {
					if( $attr->name == 'class' && $attr->value == 'action' ) {

					 	preg_match_all($this->uppercase_regex, strval($p->nodeValue), $matches);

						 if( count($matches) && isset($matches[0]) ) {
							if( count($matches[0]) ) {
								foreach($matches[0] As $match) {
									$match = trim($match);
									if( !empty($match) && $match != 'A' ) {
										if( !in_array($match, $this->uppercase) && !in_array($match, $this->characters) ) {
											array_push($this->uppercase, $match);
										} // end if in array
									} // end if empty match
								} // endforeach matches
							} // end if count first matches
						 } // end if count
					 } // end if action
				} // end foreach atr
			}

			sort($this->uppercase);
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

				if( $file_parts['extension'] == 'celtx' ) {

					$zip = zip_open($this->file);

					if( !$zip || !is_resource($zip) ) {
						throw new Exception('Unable to open Celtx file.');
					} else {

						while ( $zip_entry = zip_read($zip) ) {

							if( zip_entry_name($zip_entry) == 'project.rdf' ) {
								if (zip_entry_open($zip, $zip_entry)) {
									$this->project_rdf = zip_entry_read($zip_entry, 1000000);

								}
							}

							if( stristr(zip_entry_name($zip_entry), 'script-') ) {
								if (zip_entry_open($zip, $zip_entry)) {
									$this->script_html = zip_entry_read($zip_entry, 1000000);
								}
							}

							zip_entry_close($zip_entry);
						} // end while

						$this->content = new DomDocument;

						$this->content->preserveWhiteSpace = false;
						$this->content->strictErrorChecking = false;

						libxml_use_internal_errors(true);

						if( !empty($this->script_html) ) {
							$this->content->loadHtml($this->script_html);
							libxml_clear_errors();
						} else {
							throw new Exception("No HTML found to load and parse.");
						}

					} // end else zip

				} else {
					throw new Exception('Unknown file format.');
				}

			}
		}
	}
