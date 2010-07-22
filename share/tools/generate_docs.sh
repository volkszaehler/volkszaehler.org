#!/bin/bash
#
# Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
#
# This program is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License (either version 2 or
# version 3) as published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#
# For more information on the GPL, please go to:
# http://www.gnu.org/copyleft/gpl.html
#
# This is simple bash script to update the project documentation
# based on PHPDocumentor. It's used to be invoked by post-commit hooks
# of GitHub or the release script.
#

# change directory
cd /var/www/vz/github/

# update git 
git pull

cd /var/www/vz/

# update dokumentation
phpdoc/phpdoc --config /var/www/vz/github/share/tools/phpdoc.ini
