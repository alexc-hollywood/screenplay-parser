<?php
/**
* Filter/parser for Fountain (.fountain) markdown files. 2 classes: uses FountainParser
*
* Opens a Fountain file (markdown format), parses the markdown structure, then provides a
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
 * Filter definition to parse Fountain markdown and return the data items.
**/

class Fountain implements ExtractorInterface {

	/**
  * Container for the internal parser class
  * @var mixed
  */
	private $parser;

	/**
  * Holds the markdown elements of the document
  * @var array
  */
	private $elements;

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
			if( count($this->elements) ) {
				foreach($this->elements->elements As $i => $element) {
					if( isset($element->type) && $element->type == 'Character' ) {

						if( !empty($element->text) && $element->text != 'A' ) {
							if( !in_array($element->text, $this->characters) && stristr($element->text, '(') === FALSE ) {
								array_push($this->characters, strtoupper($element->text));
							} // if not in array
						} // match isn't A
					}

				}
			}

			sort($this->characters);
			return $this->characters;
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
			if( count($this->elements) ) {
				foreach($this->elements->elements As $i => $element) {
					if( isset($element->type) && $element->type == 'Scene Heading' ) {

						if( !empty($element->text) ) {
							if( !in_array($element->text, $this->scenes) ) {
								array_push($this->scenes, strtoupper($element->text));
							} // if not in array
						} // match isn't A
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
			if( count($this->elements) ) {
				foreach($this->elements->elements As $i => $element) {
					if( isset($element->type) && $element->type == 'Action' ) {

						 preg_match_all($this->uppercase_regex, $element->text, $capped);

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
					}

				}
			}

			sort($this->uppercase);
			return $this->uppercase;
		}

		/**
	   * Class constructor.
	   *
	   * Opens a specified file, and loads its contents into internal containers.
	   *
		 * @param string $file_path Full system path to the file to open.
	   * @author Alex Coppen (acamerondev@protonmail.com)
	   * @return void
		 * @throws \Exception
		 * @uses FountainParser
	   */
		public function __construct( $file_path ) {
			if( file_exists($file_path) ) {

				set_time_limit(180);

				$this->file = $file_path;
				$file_parts = pathinfo($this->file);

				$this->content = file_get_contents($file_path);

				$this->parser = new FountainParser();
				$this->parser->parse($this->content);
				$this->elements = $this->parser->elements();

			} else {
				throw new Exception($file_path.' not found.');
			}
		}
	}





/* EXTERNAL LIBS */


/**
 * FountainParser
 * Based off the FastFountainParser.m
 *
 * https://github.com/alexking/Fountain-PHP
 *
 * @author Alex King (PHP port)
 * @author Nima Yousefi & John August (original Objective-C version)
 */
class FountainParser {

	/**
	 * Parse a string into a collection of elements
	 *
	 * @todo add > centering < support
	 * @todo improve transition handling (when surrounded by blank lines)
	 * @todo add title parsing
	 *
	 * @param  string $contents Fountain formated text
	 */
	public function parse($contents) {

		// Trim newlines from the document
		$contents = trim($contents);

		// Convert \r\n or \r style newlines to \n
		$contents = preg_replace("/\r\n|\r/", "\n", $contents);

		// Add two line breaks to the end of the page (ref FastFountainParser.m:53)
		$contents .= "\n\n";



		// Keep track of preceding newlines
		$newlines_before = 0;
		$newline = FALSE;

		// Keep track of whether we are inside a comment block, and what its text is
		$comment_block = FALSE;
		$comment_text = "";

		// Keep track of whether we are inside a dialog block
		$dialog_block = FALSE;

		// Break into lines
		$lines = explode("\n", $contents);

		// Process each line
		foreach ($lines as $line_number => $line) {

			// Reset the newline count if necessary
        	if (!$newline) {
        		$newlines_before = 0;
        	}

			// Check for a blank line (is empty, or has whitespace characters)
			if (($line == "" || preg_match("/^\s*$/", $line)) && !$comment_block) {

				// Blank lines end dialog blocks
				$dialog_block = FALSE;

				// Increment newline count
				$newlines_before ++;
				$newline = TRUE;

				// No further processing of this line is needed
				continue;

			} else {

				// Note that this isn't a newline
				$newline = FALSE;
			}


			// Comment Blocks
			// Check whether a comment starts or ends on this line
			$comment_start = preg_match("/^\/\*/", $line);
			$comment_end = preg_match("/\*\/\s*$/", $line);

			// If this is the start, middle, or end of a comment block
			if ($comment_start || $comment_end || $comment_block) {

				// If it starts on this line
				if ($comment_start) {

					// Note this as the start of a comment block
					$comment_block = TRUE;

				}

				// If the comment continues on this line
				if ($comment_block) {

					// Add this line to the comment text
					$comment_text .= "$line\n";

				}

				// If the comment ends on this line
				if ($comment_end) {

					// Remove /* and */
					$comment_text = str_replace(array("/*", "*/"), "", $comment_text);

					// Add a Boneyard Element
					$this->add_element("Boneyard", $comment_text);

					// Note that we aren't in a comment anymore and reset the text
					$comment_block = FALSE;
					$comment_text = "";

				}

				// No further processing of this line is needed
				continue;

			}

			// Page Breaks (===)
			if (preg_match("/^={3,}\s*$/", $line)) {

				// Add a page break element
				$this->add_element("Page Break", $line);

				continue;
			}

			// Synopsis - if there aren't any preceding newlines, and there's a "="
			if ($newlines_before && substr(trim($line), 0, 1) == "=") {

				// Find the text of the synopsis
				preg_match("/^\s*={1}(.*)/", $line, $matches);

				// Add a synopsis element
				$this->add_element("Synopsis", $matches[1]);

				continue;
			}

			// Comment [[ ]] start spaces [[ spaces capture
			if ($newlines_before && preg_match("/^\s*\[{2}\s*([^\]\n])+\s*\]{2}\s*$/", $line)) {

				// Trim whitespace and [[ ]]
				$text = trim(str_replace(array("[[", "]]"), "", $line));

				// Add a comment element
				$this->add_element("Comment", $text);

				continue;
			}

			// Section heading - check if this line starts with a #
			if ($newlines_before && substr(trim($line), 0, 1) == "#") {

				// Find the number of # (##, ###, etc.) and the text
				preg_match("/^\s*(#+)\s*(.*)/", $line, $matches);
				list ($raw, $depth, $text) = $matches;

				// Convert depth to a number
				$depth = strlen($depth);

				// Add a Section Heading
				$this->add_element("Section Heading", $text, array(
					"depth" => $depth
				));

				continue;

			}

			// Scene Headings
			// Check if this is a forced or normal scene heading
			$forced_scene_heading = preg_match("/^\.[^\.]/", $line);
			$scene_heading = preg_match("/^(INT|EXT|EST|I\/??E)[\.\-\s]/i", $line, $scene_heading_matches);

			if ($forced_scene_heading || $scene_heading) {

				// Remove the prefix
				if ($forced_scene_heading) {
					$prefix_length = 1;
				} else {
					$prefix_length = strlen($scene_heading_matches[0]);
				}

				$line_without_prefix = substr($line, $prefix_length);

				// Find the text and optional scene number
				if (preg_match("/^(.*?)(?:|\s*#([^\n#]*?)#\s*)$/", $line_without_prefix, $matches)) {

					try {
						list($raw, $text, $scene_number) = $matches;
					} catch (\Exception $e) {

					}

					// Add a scene heading element
					$this->add_element("Scene Heading", $text, array(
						"scene_number" => $scene_number,
					));

				}

				continue;
			}

			// Transition - check whether it is in a list of transitions
			$transitions = array(
				"CUT TO:",
				"FADE OUT.",
				"SMASH CUT TO:",
				"CUT TO BLACK.",
				"MATCH CUT TO:"
			);

			if (in_array(trim($line), $transitions)) {

				// Add a transition element
				$this->add_element("Transition", $line);

				continue;
			}

			// Forced Transition - check whether the line starts with > (and doesn't end with <, which would make it a centered action)
			if (substr($line, 0, 1) == ">" && substr($line, strlen($line) - 1) != "<") {

				// Find the text
				$text = substr($line, 1);

				// Add a transition element
				$this->add_element("Transition", $text);

				continue;
			}


   			// Character - check if there is a newline preceding, and consists of entirely uppercase characters
   			if ($newlines_before && preg_match("/^[^a-z]+$/", $line)) {

   				// Make sure the next line isn't blank or non-existent
   				if (isset($lines[$line_number + 1]) && $lines[$line_number + 1] != "") {

   					// This is a character, check if it's dual dialog
   					$dual_dialog = FALSE;

   					if (preg_match("/\^\s*$/", $line)) {

   						// It is dual dialog,
   						$dual_dialog = TRUE;

   						// Check for a previous character - grab it by reference if it exists
   						if ($previous_character = &$this->elements()->find_last_element_of_type("Character")) {

   							// Set it to dual dialog
   							$previous_character->dual_dialog = TRUE;

   						}

   					}

   					// Add a character element
   					$this->add_element("Character", $line, array(
   						"dual_dialog" => $dual_dialog
   					));

   					// Note that we're within a dialog block
   					$dialog_block = TRUE;

   					continue;
   				}
   			}

   			// Dialog (and Parentheticals) - check if we're inside a dialog block
   			if ($dialog_block) {

   				// Check if there are newlines preceding, and if there is a (
   				if (!$newlines_before && preg_match("/^\s*\(/", $line)) {

   					// Add a parenthetical element
   					$this->add_element("Parenthetical", $line);

   				} else {

   					// Check if the previous element was dialogue
   					$last_element = &$this->elements()->last_element();
   					if ($last_element->type == "Dialogue") {

   						// The previous element was dialogue, so we'll combine the text and set it
   						$last_element->text .= "\n" . $line;

   					} else {

   						// Create a new dialogue element
   						$this->add_element("Dialogue", $line);

   					}

   				}

   				continue;

   			}


   			// If there were no newlines, and this isn't our first element
   			if (!$newlines_before && $this->elements()->count()) {

   				// Find the previous element
   				$last_element = &$this->elements()->last_element();

   				// Add this line to it and save it back
   				$last_element->text .= "\n" . $line;

   				continue;

   			} else {

   				// Add an action element
   				$this->add_element("Action", $line);

   				continue;
   			}


		}

	}

	public function parse_file($filepath) {

		// Load the file, and parse
		$this->parse(file_get_contents($filepath));

	}

	/**
	 * Element Collection
	 * @var FountainElementCollection
	 */
	protected $_elements;

	public function elements() {

		if (!$this->_elements) {
			$this->_elements = new FountainElementCollection;
		}

		return $this->_elements;
	}

	public function add_element($type, $text, $extras = array()) {

		$this->elements()->create_and_add_element($type, $text, $extras);
	}



}

class FountainElementCollection {

	public $elements;
	public $types;

	/**
	 * Add and index the element
	 * @param FountainElement $element
	 */
	public function add_element(FountainElement $element) {

		// Add to the element array
		$this->elements[] = $element;

		// Add to the types array for quick searching
		$this->types[] = $element->type;

	}

	/**
	 * Convenience function for creating and adding a FountainElement
	 */
	public function create_and_add_element($type, $text, $extras = array()) {

		// Create
		$element = new FountainElement($type, $text, $extras);

		// Add to the collection
		$this->add_element($element);

	}

	/**
	 * Find the most recent element by type
	 * @param  string 	$type 	type of element
	 * @return mixed 	FountainElement or FALSE
	 */
	public function &find_last_element_of_type($type) {

		// Reverse the index
		$types = array_reverse((array) $this->types, TRUE);

		// Find the last one
		$index = array_search($type, $types, TRUE);

		// Return if successful
		if ($index) {
			return $this->elements[$index];
		} else {
			return FALSE;
		}
	}

	/**
	 * Find the last element
	 * @return mixed 	FountainElement or FALSE
	 */
	public function &last_element() {
		if ($count = count($this->elements)) {
			return $this->elements[$count - 1];
		} else {
			return FALSE;
		}
	}

	/**
	 * Return the number of elements
	 * @return int
	 */
	public function count() {
		return count($this->elements);
	}

	/**
	 * Convert to string
	 */
	public function __toString() {

		$string = "";
		foreach ((array) $this->elements as $element) {
			$string .= (string) $element . "\n";
		}

		return $string;

	}

}

class FountainElement {

	public $type;
	public $text;
	public $extras;

	/**
	 * Construct a new FountainElement
	 * @param string  $type   Character, Dialog, etc.
	 * @param string  $text   Text for the element
	 * @param array   $extras Additional properties
	 */
	public function __construct($type, $text, $extras = array()) {

		// Assign the type and text
		$this->type = $type;
		$this->text = $text;

		// Assign the extras
		if (count($extras)) {
			foreach ((array) $extras as $key => $value) {
				$this->{$key} = $value;
			}
		}

	}

	/**
	 * Convert to String
	 * @return string
	 */
	public function __toString() {
		$string .= strtoupper($this->type) . ":" . $this->text;

		if ($this->dual_dialog) {
			$string .= "(DUAL)";
		}

		return $string;
	}

}
