# volkszaehler.org

[![Build Status](https://travis-ci.org/volkszaehler/volkszaehler.org.svg?branch=master)](https://travis-ci.org/volkszaehler/volkszaehler.org)
[![Join the chat at https://gitter.im/volkszaehler/volkszaehler.org](https://badges.gitter.im/volkszaehler/volkszaehler.org.svg)](https://gitter.im/volkszaehler/volkszaehler.org?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

volkszaehler.org is a free smart meter implementation with focus on data privacy.


## Demo

http://demo.volkszaehler.org

![Screenshot](misc/docs/screenshot.png?raw=true)


## Quickstart

The easiest way to run volkszaehler is using Docker.

### Preparation

Start database server and create the database:

    docker run --name db -e MYSQL_ROOT_PASSWORD=R00t+ mysql
    docker run --link db mysql -u root -pR00t+ -h db -e "CREATE DATABASE volkszaehler;"

Create `docker.conf.php`, mount into the volkszaehler container and initialize the database schema:

    docker run --link db -v $(pwd)/etc/docker.conf.php:/vz/etc/volkszaehler.conf.php volkszaehler/volkszaehler /vz/bin/doctrine orm:schema-tool:create

### Running

Start the application:

    docker run -p 8080:8080 --link db -v $(pwd)/etc/docker.conf.php:/vz/etc/volkszaehler.conf.php volkszaehler/volkszaehler

Run data aggregation:

    docker run --link db -v $(pwd)/etc/docker.conf.php:/vz/etc/volkszaehler.conf.php volkszaehler/volkszaehler /vz/bin/aggregate run -l day -l hour

## Local Installation

From the shell:

    wget https://raw.github.com/volkszaehler/volkszaehler.org/master/bin/install.sh
    bash install.sh

Or follow the detailed installation instructions at http://wiki.volkszaehler.org/software/middleware/installation


## Documentation

* Website: http://volkszaehler.org
* Wiki: http://wiki.volkszaehler.org


## Support

* Users mailing list: volkszaehler@lists.volkszaehler.org
* Developers mailing list: volkszaehler-dev@lists.volkszaehler.org


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
