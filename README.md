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
3. Execute this command: `scripts/setup-dev-env.sh`;
4. The environment is up and running with a fresh Drupal instance where the module is enabled.

The admin user set in the Drupal instance is:
* User name: _admin_
* User password: _admin_

### For site builder

```
TODO (NEPT-935)
```

## Configuration

In order to use Europa Search service through Search API, you must define a server and at least an index via 
the Search API administration interface.

This README will only focus on Europa Search specificity. For general explanation about the Search API, 
please consult the [Search API documentation](https://www.drupal.org/docs/7/modules/search-api)

### Search API Server

Configuring a server pointing the Europa Search services, implies the following configuration in the 
"Add server" form of the Search API admin interface:
 
 1. **Service class**: Select the "_Europa Search Service_";
 2. **Europa Search Service domain name**: Type the URL root (domain name) where the ES services are hosted; I.E. 
 the URL part common to all Europa search services;
 3. **Europa Search Service url port** (optional): the port number define to access the Europa search services;
 4. **Ingestion services settings (Indexing requests) > Registered API key**: The API key to use with the indexing requests.<br /> 
    It is communicated by the Europa Search team;
 5. **Ingestion services settings (Indexing requests) > Registered database**: The database id to use with the indexing requests.<br /> 
    It is communicated by the Europa Search team;
 6. **Search API services settings (Search requests) > Registered API key**: The API key to use with the search requests.<br /> 
    It is communicated by the Europa Search team.<br />
    Note that the value can be the same as the indexing one.
 7. **Search API services settings (Search requests) > Include the database value in search queries**: Indicates if the database id 
 set previously must be used in the search queries sent to the services.

 ### Search API index
 
 The definition of the Search API index is detailed in the [Search API official documentation](https://www.drupal.org/docs/7/modules/search-api/getting-started/howto-add-an-index).
 
 #### Mandatory configurations:
 
 In order that the Search API interacts correctly with the Europa Search services, some data alterations **must** be add to the Search API index,
 see filters tab of the index definition interface:
 * URL field;
 * Complete entity view; 
 * Europa Search reference.
 
 It adds data required by the Europa Search services in order to index correctly entities.


