#!/bin/bash
#
# getting specimens of Wordpress login sites to make honeypots of
#

# no, really
if [ -z ${1+x} ] || [[ "$1" != 'http://'* && "$1" != 'https://'* ]]; then
  echo "provide an URL (with schema,) plzkthxbai"
  exit 1
fi

if [[ "${1: -1}" != "/" ]]; then
  WP_URL="$1/"
else
  WP_URL="$1"
fi

# the specimen dir
SPECIMEN_DIR="${WP_URL//http:/}"
SPECIMEN_DIR="${SPECIMEN_DIR//https:/}"
SPECIMEN_DIR="$( echo "${SPECIMEN_DIR//https:/}" | tr -d '/:' )"

echo "working with: $WP_URL"
echo "directory is: $SPECIMEN_DIR"

mkdir -p "$SPECIMEN_DIR"

curl -D "$SPECIMEN_DIR/clean.headers" -b "wordpress_test_cookie=WP+Cookie+check" "$WP_URL/wp-login.php" > "$SPECIMEN_DIR/clean.html"
curl -D "$SPECIMEN_DIR/user.headers"  -b "wordpress_test_cookie=WP+Cookie+check" -d "log=HONEYUSER" -d "pwd=" -d "wp-submit=Log+In" -d "redirect_to=%2Fwp-admin%2F" -d "testcookie=1" "$WP_URL/wp-login.php" > "$SPECIMEN_DIR/user.html"
curl -D "$SPECIMEN_DIR/password.headers" -b "wordpress_test_cookie=WP+Cookie+check" -d "log=" -d "pwd=HONEYPASS" -d "wp-submit=Log+In" -d "redirect_to=%2Fwp-admin%2F" -d "testcookie=1" "$WP_URL/wp-login.php" > "$SPECIMEN_DIR/password.html"
curl -D "$SPECIMEN_DIR/userpass.headers" -b "wordpress_test_cookie=WP+Cookie+check" -d "log=HONEYUSER" -d "pwd=HONEYPASS" -d "wp-submit=Log+In" -d "redirect_to=%2Fwp-admin%2F" -d "testcookie=1" "$WP_URL/wp-login.php" > "$SPECIMEN_DIR/userpass.html"

curl -D "$SPECIMEN_DIR/lostpass.headers" -b "wordpress_test_cookie=WP+Cookie+check" "$WP_URL/wp-login.php?action=lostpassword" > "$SPECIMEN_DIR/lostpass.html"
curl -D "$SPECIMEN_DIR/lostpass_user.headers" -b "wordpress_test_cookie=WP+Cookie+check" -d "user_login=HONEYUSER" -d "redirect_to=" -d "wp-submit:Get+New+Password" "$WP_URL/wp-login.php?action=lostpassword" > "$SPECIMEN_DIR/lostpass_user.html"
curl -D "$SPECIMEN_DIR/lostpass_email.headers" -b "wordpress_test_cookie=WP+Cookie+check" -d "user_login=HONEYMAIL@EXAMPLE.COM" -d "redirect_to=" -d "wp-submit:Get+New+Password" "$WP_URL/wp-login.php?action=lostpassword" > "$SPECIMEN_DIR/lostpass_email.html"


