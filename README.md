# volkszaehler.org

[![Join the chat at https://gitter.im/volkszaehler/volkszaehler.org](https://badges.gitter.im/volkszaehler/volkszaehler.org.svg)](https://gitter.im/volkszaehler/volkszaehler.org?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

volkszaehler.org is a free smart meter implementation with focus on data privacy.

[![Build Status](https://travis-ci.org/volkszaehler/volkszaehler.org.png?branch=master)](https://travis-ci.org/volkszaehler/volkszaehler.org)


## Demo

http://demo.volkszaehler.org

![Screenshot](misc/docs/screenshot.png?raw=true)


## Quickstart

From the shell:

    wget --no-check-certificate https://raw.github.com/volkszaehler/volkszaehler.org/master/misc/tools/install.sh
    sudo bash install.sh

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
     |_ htdocs/                 public web files
     |   |_ middleware.php      middleware
     |   \_ frontend            web ui
     |
     |_ lib/                    middleware libraries
     \_ misc/
         |_ controller/
         |   |_ vzlogger/       command line tool to log meters/sensors
         |   \_ mbus/           a controller for mbus/messbus
         |
         |_ docs/               documentation
         |_ frontend/           alternative frontends
         |_ graphics/           several graphics for docs, etc.
         |_ sql/                database schema dumps
         |   \_ demo/           demo data
         |
         |_ tools/              scripts for imports, installation etc.
         \_ tests/              simple tests for middleware classes


## Copyright

Copyright © 2015 volkszaehler.org  
Licensed under the GNU Public License (http://opensource.org/licenses/gpl-license.php).
