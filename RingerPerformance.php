<?php
require_once('DatabaseRecord.php');
require_once('functions.php');

class RingerPerformance extends DatabaseRecord {
  public $bell = 0;
  public $credit = '';
  public $conductor = false;
  public $footnote = ''; // At present, only footnotes derived from constructor

  public function __construct($str, $bell) {
    if(!$str) return;

    $this->bell = (int) $bell;

    $str = utf8_decode($str);

    // Check for conductor, and strip (C... if found
    if(($end = stripos($str, '(c')) !== false) {
      $this->name = mytrim(substr($str, 0, $end));
      $this->conductor = true;
    } else {
      $this->name = mytrim($str);
    }

    // Check for further in-name footnotes (will not find additional ones after (c))
    if(($footnote = strstr($this->name, '(')) !== false) {
      $this->footnote = mytrim($footnote, '()');
      $this->name = mytrim(str_replace($footnote, '', $str));
    }
  }
}
