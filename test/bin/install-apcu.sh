#!/bin/bash

if [ "$TRAVIS_PHP_VERSION" == "5.3" ] || [ "$TRAVIS_PHP_VERSION" == "5.4" ]
then
    exit 0
fi

echo "no" | pecl install apcu-beta