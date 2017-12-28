/**
 * Middleware handling
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
 * Middleware constructor
 * @var middleware definition
 */
var Middleware = function(definition) {
	this.title = definition.title;
	this.url = definition.url;
	this.live = definition.live || null;

	this.public = [];
	this.session = null;
};

/**
 * Load public entities
 */
Middleware.prototype.loadEntities = function() {
	return vz.load({
		controller: 'entity',
		url: this.url,
		context: this
	}).then(function(json) {
		this.public = [];

		json.entities.forEach(function(json) {
			// fix https://github.com/volkszaehler/volkszaehler.org/pull/560
			json.active = true;
			var entity = new Entity(json, this.url);
			entity.eachChild(function(child) {
				child.active = true;
			}, true); // recursive
			this.public.push(entity);
		}, this);
		this.public.sort(Entity.compare);

		// chainable
		return this;
	});
};

/**
 * Find middleware by url
 */
vz.middleware.find = function(url) {
	var mw = $.grep(vz.middleware, function(middleware) {
		if (url == middleware.url) {
			return true;
		}
	});

	return mw.length ? mw[0] : null;
};
