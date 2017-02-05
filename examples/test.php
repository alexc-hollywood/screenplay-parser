<?php

  require('../src/Screenplay/ExtractorInterface.php');
  require('../src/Screenplay/Extractor.class.php');

  require('../src/Screenplay/Filters/Adobe.class.php');
  require('../src/Screenplay/Filters/Celtx.class.php');
  require('../src/Screenplay/Filters/FinalDraft.class.php');
  require('../src/Screenplay/Filters/Fountain.class.php');
  require('../src/Screenplay/Filters/OSF.class.php');
  require('../src/Screenplay/Filters/RTF.class.php');

  try {

    $extractor = new Screenplay\Extractor( 'test.fdx' );

    $scenes 	= $extractor->analyzer()->parse_scenes();
    $chars 		= $extractor->analyzer()->parse_characters();
    $elements = $extractor->analyzer()->parse_capitalized();

    print_r(json_encode($scenes, JSON_PRETTY_PRINT));
    print_r(json_encode($chars, JSON_PRETTY_PRINT));
    print_r(json_encode($elements, JSON_PRETTY_PRINT));

  } catch (\Exception $e) {
    var_dump( $e->getMessage() );
  }
