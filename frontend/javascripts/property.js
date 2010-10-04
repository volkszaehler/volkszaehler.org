/**
 * Property handling & validation
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
 * volkzaehler.org is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or any later version.
 * 
 * volkzaehler.org is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Property constructor
 */
var Property = function(key, value) {
	
};

/**
 * Validate value
 * @param value
 * @return boolean
 * @todo implement/test
 */
Property.prototype.validate = function(value) {
	switch (property.type) {
		case 'string':
		case 'text':
			// TODO check pattern
			// TODO check string length
			return true;
			
		case 'float':
			// TODO check format
			// TODO check min/max
			return true;
			
		case 'integer':
			// TODO check format
			// TODO check min/max
			return true;
			
		case 'boolean':
			return value == '1' || value == '';
			
		case 'multiple':
			return $.inArray(value, property.options);
			
		default:
			alert('Error: unknown property!');
	}
};

/**
 * 
 * @todo implement/test
 */
Property.prototype.getDOM = function() {
	switch (property.type) {
		case 'string':
		case 'float':
		case 'integer':
			return $('<input>')
				.attr('type', 'text')
				.attr('name=', property.name)
				.attr('maxlength', (property.type == 'string') ? property.max : 0);
			
		case 'text':
			return $('<textarea>')
				.attr('name', property.name);
			
		case 'boolean':
			return $('<input>')
				.attr('type', 'checkbox')
				.attr('name', property.name)
				.value(1);
			
		case 'multiple':
			var dom = $('<select>').attr('name', property.name)
			property.options.each(function(index, option) {
				dom.append($('<option>')
					.value(option)
					.text(option)
				);
			});
			return dom;
	
		default:
			throw 'Unknown property type';
	}
};