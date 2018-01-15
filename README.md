# Search API Europa Search

[![Build Status](https://travis-ci.org/ec-europa/search_api_europa_search.svg?branch=7.x-1.x)](https://travis-ci.org/ec-europa/search_api_europa_search)

Search API Europa Search module provides a backend for the Search API which uses the "Europa Search" search engine for storing and searching data.

Table of content:
=================
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Limitations](#limitations)
- [Installation](#installation)
  - [For module maintainers](#for-module-maintainers)
  - [For site builders](#for-site-builders)

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
* The test environment for this project uses the contrib module "[Composer Autoload](https://www.drupal.org/project/composer_autoload)"
  as mechanism.

## Limitation

Like the client library, The module is compatible with the **versions 2 and upper** of the Europa Search REST API only.


## Installation

### For module maintainers

Run:

```
$ composer install
```

This will download all development dependencies and build a Drupal 7 target site under `./build` and run
`./vendor/bin/run drupal:site-setup` to setup proper symlink and produce necessary scaffolding files.

After that:

1. Copy `runner.yml.dist` into `runner.yml` and customise relevant parameters.
2. Run `./vendor/bin/run drupal:site-install` to install the project having the Search API Europa Search module enabled.

The project uses the target site for tests, see the ["Tests" section](#tests) for more information.

To have a complete list of building options run:

```
$ ./vendor/bin/run
```

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

#### Requirements

 * Drupal 7.x
 * Search API 1.x
 * Any solution allowing calling the composer autoload class like [Composer autoloader](https://www.drupal.org/project/composer_autoloader).
 * Configuration parameters supplied by the Europa Search team.
 
#### Installation
 
Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-7 for further
information.

Navigate to administer >> modules. Enable "Search API Europa Search".


## Configuration

In order to use Europa Search service through Search API, you must define a server and at least an index via 
the Search API administration interface.

This README will only focus on Europa Search specificity. For general explanation about the Search API, 
please consult the [Search API documentation](https://www.drupal.org/docs/7/modules/search-api)

### Search API Server

Configuring a server pointing the Europa Search services, implies the following configuration in the 
"Add server" form of the Search API admin interface:
 
 1. **Service class**: Select the "_Europa Search Service_";
 2. **Ingestion services settings (Indexing requests) > Europa Search Service URL**: Type the URL where the ES Indexing services are hosted;
 3. **Ingestion services settings (Indexing requests) > Proxy settings for accessing the services**: Fields for configuring the proxy to use to connect 
    the services<br />.
    If the host system settings are enough, leave the child fields untouched; I.E.:
    * _Configuration type_ : The type of configuration to use:
      * "Host system's settings": The module will use the same proxy as the host system uses;
      * "Specific proxy's settings": The module will use the proxy different than the host system uses;<br />
        Then, the "Proxy URL" field must be filled.
      * "Bypass proxy": The module will send requests without passing to any proxy;
    * _Proxy URL_ : The URL of the specific proxy to use (mandatory if the "Configuration type" value is "Host system's settings");
    * _Proxy user name_ : The user name to use for the Proxy credentials;
    * _Proxy password_ : The password to use for the Proxy credentials;
 4. **Ingestion services settings (Indexing requests) > Registered API key**: The API key to use with the indexing requests.<br /> 
    It is communicated by the Europa Search team;
 5. **Ingestion services settings (Indexing requests) > Fallback language in case of Neutral language content**: The code of the language 
    to use instead of the 'und' (LANGUAGE_NONE) because ES services do not support this value;
 6. **Ingestion services settings (Indexing requests) > Registered database**: The database id to use with the indexing requests.<br /> 
    It is communicated by the Europa Search team;
    **Search API services settings (Search requests) > Europa Search Service URL**: Type the URL where the ES Search services are hosted;
 7. **Search API services settings (Search requests) > Proxy settings for accessing the services**: Fields for configuring the proxy to use to connect 
    the services<br />.
    If the host system settings are enough, leave the child fields untouched; I.E.:
    * _Configuration type_ : The type of configuration to use:
      * "Host system's settings": The module will use the same proxy as the host system uses;
      * "Specific proxy's settings": The module will use the proxy different than the host system uses;<br />
        Then, the "Proxy URL" field must be filled.
      * "Bypass proxy": The module will send requests without passing to any proxy;
    * _Proxy URL_ : The URL of the specific proxy to use (mandatory if the "Configuration type" value is "Host system's settings");
    * _Proxy user name_ : The user name to use for the Proxy credentials;
    * _Proxy password_ : The password to use for the Proxy credentials;
 8. **Search API services settings (Search requests) > Registered API key**: The API key to use with the search requests.<br /> 
    It is communicated by the Europa Search team.<br />
    Note that the value can be the same as the indexing one.
 9. **Search API services settings (Search requests) > Include the database value in search queries**: Indicates if the database id 
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
 
 ### Recommended configurations:
 
 It is recommended to enable "**Europa Search results processing**" processor in the index filters. This Search API processor allows:
 - Setting the highlighting parameters to use with each search request:
   - Which HTML tags to use in the text highlighting mechanism ("**Highlighting prefix**" & "**Highlighting suffix**" processor parameters);
   - The maximum number of characters that must be highlighted in a result text ("**Highlight limit**" processor parameter).
 - Setting which text format to apply on "text" fields (string, fulltext search, uri...) ("**Text format on result text fields**" processor parameter).
 
 Without this processor, the Europa Search service will use their default parameters for highlighting texts, and the "check plain" format will be apply
 on all text fields.
 
 ### Multilingualism
 
 The module does not manage the multilingualism itself because Search API do it natively for the "content translation" (i18n) mechanism.
 
 For entities for which the "Entity translation" (ET) mechanism manages translations, the use of the **version 2.x of the "[Search API Entity Translation]**(https://www.drupal.org/project/search_api_et)" 
 module is required.<br />
 
 #### Entity translation support: Configuration
 
 In order to support multilingual searches based on Entity translation, you need to:
 * Enable Search API Entity Translation (see above);
 * Create an Search API index through the Search API index creation form (path: admin/config/search/search_api/add_index) as usual but
   by selecting one of these Item types to index:
   - Multilingual Comment (indexing "ET" comments);
   - Multilingual Node (indexing "ET" contents);
   - Multilingual File (indexing "ET" files)
   - Multilingual Taxonomy term (indexing "ET" Taxonomy terms);
   - Multilingual Taxonomy vocabulary (indexing "ET" Taxonomy vocabularies);
 * Continue the configuration with the specific configurations detailed the 2 previous sub-sections.
 
In the current version, the module does not support "multiple" Multilingual types; I.E. an index covering several entity types.


