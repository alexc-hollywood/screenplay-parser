<?php
/**
* Screenplay filter interface.
*
* Provides standardized methods for filters.
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

/**
 * Abstract for all child filter methods.
**/

interface ExtractorInterface {

  /**
   * Definition for retrieving a list of characters.
   *
   * @access public
   * @author Alex Coppen (azcoppen@protonmail.com)
   */
  public function parse_characters();

  /**
   * Definition for retrieving a list of scenes.
   *
   * @access public
   * @author Alex Coppen (azcoppen@protonmail.com)
   */
  public function parse_scenes();

  /**
   * Definition for retrieving a list of capitalized items.
   *
   * @access public
   * @author Alex Coppen (azcoppen@protonmail.com)
   */
  public function parse_capitalized();

}
