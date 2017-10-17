#!/bin/sh

ROOTDIR=`pwd`
MODULESDIR="$ROOTDIR/web/sites/all/modules"

if [ -d "$ROOTDIR/web" ]; then
  chmod -R 755 $ROOTDIR/web
  rm -rf $ROOTDIR/web
fi

USERMAIL="admin@test.com"
USERNAME="admin"
USERPASSWORD="admin"

/usr/bin/env PHP_OPTIONS="-d sendmail_path=`which true`" vendor/bin/drush qd -v -y --core=drupal-7 --root=$ROOTDIR/web --account-mail=$USERMAIL --account-name=$USERNAME --account-pass=$USERPASSWORD --no-server
mkdir -p $ROOTDIR/web/sites/all/modules/search_api_europa_search
ln -s $ROOTDIR/search_api_europa_search.info $MODULESDIR/search_api_europa_search/search_api_europa_search.info
ln -s $ROOTDIR/search_api_europa_search.module $MODULESDIR/search_api_europa_search/search_api_europa_search.module
ln -s $ROOTDIR/phpunit.xml.dist $MODULESDIR/search_api_europa_search/phpunit.xml.dist
# ln -s src $MODULESDIR/search_api_europa_search/src
# ln -s tests $MODULESDIR/search_api_europa_search/tests
ln -s vendor $MODULESDIR/search_api_europa_search/vendor
cd web
../vendor/bin/drush en -y search_api_europa_search