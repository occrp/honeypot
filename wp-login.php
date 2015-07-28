<?php

# we don't want to leak any data whatsoever
#error_reporting(0);
#ini_set("display_errors", 0);
error_reporting(E_ALL); # DEBUG
ini_set("display_errors", 1);

# specimen 
define('SPECIMEN_DIR', 'specimens/' . $_SERVER["HTTP_HOST"]);
define('DUMP_DIR', 'dumps/' . $_SERVER["HTTP_HOST"]);

# is the domain name within our scope?
if (!is_dir(SPECIMEN_DIR)) {
  header('HTTP/1.1 500 Internal Server Error');
}


# getting the specimen headers
# replacing whatever is to be replaced
# and dumping them out
function output_specimen_headers($f) {
  $headers = file($f);
  foreach ($f as $l) {
    if (strpos($l, 'Last-Modified:')) {
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s T'));
    } elseif (strpos($l, 'Date:')) {
      header('Date: ' . gmdate('D, d M Y H:i:s T'));
    } else {
      header($l);
    }
  }
  header('X-Rashiq-Testing: indeed'); # DEBUG
}


# getting the specimen from file
# replacing whatever is to be replaced
# and dumping it out
function output_specimen_html($f) {
  # get the specimen
  $html = file_get_contents($f);
  # replace whatever is there to be replaced, if there is anything to be replaced, that is
  $html = replace_credentials($html);
  # print the damn thing
  print($html);
}


# replacing everything that needs to be replaced
function replace_credentials($str) {
  # we only operate when POST is in effect
  if($_SERVER['REQUEST_METHOD'] === 'GET') return $str;
  # get the creds, if available
  $user = "";
  $mail = "";
  $pass = $_POST['pwd']; # that one's simple
  
  # if we have 'log' set, that's the username and we're done
  if ($_POST['log']) {
    $user = $_POST['log'];
    
  # otherwise, if user_login does not contain '@', assume a user login
  } elseif (strpos($_POST['user_login'], '@') === false) {
    $user = $_POST['user_login'];
    
  # otherwise assume e-mail
  } else {
    $mail = $_POST['user_login'];
  }
  
  # replace
  str_replace('HONEY', $user, $str);
  str_replace('HONEYPASS', $pass, $str);
  str_replace('HONEYEMAIL@EXAMPLE.COM', $mail, $str);
}


# the important part -- dumping all the data
function dump_request_data() {
  # if the directory does not exist, create it
  if (!file_exists(DUMP_DIR)) {
    mkdir(DUMP_DIR, 0777, true);
  }
  # compute the filename
  $fname = $_SERVER['REMOTE_ADDR'] . '.' . str_replace(' ', '.', microtime()) . '.dump';
  # get the data
  $data = '$_GET = ' . var_export($_GET, true);
  $data = '$_POST = ' . var_export($_POST, true);
  $data = '$_SERVER = ' . var_export($_SERVER, true);
  $data = '$_COOKIE = ' . var_export($_COOKIE, true);
  # dump it!
  file_put_contents($fname, $data);
}


# is method GET or POST?
if($_SERVER['REQUEST_METHOD'] === 'GET') {
  
  # is the "action" get parameter set to "lostpassword?
  if ($_GET['action'] === 'lostpassword' ) {
    output_specimen_headers(SPECIMEN_DIR . '/lostpass.headers');
    output_specimen_html(SPECIMEN_DIR . '/lostpass.html');
  # nah, just a "clean" login site
  } else {
    output_specimen_headers(SPECIMEN_DIR . '/clean.headers');
    output_specimen_html(SPECIMEN_DIR . '/clean.html');
  }
  
# a bit more tricky, now!
} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  # is it the lostpassword page?
  if (isset($_GET['action'] === 'lostpassword')) {
    # okay, do we have *anything* in POST?
    if (isset($_POST['user_login'])) {
      
      # is it an e-mail?
      if (strpos($_POST['user_login'], '@') !== false) {
        output_specimen_headers(SPECIMEN_DIR . '/lostpass_email.headers');
        output_specimen_html(SPECIMEN_DIR . '/lostpass_email.html');
      
      # user login, apparently!
      } else {
        output_specimen_headers(SPECIMEN_DIR . '/lostpass_user.headers');
        output_specimen_html(SPECIMEN_DIR . '/lostpass_user.html');
      }
    
    # nah, just a clean page pls
    } else {
      output_specimen_headers(SPECIMEN_DIR . '/lostpass.headers');
      output_specimen_html(SPECIMEN_DIR . '/lostpass.html');
    }
  
  # it's the login page, then,
  } else {
  
    # do we have the user?
    if (isset($_POST['log'])) {
      # do we also have the password?
      if (isset($_POST['pwd'])) {
        # both!
        output_specimen_headers(SPECIMEN_DIR . '/userpass.headers');
        output_specimen_html(SPECIMEN_DIR . '/userpass.html');
      } else {
        # just the user
        output_specimen_headers(SPECIMEN_DIR . '/user.headers');
        output_specimen_html(SPECIMEN_DIR . '/user.html');
      }
      
    } elseif (isset($_POST['pwd'])) {
      # just the pasword
      output_specimen_headers(SPECIMEN_DIR . '/password.headers');
      output_specimen_html(SPECIMEN_DIR . '/password.html');
      
    # nothing is supplied, just display a clean login page
    } else {
      output_specimen_headers(SPECIMEN_DIR . '/clean.headers');
      output_specimen_html(SPECIMEN_DIR . '/clean.html');
    }
    
  }
  
}

?>