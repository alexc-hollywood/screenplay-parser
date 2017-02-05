<?php
/**
* Screenplay loader/reader/extractor
*
* Opens a specified screenplay file, invokes the appropriate filter from the
* extension, and provides accessors to the parsed data.
*
* LICENSE: MIT
*
* @category   PHP
* @package    Screenplay
* @author     Alex Coppen (azcoppen@protonmail.com)
* @copyright  Copyright (c) 2016 Alex Coppen
* @license    http://framework.zend.com/license BSD License
* @version    $Id:$
* @since      File available since Release 1.0
*/

namespace Screenplay;

use \Exception;

use Screenplay\Filters\FinalDraft;
use Screenplay\Filters\Celtx;
use Screenplay\Filters\RTF;
use Screenplay\Filters\Adobe;
use Screenplay\Filters\OSF;
use Screenplay\Filters\Fountain;

/**
 * Loads screenplay files and performs generic parsing of the content for breakdowns.
**/

class Extractor {

	/**
  * Path to the file we need to open.
  * @var string
  */
	public $file;

	/**
  * Holds the directory of the specified file (from pathinfo)
  * @var string
  */
	public $directory;

	/**
  * Holds the BASENAME of the specified file (from pathinfo)
  * @var string
  */
	public $filename;

	/**
  * Holds the extension of the specified file (from pathinfo)
  * @var string
  */
	public $extension;

	/**
  * Holds the filename of the specified file (from pathinfo)
  * @var string
  */
	public $file_base;

	/**
  * Internal container for the filter being invoked (FD, Story etc)
  * @var mixed
  */
	public $screenplay;

	/**
  * List of allowed file extensions
  * @var array
  */
	public $allowed_exts = Array('astx', 'celtx', 'fadein', 'fountain', 'fdx', 'rtf', 'xml');

	/**
   * Accessor for getting the parsed data.
   *
   * @access public
   * @author Alex Coppen (azcoppen@protonmail.com)
   * @return mixed The parsed data from the child filter.
   */
		public function analyzer() {
			return $this->screenplay;
		}

		/**
	   * Class constructor.
		 *
		 * Checks for the existence of a specified file, auto-loads the filter.
	   *
	   * @access public
		 * @param string $file_path Full system path to the file to be parsed.
	   * @author Alex Coppen (azcoppen@protonmail.com)
	   * @return mixed Container for the filter, or boolean.
   	 * @throws \Exception
	   */
		public function __construct( $file_path = null ) {
			if( !empty($file_path) && file_exists( $file_path ) && is_readable($file_path) ) {

				set_time_limit(180); // big files need more time.

				$this->file = $file_path;
				$file_parts = pathinfo($this->file);

				$this->directory 	= $file_parts['dirname'];
				$this->filename 	= $file_parts['basename'];
				$this->extension 	= $file_parts['extension'];
				$this->file_base 	= $file_parts['filename'];

				if( in_array($this->extension, $this->allowed_exts) ) {
					try {
						switch( strtolower($this->extension) ) {

							case 'fdx':
								$this->screenplay = new FinalDraft( $this->file );
							break;

							case 'celtx':
								$this->screenplay = new Celtx( $this->file );
							break;

							case 'rtf':
								$this->screenplay = new RTF( $this->file );
							break;

							case 'astx':
								$this->screenplay = new Adobe( $this->file );
							break;

							case 'fadein':
							case 'xml':
								$this->screenplay = new OSF( $this->file );
							break;

							case 'fountain':
								$this->screenplay = new Fountain( $this->file );
							break;

							default:
								$this->screenplay = false;
							break;
						}

						return $this->screenplay;

					} catch( Exception $e) {

					}

				} else {
					throw new Exception('File extension '.$this->extension.' is not allowed.');
				}

			} else {
				throw new Exception('File '.$this->filename.' does not exist or is not readable.');
			}
		}

	}
