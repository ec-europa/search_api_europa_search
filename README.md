# Search API Europa Search

[![Build Status](https://travis-ci.org/ec-europa/search_api_europa_search.svg?branch=7.x-1.x)](https://travis-ci.org/ec-europa/search_api_europa_search)

Search API Europa Search module provides a backend for the Search API which uses the "Europa Search" search engine for storing and searching data.

## Introduction

Europa Search is the corporate search engine for the European Commission. 

REST API services are provided in order to integrate any 3rd party application like Drupal, with the engine as far as this one is hosted in European Commission infrastructure.

The module interacts with these services in order to exchange data. To do so, it will rely on the Europa Search Client Library](https://github.com/ec-europa/oe-europa-search-client).

## Requirements

* Drupal 7;
* Search API module;
* [Europa Search Client Library](https://github.com/ec-europa/oe-europa-search-client);

## Limitation

The module like the client library is compatible with the **version 2 and upper** of the Europa Search REST API only.


## Installation

### For module maintainer

The project proposes a script that installs automatically the development environment with an up and running Drupal instance.
This instance allows testing the module's code.

In order to use this script, please read the next sub-sections.

#### Prerequisites
* OS: Unix/Linux, OS X.
* Composer
* Web server with:
  * PHP support; the project supports currently PHP 5.4+;
  * SQLite to take advantage of the installation script (see next sub-section).

#### Script execution

1. Clone the "[github](https://github.com/ec-europa/search_api_europa_search)" repository in the "DocumentRoot" repository of your server (www or htdocs);
2. Go in the cloned repository;
3. Create a "scripts/build.properties.local" file where you set parameters specific to your environment.<br />
The available parameters are listed in the "scripts/build.properties.dist" file. You have just to set those that are specific to environment.<br />
Below, the list of available parameters with their use:
   * **USER_MAIL**: The e-mail of the admin user to define in the Drupal instance of your environment;
   * **USER_NAME**: The user name of the admin user to define in the Drupal instance of your environment;
   * **USER_PASSWORD**: The user password of the admin user to define in the Drupal instance of your environment;
   * **DB_TYPE**: The database type used in your environment and will be used in the DB url definition (I.E. mysql);
   * **DB_URL**: The database URL used in your environment and will be used in the DB url definition;
   * **DB_PORT**: (Optional) The database URL used in your environment and will be used in the DB url definition;
   * **DB_USER**: The database user name used in your environment and will be used in the DB url definition;
   * **DB_PASS**: (Optional) The database user password used in your environment and will be used in the DB url definition;
   * **DB_INSTANCE*: The database name used in your environment and will be used in the DB url definition;
4. Execute this command: `scripts/setup-dev-env.sh`;
5. The environment is up and running with a fresh Drupal instance where the module is enabled.


### For site builder

```
TODO (NEPT-935)
```

## Configuration

```
TODO (NEPT-935)
```




