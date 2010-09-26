/**
 * Main javascript file
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

var myUUID = '';
if($.getUrlVar('uuid'))
	myUUID = $.getUrlVar('uuid');

// storing json data
var data;

// windowStart parameter for json server
var myWindowStart = 0;

// windowEnd parameter for json server
var myWindowEnd = getGroupedTimestamp((new Date()).getTime());

// windowGrouping for json server
var windowGrouping = 0;

// mouse position on mousedown (x-axis)
var moveXstart = 0;

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	// resize chart area for low resolution displays
	// works fine with HTC hero
	// perhaps you have to reload after display rotation
	if($(window).width() < 800) {
		$("#chart").animate({
			width:$(window).width() - 40,
			height:$(window).height() - 3,
		},0);
		$("#options").animate({
			height:$(window).height() - 3,
		},0);
	}
	
	// load channel list
	// loadChannelList();
	
	// start autoReload timer
	window.setInterval("autoReload()",5000);
	
	getData();
});
