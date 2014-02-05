[![Build Status](https://travis-ci.org/andig/volkszaehler.org.png?branch=master-travis)](https://travis-ci.org/andig/volkszaehler.org)

# Unit tests

Unit testing checks middleware functionality by executing HTTP JSON requests and validating the response.

Unit tests require installation of [PHPUnit](http://phpunit.de/manual/current/en/installation.html), the de facto standard for unit testing under PHP.
One way to install PHPUnit that will make it available for use with Volkszaehler is installation via PEAR:

    pear config-set auto_discover 1 
    pear install pear.phpunit.de/PHPUnit

## Using PHPUnit

Run PHPUnit from the test folder using a shell:

    phpunit --configuration phpunit.xml
