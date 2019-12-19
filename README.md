# volkszaehler.org

[![Build Status](https://travis-ci.org/volkszaehler/volkszaehler.org.svg?branch=master)](https://travis-ci.org/volkszaehler/volkszaehler.org)

volkszaehler.org is a free smart meter implementation with focus on data privacy.


## Demo

[demo.volkszaehler.org](https://demo.volkszaehler.org)

![Screenshot](misc/docs/screenshot.png?raw=true)


## Quickstart

The easiest way to try out volkszaehler is using Docker:

    docker-compose up -d

which will create a database, initialize it and start volkszaehler at port 8080.

## Installation

For local installation, run the install script from the shell:

    wget https://raw.github.com/volkszaehler/volkszaehler.org/master/bin/install.sh
    bash install.sh

Or follow the detailed installation instructions at http://wiki.volkszaehler.org/software/middleware/installation


## Documentation

* Website: [volkszaehler.org](http://volkszaehler.org)
* Wiki: [wiki.volkszaehler.org](http://wiki.volkszaehler.org)


## Support

* Users mailing list: https://demo.volkszaehler.org/mailman/listinfo/volkszaehler-users
* Developers mailing list: https://demo.volkszaehler.org/mailman/listinfo/volkszaehler-dev



## Repository structure

    volkszaehler.org/
     |_ etc/                    configuration files
     |_ bin/                    scripts for imports, installation etc.
     |_ htdocs/                 web UI
     |   \_ middleware.php      middleware
     |
     |_ lib/                    middleware libraries
     |_ test/                   unit tests
     \_ misc/
         |_ docs/               documentation
         |_ graphics/           graphics for docs, etc.
         \_ sql/                database schema dumps
             \_ demo/           demo data


## Copyright

Copyright © 2011-2018 volkszaehler.org
Licensed under the GNU General Public License Version 3 (https://opensource.org/licenses/GPL-3.0).
