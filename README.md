# Search API Europa Search

[![Build Status](https://travis-ci.org/ec-europa/search_api_europa_search.svg?branch=7.x-1.x)](https://travis-ci.org/ec-europa/search_api_europa_search)

Search API Europa Search module provides a backend for the Search API which uses the "Europa Search" search engine for storing and searching data.

## Introduction

Europa Search is the corporate search engine for the European Commission. 

REST API services are provided in order to integrate any 3rd party application like Drupal, with the engine as far as 
this one is hosted in European Commission infrastructure.

The module interacts with these services in order to exchange data.<br />
To do so, it will rely on the [Europa Search Client Library](https://github.com/ec-europa/oe-europa-search-client).

## Requirements

* Drupal 7;
* Search API module;
* [Europa Search Client Library](https://github.com/ec-europa/oe-europa-search-client);
* An "class autoload" mechanism allowing Drupal to load classes coming from the composer _"vendor"_ repository.<br />
  The test environment used in this project uses the contrib module "[Composer Autoload](https://www.drupal.org/project/composer_autoload)"
  as mechanism.

## Limitation

Like the client library, The module is compatible with the **versions 2 and upper** of the Europa Search REST API only.


## Installation

### For module maintainers

The project proposes a script that installs automatically the development/test environment with an up and running Drupal instance.
This instance allows testing the module's code in the same way that Travis will do.

In order to use this script, please read the next sub-sections.

#### Prerequisites
* OS: Unix/Linux, OS X.<br /><br />
  For Windows, the tasks listed in the 4th point of the ["Script execution"](#script-execution) sub-section, must
  be executed manually.<br /><br />
* Composer.
* Web server with:
  * PHP support; the project supports currently PHP 5.6+;
  * An access to a database.

#### Script execution

1. Clone the "[github](https://github.com/ec-europa/search_api_europa_search)" repository in the "DocumentRoot" repository of 
   your server (www or htdocs);
2. Go in the cloned repository;
3. Create a _"scripts/build.properties.local"_ file where you set parameters specific to your environment.<br />
   The available parameters are listed in the _"scripts/build.properties.dist"_ file. You have just to define those that are 
   specific to your environment.<br />
   Below, the list of available parameters with their use:
   * **USER_MAIL**: The e-mail of the admin user to define in the Drupal instance of your environment;
   * **USER_NAME**: The user name of the admin user to define in the Drupal instance of your environment;
   * **USER_PASSWORD**: The user password of the admin user to define in the Drupal instance of your environment;
   * **DB_TYPE**: The database type used in your environment and will be used in the DB url definition (I.E. mysql);
   * **DB_URL**: The database URL used in your environment and will be used in the DB url definition;
   * **DB_PORT**: (Optional) The database URL used in your environment and will be used in the DB url definition;
   * **DB_USER**: The database user name used in your environment and will be used in the DB url definition;
   * **DB_PASS**: (Optional) The database user password used in your environment and will be used in the DB url definition;
   * **DB_NAME**: The database name used in your environment and will be used in the DB url definition;
4. Execute this command: `scripts/setup-dev-env.sh`.<br /><br />
   This command will execute **automatically** the following tasks:
   * Executing the `composer install` command based on the project's _"composer.json"_ file in order to:
     - Installing the different libraries the project depends on;
     - Enabling GrumPHP quality control on the Git commits (see the ["Quality control" section](#quality-control));
     - Creating the _"web"_ sub-folder with the code of a Drupal 7 site and the "contrib" modules required by Search API 
       Europa Search;   
   * Creating symlinks for the module files and the sub-folders in the _"web/sites/all/modules"_ repository;
   * Generating the Drupal 7 site based on the parameters set in the _"scripts/build.properties.local"_ file.<br /><br />
5. The environment is up and running with a fresh Drupal instance where the module is enabled for the tests (see the ["Tests" section](#tests))

#### Quality control

The automatic quality control is managed by the ["OpenEuropa code review"](https://github.com/ec-europa/oe-code-review) component.
 
The component depends on [GrumPHP](https://github.com/phpro/grumphp) and based its controls on the Drupal coding convention.

Check the ["OpenEuropa code review" documentation](https://github.com/ec-europa/oe-code-review/blob/master/README.md) for more.

##### Component's Usage

GrumPHP tasks will be ran at every commit, if you want to run them without performing a commit use the following command:

```
$ ./vendor/bin/grumphp run
```

If you want to simulate a commit message use:

```
$ ./vendor/bin/grumphp git:pre-commit
```

Check [GrumPHP documentation](https://github.com/phpro/grumphp/tree/master/doc) for more.

### Tests

```
TODO (NEPT-934)
```

### For site builders

```
TODO (NEPT-935)
```

## Configuration

```
TODO (NEPT-935)
```




