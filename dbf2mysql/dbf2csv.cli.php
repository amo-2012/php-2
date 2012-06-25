<?php

  $db = false;

  if(!empty($argc)) {

    $file = end($argv);

    $db = dbase_open($file, 0);
  }

  if ($db) {

    $iconvFrom  = '866';
    $iconvTo    = 'UTF-8';
    $delimetr   = ',';

    $info         = dbase_get_header_info($db);
    $fields       = dbase_numfields($db);
    $fieldsCount  = sizeof($fields);
    $records      = dbase_numrecords($db);

    //for ($i = 1; $i <= 10; $i++) { # test
    for ($i = 1; $i <= $records; $i++) {
      $row  = dbase_get_record($db, $i);
      $line = array();
      for ($j = 0; $j < $fields; $j++) {
        $line[] =  addslashes(iconv($iconvFrom, $iconvTo, trim($row[$j])));
      }
      echo implode($delimetr, $line);
      echo PHP_EOL;
    }

    dbase_close($db);
  }
