<?php

# debug
define('DEBUG', false);

# we don't want to leak any data whatsoever
if (DEBUG) {
  error_reporting(E_ALL); # DEBUG
  ini_set("display_errors", 1);
} else {
  error_reporting(0);
  ini_set("display_errors", 0);
}

$GLOBALS['DEBUGNUM']=0;
function dbg($txt) {
  if (DEBUG) {
    header("X-OCCRP-Debug-{$GLOBALS['DEBUGNUM']}: $txt");
    $GLOBALS['DEBUGNUM'] += 1;
  }
}

# dump dir
define('DUMP_DIR', 'dumps/' . $_SERVER["HTTP_HOST"]);


# getting the specimen headers
# replacing whatever is to be replaced
# and dumping them out
function output_specimen_headers($f) {
  $headers = file($f);
  foreach ($headers as $l) {
    if (strpos($l, 'Last-Modified:')) {
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s T'));
    } elseif (strpos($l, 'Date:')) {
      header('Date: ' . gmdate('D, d M Y H:i:s T'));
    } else {
      header($l);
    }
  }
  dbg('debugging enabled');
}


# getting the specimen from file
# replacing whatever is to be replaced
# and dumping it out
function output_specimen_html($f) {
  dbg("specimen-file $f"); # DEBUG
  # get the specimen
  $html = file_get_contents($f);
  # print the damn thing
  dbg("printing out html"); # DEBUG
  print($html);
}


# the important part -- dumping all the data
function dump_request_data() {
  # if the directory does not exist, create it
  if (!file_exists(DUMP_DIR)) {
    mkdir(DUMP_DIR, 0777, true);
  }
  # compute the filename
  $fname = $_SERVER['REQUEST_METHOD'] . '.' . $_SERVER['HTTP_X_REAL_IP'] . '.' . date('Y-m-d.H:i:s', $_SERVER['REQUEST_TIME']) . '.' . explode('.', $_SERVER['REQUEST_TIME_FLOAT'])[1] . '.dump';
  dbg("dump filename: $fname");
  # get the data
  $data = '$_GET = ' . var_export($_GET, true) . ";\n";
  $data .= '$_POST = ' . var_export($_POST, true) . ";\n";
  $data .= '$_SERVER = ' . var_export($_SERVER, true) . ";\n";
  $data .= '$_COOKIE = ' . var_export($_COOKIE, true) . ";\n";
  # dump it!
  dbg("dumping...");
  file_put_contents(DUMP_DIR . "/$fname", $data);
}

dump_request_data();
output_specimen_headers('504.headers');
output_specimen_html('504.html');

?>