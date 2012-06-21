#!/bin/bash
#
# Create new release tarball and packages for further distriubution
#
# @copyright Copyright (c) 2011, The volkszaehler.org project
# @package tools
# @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
# @author Steffen Vogel <info@steffenvogel.de>
#
##
# This file is part of volkzaehler.org
#
# volkzaehler.org is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# any later version.
#
# volkzaehler.org is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
##

VZ_ROOT=../..

# create temporory directory
# TODO use random path
VZ_TMP=/tmp/vz-release

mkdir ${VZ_TMP}

# copy files
cp -r ${VZ_ROOT} ${VZ_TMP}

# remove overhead
rm -r ${VZ_TMP}/misc

# minify

# get version number
VZ_VERSION=$(cat $(VZ_ROOT)/htdocs/middleware.php | sed /define\('VZ_VERSION',\ '([0-9.-]*)'\)\;/)

# get last release
# TODO get hash from last tagged release

# edit changelog
# TODO use <since>.. last release tag
git log --oneline >> ${VZ_ROOT}/CHANGELOG
pico ${VZ_ROOT}/CHANGELOG

# make tarball
tar -cz

# cleanup
rm -r ${VZ_TMP}


