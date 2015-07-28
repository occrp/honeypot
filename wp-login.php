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

# specimen 
define('SPECIMEN_DIR', 'specimens/' . $_SERVER["HTTP_HOST"]);
define('DUMP_DIR', 'dumps/' . $_SERVER["HTTP_HOST"]);

# is the domain name within our scope?
if (!file_exists(SPECIMEN_DIR) or !is_dir(SPECIMEN_DIR)) {
  header('HTTP/1.1 500 Internal Server Error');
  exit(0);
}


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
  # replace whatever is there to be replaced, if there is anything to be replaced, that is
  $html = replace_credentials($html);
  # print the damn thing
  dbg("printing out html"); # DEBUG
  print($html);
}


# replacing everything that needs to be replaced
function replace_credentials($str) {
  dbg("handling credentials...");
  # we only operate when POST is in effect
  if($_SERVER['REQUEST_METHOD'] === 'GET') return $str;
  # get the creds, if available
  $user = "";
  $mail = "";
  $pass = "";
  
  # that one's simple
  if (isset($_POST['pwd'])) {
    $pass = $_POST['pwd'];
    dbg("pass: $pass");
  }
  
  # if we have 'log' set, that's the username and we're done
  if (isset($_POST['log'])) {
    $user = $_POST['log'];
    dbg("user from log: $user");
    
  # otherwise, if user_login does not contain '@', assume a user login
  } elseif (isset($_POST['user_login'])) {
    if (strpos($_POST['user_login'], '@') === false) {
      $user = $_POST['user_login'];
      dbg("user from user_login: $user");
      
    # otherwise assume e-mail
    } else {
      $mail = $_POST['user_login'];
      dbg("email from user_login: $mail");
    }
  }
  
  # replace
  dbg("replacing...");
  $str = str_replace('HONEYUSER', $user, $str);
  $str = str_replace('HONEYPASS', $pass, $str);
  $str = str_replace('HONEYMAIL@EXAMPLE.COM', $mail, $str);
  # return the string
  return $str;
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

# is method GET or POST?
if($_SERVER['REQUEST_METHOD'] === 'GET') {
  dbg("GET!");
  
  # is the "action" get parameter set to "lostpassword?
  if ( (isset($_GET['action'])) and ($_GET['action'] === 'lostpassword') ) {
    dbg("lostpassword");
    output_specimen_headers(SPECIMEN_DIR . '/lostpass.headers');
    output_specimen_html(SPECIMEN_DIR . '/lostpass.html');
  # nah, just a "clean" login site
  } else {
    dbg("clean");
    output_specimen_headers(SPECIMEN_DIR . '/clean.headers');
    output_specimen_html(SPECIMEN_DIR . '/clean.html');
  }
  
# a bit more tricky, now!
} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
  dbg("POST!");
  
  # is it the lostpassword page?
  if (isset($_GET['action']) and ($_GET['action'] === 'lostpassword') ) {
    dbg("lostpassword");
    
    # okay, do we have *anything* in POST?
    if (isset($_POST['user_login']) and ! empty($_POST['user_login'])) {
      dbg("user_login present");
      
      # is it an e-mail?
      if (strpos($_POST['user_login'], '@') !== false) {
        dbg("user_login is email");
        output_specimen_headers(SPECIMEN_DIR . '/lostpass_email.headers');
        output_specimen_html(SPECIMEN_DIR . '/lostpass_email.html');
      
      # user login, apparently!
      } else {
        dbg("user_login is username");
        output_specimen_headers(SPECIMEN_DIR . '/lostpass_user.headers');
        output_specimen_html(SPECIMEN_DIR . '/lostpass_user.html');
      }
    
    # nah, just a clean page pls
    } else {
      dbg("clean lostpass plz");
      output_specimen_headers(SPECIMEN_DIR . '/lostpass.headers');
      output_specimen_html(SPECIMEN_DIR . '/lostpass.html');
    }
  
  # it's the login page, then,
  } else {
    dbg("login page");
  
    # do we have the user?
    if (isset($_POST['log']) and ! empty($_POST['log'])) {
      dbg("log present");
      
      # do we also have the password?
      if (isset($_POST['pwd']) and ! empty($_POST['pwd'])) {
        dbg("pwd present");
        # both!
        output_specimen_headers(SPECIMEN_DIR . '/userpass.headers');
        output_specimen_html(SPECIMEN_DIR . '/userpass.html');
      } else {
        # just the user
        output_specimen_headers(SPECIMEN_DIR . '/user.headers');
        output_specimen_html(SPECIMEN_DIR . '/user.html');
      }
      
    } elseif (isset($_POST['pwd'])  and ! empty($_POST['pwd'])) {
      dbg("pwd present");
      # just the pasword
      output_specimen_headers(SPECIMEN_DIR . '/password.headers');
      output_specimen_html(SPECIMEN_DIR . '/password.html');
      
    # nothing is supplied, just display a clean login page
    } else {
      dbg("nothing present, clean login page");
      output_specimen_headers(SPECIMEN_DIR . '/clean.headers');
      output_specimen_html(SPECIMEN_DIR . '/clean.html');
    }
    
  }
  
}

?>