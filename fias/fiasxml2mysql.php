<?php

  $file = end($argv);

  $sax = xml_parser_create();

  xml_set_element_handler($sax, 'tag_start', 'tag_end');

  //xml_parse_into_struct($sax, file_get_contents($file), false, $index);
  //print_r($values);
  //print_r($index);
  //xml_parse($sax, file_get_contents($file));

  //$mem = memory_get_usage();
  //echo PHP_EOL;


  $fp = fopen($file, 'r');
  while ($data = fread($fp, 4096)) {
      xml_parse($sax, $data, feof($fp));
      flush();
  }
  fclose($fp);

  xml_parser_free($sax);

  function tag_start($sax, $name, $attrs) {
    if (!empty($attrs)) {
      $sql = 'INSERT INTO `'. strtolower($name) .'` SET ';
      $values = array();
      foreach ($attrs as $attr => $val) {
        $values[] =  '`' . strtolower($attr) . '` = \'' . addcslashes($val, "'") . '\'';
      }
      $sql .= implode(',', $values);
      echo $sql . ';' . PHP_EOL;
    }
  }


  function tag_end($sax, $name) {
     //echo $name. PHP_EOL;
  }

  


  
