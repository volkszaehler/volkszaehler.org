# volkszaehler.org

[![Build Status](https://travis-ci.org/volkszaehler/volkszaehler.org.svg?branch=master)](https://travis-ci.org/volkszaehler/volkszaehler.org)
[![Join the chat at https://gitter.im/volkszaehler/volkszaehler.org](https://badges.gitter.im/volkszaehler/volkszaehler.org.svg)](https://gitter.im/volkszaehler/volkszaehler.org?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

volkszaehler.org is a free smart meter implementation with focus on data privacy.


## Demo

http://demo.volkszaehler.org

![Screenshot](misc/docs/screenshot.png?raw=true)


## Quickstart

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
         |_ controller/         various logging tools, e.g. for mbus/messbus
         |_ docs/               documentation
         |_ graphics/           graphics for docs, etc.
         \_ sql/                database schema dumps
             \_ demo/           demo data


## Copyright

Copyright © 2011-2018 volkszaehler.org
Licensed under the GNU General Public License Version 3 (https://opensource.org/licenses/GPL-3.0).
