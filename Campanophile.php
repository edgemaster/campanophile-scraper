<?php
require_once('Performance.php');

class Campanophile {
  const CBASE = 'http://www.campanophile.com/';
  private $session_key = '';

  /***
   * Constructors
   ***/

  private function __construct($session) {
    if(!$session) {
      $this->begin_session();
    } else {
      $this->session_key = $session;

      // Check the given session is still active
      if(!$this->test_session()) {
        $this->begin_session();
      }
    }
  }

  private function __clone() {}

  public function getInstance($session = '') {
    // Singleton class
    static $instance = null;
    if(!$instance) $instance = new self($session);

    return $instance;
  }

  /***
   * Public methods
   ***/

  public function search($params = array()) {
    /***
     * Returns outline set of matching performances
     *
     * $params is an array of any of the following:
     *   StartDate: "dd/mm/yyyy" // defaults to today - 1 year
     *   FinalDate: "dd/mm/yyyy" // defaults to today
     *   Guild: ""
     *   Location: ""
     *   County: ""
     *   Dedication: ""
     *   Method: ""
     *   Composer: ""
     *   Ringer: ""
     *   TypeCode: 0 // Peals
     *   TypeCode: 1 // Quarters
     *   TypeCode: 2 // Both the above (default)
     ***/

    $defaults = array(
      'StartDate' => date('d/m/Y', time()-31556926),
      'FinalDate' => date('d/m/Y'),
      'TypeCode'  => 2
    );

    $curl = curl_init($this->gen_url('find2'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,
        http_build_query($params + $defaults));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $results_html = curl_exec($curl);
    return $this->parse_search_results($results_html);
  }

  public function get_performance($campano_id, $perf = null) {
    /***
     * Returns Performance object of specific Campanophile (Quarter) Peal
     * specified by given reference number, $campano_id
     *
     * Optionally updates given Performance object $perf
     ***/
    
    print_r($this->get_page('view2', $campano_id));

  }

  /***
   * Helper functions
   ***/
  
  //
  // Site access
  //

  private function gen_url($name, $suff='') {
    return self::CBASE . $name . '.aspx?' . $this->session_key . $suff;
  }

  private function get_page($name, $suff='') {
    return file_get_contents($this->gen_url($name, $suff));
  }

  private function begin_session() {
    $homepage = file_get_contents(self::CBASE . 'default.aspx');
    if(preg_match('/"menu.aspx\?([^\"]*)"/', $homepage, $matches)) {
      $this->session_key = $matches[1];
      return true;
    }
    return false; // Should probably try catching the error
  }

  private function test_session() {
    $menu = $this->get_page('menu');
    return strpos($menu, 'expired') === FALSE; // Since can return 0
  }

  // 
  // Page parsing
  //

  private function parse_search_results($html) {
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $rows = $dom->getElementsByTagName('tr');

    $performances = array();

    for($i = 1; $i < $rows->length; $i++) {
      $node = $rows->item($i);
      $p = new Performance();

      // First cell, date and hyperlink, including ID code appended to sessid
      $p->date = strtotime($node->firstChild->textContent);
      $loc = $node->firstChild->firstChild->attributes->getNamedItem('href')->textContent;
      $loc = explode($this->session_key, $loc);
      $p->campano_id = (int) $loc[1];

      // Second cell, locational details
      $matches = $this->parse_location($node->firstChild->nextSibling->textContent);
      $p->apply_array($matches);

      // Third cell, method details
      $matches = $this->parse_method($node->lastChild->textContent);
      $p->apply_array($matches);

      $performances[] = $p;
    }

    return $performances;
  }

  private function parse_location($str) {
    // String of form "Location (Dedication), County"
    // Returns array containing keys of 'location', 'dedication' and 'county'
    preg_match(
      '/^(?P<location>.*?)(?: \((?P<dedication>.*)\))?(?:, (?P<county>[^,]*))?$/',
      $str, $matches);
    return $matches;
  }

  private function parse_method($str) { 
    // 1234 Funny Principle Major
    preg_match('/(?P<changes>\d+) (?P<method>.*)/', $str, $matches);
    $matches['changes'] = (int) $matches['changes'];
    return $matches;
  }

  private function parse_length($str) {
    preg_match('/(?:(?P<h>\d{1,2})\D*)?(?P<m>\d{2})\D*$/', $str, $matches);
    $len = $matches['h'] * 60 + $matches['m'];
    return $len;
  }

}

$c = Campanophile::getInstance();

$r = $c->search(array('StartDate' => '26/05/2010', 'FinalDate' => '26/05/2010', 'Method' => 'Plain Bob Major'));

$r[0]->fetch_details();
?>
