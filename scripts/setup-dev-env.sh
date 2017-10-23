#!/bin/sh

echo "###################################"
echo "# START ENVIRONMENT INSTALLATION. #"
echo "###################################"
echo
ROOTDIR=`pwd`
MODULESDIR="$ROOTDIR/web/sites/all/modules"

echo "Delete 'web' folder"
echo "==================="
if [ -d "$ROOTDIR/web" ]; then
  chmod -R 755 $ROOTDIR/web
  rm -rf $ROOTDIR/web
fi
echo

echo "Run composer"
echo "============"
composer install
echo

echo "Create module related symlinks"
echo "=============================="
# Create symlinks into the web repository for module files.
mkdir -p $ROOTDIR/web/sites/all/modules/custom/search_api_europa_search
ln -s $ROOTDIR/search_api_europa_search.info $MODULESDIR/custom/search_api_europa_search/search_api_europa_search.info
ln -s $ROOTDIR/search_api_europa_search.module $MODULESDIR/custom/search_api_europa_search/search_api_europa_search.module
ln -s $ROOTDIR/phpunit.xml.dist $MODULESDIR/custom/search_api_europa_search/phpunit.xml.dist
ln -s $ROOTDIR/src $MODULESDIR/custom/search_api_europa_search/src
# ln -s tests $MODULESDIR/search_api_europa_search/tests
echo


echo "Load drupal instance parameters"
echo "==============================="
# Load DIST parameters.
FILE="$ROOTDIR/scripts/build.properties.dist"

if [ -f "$FILE" ]
then
  echo "$FILE found; loading parameters."

  while IFS='=' read -r key value
  do
    eval "${key}='${value}'"
  done < "$FILE"
else
  echo "$FILE not found."
fi

# Load DIST parameters.
FILE="$ROOTDIR/scripts/build.properties.local"

if [ -f "$FILE" ]
then
  echo "$FILE found; loading local parameters."

  while IFS='=' read -r key value
  do
    eval "${key}='${value}'"
  done < "$FILE"
else
  echo "$FILE not found, work with dist file only."
fi
echo

# BUILD Database parameter value.
DB_FULL_URL="${DB_TYPE}://${DB_USER}:${DB_PASS}@${DB_URL}/${DB_INSTANCE}"
if [ ! -z "$DB_PORT" ];
then
  DB_FULL_URL="${DB_TYPE}://${DB_USER}:${DB_PASS}@${DB_URL}:${DB_PORT}/${DB_INSTANCE}"
fi

echo "Parameters used for the environment installation:"
echo "================================================="
echo "DB_TYPE     = " $DB_TYPE
echo "DB_URL      = " $DB_URL
echo "DB_PORT     = " $DB_PORT
echo "DB_USER     = " $DB_USER
echo "DB_PASS     = " $DB_PASS
echo "DB_NAME     = " $DB_INSTANCE
echo "DB_FULL_URL = " $DB_FULL_URL
echo

echo "Install Drupal instance with needed modules:"
echo "============================================"
cd $ROOTDIR/web
../vendor/bin/drush -y site-install standard --db-url=${DB_FULL_URL} --account-name=${USER_NAME} --account-pass=${USER_PASSWORD} --account-mail=${USER_MAIL}
../vendor/bin/drush en -y composer_autoloader
../vendor/bin/drush en -y search_api_europa_search
../vendor/bin/drush dis -y overlay
echo
echo "#################################"
echo "# END ENVIRONMENT INSTALLATION. #"
echo "#################################"
