/**
 * Javascript chart functions
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
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
 * Update headline on zoom
 */
vz.wui.updateHeadline = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min,
			format = '%a %e. %b %Y',
			from = vz.options.plot.xaxis.min,
			to = vz.options.plot.xaxis.max;

	if (delta < 3*24*3600*1000) {
		format += ' %H:%M'; // under 3 days
		if (delta < 5*60*1000) format += ':%S'; // under 5 minutes
	}
	else {
		// only formatting days- remove 1ms to display previous day for consumption mode
		to--;
	}

	// timezone-aware dates if timezone-js is included
	from = $.plot.formatDate(
		$.plot.dateGenerator(from, vz.options.plot.xaxis),
		format, vz.options.monthNames, vz.options.dayNames, true
	);
	to = $.plot.formatDate(
		$.plot.dateGenerator(to, vz.options.plot.xaxis),
		format, vz.options.monthNames, vz.options.dayNames, true
	);

	$('#title').html(from + ' - ' + to);
};

/**
 * Update legend on move hover
 */
vz.wui.updateLegend = function(pos, item) {
	vz.wui.updateLegendTimeout = null;

	var axes = vz.plot.getAxes();
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
		pos.y < axes.yaxis.min || pos.y > axes.yaxis.max)
		return;

	var i, j, dataset = vz.plot.getData();
	for (i = 0; i < dataset.length; ++i) {
		var series = dataset[i];

		if (!series.data.length)
			continue;

		// find the nearest points, x-wise
		for (j = 0; j < series.data.length; ++j)
			// bar charts store adjusted timestamp in index 3
			if (series.data[j][series.bars.show && series.bars.barWidth ? 3 : 0] > pos.x)
				break;

		var y = null, p = series.data[j-1];
		if (series.bars.show && series.bars.barWidth) {
			// display = bars
			if (p && pos.x < p[3] + series.bars.barWidth)
				y = p[1];
		}
		// lines.steps includes states
		else if (series.lines.show && series.lines.steps) {
			// display = steps
			if (p)
				y = p[1];
		}
		else {
			// display = line -> interpolate
			var p2 = series.data[j];
			if (p && p2)
				y = p[1] + (p2[1] - p[1]) * (pos.x - p[0]) / (p2[0] - p[0]);
		}

		var legend = $('.legend .legendLabel');
		if (y === null) {
			legend.eq(i).text(series.title);
		}
		else {
			legend.eq(i).text(series.title + ": " + vz.wui.formatNumber(y, series.unit));
		}
	}

	// use plot wrapper instead of `new Date()` for timezone support
	var d = $.plot.dateGenerator(pos.x, vz.options.plot.xaxis);
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var format = (delta > 1*24*3600*1000) ? '%d.%m.%y %H:%M' : '%H:%M:%S';

	/*
	 * Important coordinate types
	 *
	 * pos.pageX: 										abs mouse position
	 * vz.plot.offset()								abs plot area position (in page)
	 *
	 * $('#flot').offset().left 			abs placeholder div position (in page)
	 * vz.plot.pointOffset(pos) 			rel point position inside placeholder div
	 *
	 * vz.plot.getPlotOffset()				rel grid position inside canvas/placeholder div
	 */

	// position the timestamp
	$('#time').text($.plot.formatDate(d, format));
	var offset = (pos.pageX + $('#time').outerWidth() > vz.plot.offset().left + vz.plot.width()) ? $('#time').outerWidth() : 0;

	$('#time').css({
		top: vz.plot.offset().top,
		left: pos.pageX - offset,
		display: 'inline-block'
	});

	// update opaque background sizing
	$('.legend > div').css({ width: $('.legend table').css('width') });

	if (item) {
		var x = item.datapoint[0].toFixed(2),
				y = item.datapoint[1].toFixed(2);

		$("#tooltip").html(
			vz.wui.formatNumber(parseFloat(y), item.series.unit)
				.replace(/ /g, '&nbsp;')
		)
			.css({top: item.pageY-20, left: item.pageX+10})
			.fadeIn(200);
	} else {
		$('#tooltip').fadeOut(200);
	}
};

/**
 * Callback when mouse leaves plot area
 */
vz.wui.plotLeave = function() {
	vz.wui.updateLegendTimeout = null;
	$('#time').css({ display: 'none' });

	vz.plot.getData().forEach(function(series, idx) {
		$('.legend .legendLabel').eq(idx).text(series.title);
	});
};

/**
 * Formatter-aware tickGenerator extension to limit tick generation
 * Source: jquery.flot.js
 */
vz.wui.tickGenerator = function (axis) {
	function floorInBase(n, base) {
		return base * Math.floor(n / base);
	}
	var ticks = [],
			start = floorInBase(axis.min, axis.tickSize),
			min = axis.options.minTick,
			max = axis.options.maxTick,
			i = 0,
			v = Number.NaN,
			prev;
	do {
		prev = v;
		v = start + i * axis.tickSize;
		if ((min === undefined || v >= min) && (max === undefined || v <= max))
			ticks.push(v);
		++i;
	} while (v < axis.max && v != prev);
	if (max !== undefined && v > max)
		ticks.push(v);
	return ticks;
};

/**
 * tickFormatter extension to apply axis labels to last tick
 * Source: jquery.flot.js
 */
vz.wui.tickFormatter = function (value, axis, tickIndex, ticks) {
	var si, last;

	// last tick: return label instead
	if (ticks && axis.options.axisLabel) {
		last = ticks.length-1;
		// With a fixed maximum the axis is not snapped to the last tick, which
		// can then lie outside the visible range: use the last visible one.
		while (axis.options.max != null && last > 0 && ticks[last] > axis.max) {
			last--;
		}
		if (tickIndex === last && last > 0) {
			si = axis.options.si || {};
			return '[' + si.prefix + si.unit + ']';
		}
	}

	// first tick: calculate label formatting
	if (ticks && tickIndex === 0 && ticks.length) {
		var maxValue = ticks[ticks.length-1];
		axis.options.si = vz.wui.scaleNumberAndUnit(maxValue, axis.options.axisLabel);

		// see vz.wui.formatNumber
		si = axis.options.si;
		var precision = (Math.abs(si.number) < vz.options.minNumber) ? 0 : Math.max(0, vz.options.precision - Math.max(-1, Math.floor(Math.log(Math.abs(si.number))/Math.LN10)));

		axis.tickDecimals = precision;
	}

	// scale value according to unit
	value *= axis.options.si.scaler;

	return value.toFixed(axis.tickDecimals);
};

/**
 * Draws plot to container
 *
 * The general flow of chart drawing looks like this:
 *   1. assign entities to axis (persisted)
 *   2. for each entity/series
 *      a. determine drawing style
 *      b. manipulate points matching style (steps, bars and consumption mode)
 *   3. call plot
 *      a. orderBars adjusts Xaxis min/max for consumption mode (orderBars core modification)
 *      b. tickFormatter takes care of y axis ticks (flot core modification)
 */
vz.wui.drawPlot = function () {
	vz.options.interval = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	vz.wui.updateHeadline();

	// assign entities to axes
	if (vz.options.plot.axesAssigned === false) {
		vz.entities.eachActiveChannel(function(entity) {
			entity.assignAxis();
		}, true);

		vz.options.plot.axesAssigned = true;
	}

	// consumption mode does some Xaxis manupulation- preserve original options
	var plotOptions = $.extend(true, {}, vz.options.plot);
	var series = [], index = 0;

	vz.entities.eachActiveChannel(function(entity) {
		// work on copy here to be able to redraw
		var i, tuples = entity.data.tuples.map(function(t) {
			return t.slice(0,2);
		});

		var style = vz.options.style || (entity.isConsumptionMode() ? 'bars' : entity.style);
		var linestyle = vz.options.linestyle || entity.linestyle;
		var fillstyle = parseFloat(vz.options.fillstyle || entity.fillstyle);
		var linewidth = parseFloat(vz.options.linewidth ||
			entity.selected ? vz.options.lineWidthSelected : entity.linewidth || vz.options.lineWidthDefault
		);
		var gap = vz.options.gap || entity.gap;

		// mangle data for "steps" curves by shifting one ts left ("step-before")
		if (style == 'steps') {
			tuples.unshift([entity.data.from, 1, 1]); // add new first ts
			for (i=0; i<tuples.length-1; i++) {
				tuples[i][1] = tuples[i+1][1];
			}
		}

		// round timestamps for consumption mode
		if (entity.isConsumptionMode()) {
			var modeIndex = ['hour', 'day', 'month', 'year'].indexOf(vz.options.mode);

			for (i=0; i<tuples.length; i++) {
				tuples[i][0] = vz.wui.adjustTimestamp(tuples[i][0], true);
			}
		}

		var serie = {
			data: tuples,
			color: entity.color,
			label: entity.title,
			title: entity.title,
			unit:  entity.getUnitForMode(),
			yaxis: entity.assignedYaxis
		};

		if (['lines', 'steps', 'states'].indexOf(style) >= 0) {
			$.extend(serie, {
				lines: {
					show:       true,
					steps:      style == 'steps' || style == 'states',
					fill:       fillstyle !== undefined ? fillstyle : false,
					lineWidth:  linewidth
				}
			});

			if (linestyle == 'dashed' || linestyle == 'dotted') {
				// dashes are an extension of lines
				$.extend(serie, {
					dashes: {
						show: true,
						dashLength: linestyle == 'dashed' ? 5 : [1, 2]
					}
				});
			}

			// disable interpolation when data has gaps
			if (gap) {
				var minGapWidth = (entity.data.to - entity.data.from) / tuples.length;
				serie.xGapThresh = Math.max(gap * 1000, 2 * minGapWidth);
				plotOptions.xaxis.insertGaps = true;
			}
		}
		else if (style == 'points') {
			$.extend(serie, {
				points: {
					show:       true,
					lineWidth:  linewidth
				}
			});
		}
		else if (style == 'bars') {
			$.extend(serie, {
				bars: {
					show:       true,
					lineWidth:  0,
					fill:       entity.selected ? 1.0 : 0.8,
					order:      index++ // only used for bars
				}
			});
		}

		series.push(serie);
	});

	// bar chart formatting
	if (vz.wui.isConsumptionMode() && series.length > 0) {
		var barTypes = {
			hour:  1,
			day:   24,
			week:  24 * 7,
			month: 24 * 30,
			year:  24 * 365
		};

		// apply spacing around bars
		var barWidth = barTypes[vz.options.mode] * 3.6e6 * (vz.options.plot.series.bars.usedSpace || 0.6) / index;

		$.extend(plotOptions, {
			bars: {
				barWidth: Math.max(barWidth, 1)
			}
		});

		// avoid confusing intermediate ticks in consumption mode
		plotOptions.xaxis.minTickSize = [1, vz.options.mode];
	}

	// remove right hand margin space if no right yaxis defined and used
	var yaxesAtRightSide = 0;
	plotOptions.yaxes.forEach(function(axis) {
		if (axis.position == 'right' && axis.axisLabel !== undefined) {
			yaxesAtRightSide++;
		}
	});
	if (plotOptions.xaxis.reserveSpace === undefined && yaxesAtRightSide === 0) {
		plotOptions.xaxis.reserveSpace = false;
	}

	if (series.length === 0) {
		$('#overlay').html('<img src="images/empty.png" alt="no data..." /><p>nothing to plot...</p>');
		series.push({}); // add empty dataset to show axes
	}
	else {
		$('#overlay').empty();
	}

	// manual y axis ranges override the automatic extrema
	vz.wui.applyAxisRanges(plotOptions);

	// call flot
	vz.plot = $.plot($('#flot'), series, plotOptions);

	// mark manually scaled axes, also after later redraws
	vz.plot.hooks.draw.push(vz.wui.updateAxisBadges);
	vz.wui.updateAxisBadges(vz.plot);

	// disable automatic refresh if we are in past
	if (vz.options.refresh) {
		if (vz.wui.tmaxnow) {
			vz.wui.setTimeout();
		} else {
			vz.wui.clearTimeout('(suspended)');
		}
	} else {
		vz.wui.clearTimeout();
	}
};

/**
 * Manual y axis ranges
 *
 * The mouse wheel over a y axis zooms that axis around the pointer, dragging
 * the axis shifts it. Double click on the axis or a click on its marker
 * returns to the automatic scale. Time zoom and the other axes are not
 * affected.
 *
 * The ranges live in vz.wui.axisRanges, keyed by axis number and unit, and are
 * applied on top of the automatic extrema in every drawPlot(). A range is
 * dropped as soon as its axis gets another unit.
 */
vz.wui.axisRanges = {};

vz.wui.axisKey = function(index, axis) {
	return index + '|' + (axis.axisLabel || '');
};

/**
 * Apply manual ranges to the (copied) yaxes options
 */
vz.wui.applyAxisRanges = function(plotOptions) {
	var keep = {};

	plotOptions.yaxes.forEach(function(axis, index) {
		var key = vz.wui.axisKey(index, axis),
			range = vz.wui.axisRanges[key];

		if (range) {
			axis.min = range.min;
			axis.max = range.max;
			keep[key] = range;
		}
	});

	vz.wui.axisRanges = keep;
};

/**
 * Y axis below the mouse pointer: {index, axis} or null
 */
vz.wui.axisAt = function(event) {
	if (!vz.plot) {
		return null;
	}

	var rect = $('#flot')[0].getBoundingClientRect(),
		x = event.clientX - rect.left,
		y = event.clientY - rect.top,
		axes = vz.plot.getYAxes();

	for (var i = 0; i < axes.length; i++) {
		var box = axes[i].box;
		if (axes[i].show && box && x >= box.left && x <= box.left + box.width &&
				y >= box.top && y <= box.top + box.height) {
			return {index: i, axis: axes[i], y: y};
		}
	}
	return null;
};

/**
 * Current range of an axis: manual range if set, else the drawn one
 */
vz.wui.axisRange = function(index, axis) {
	var range = vz.wui.axisRanges[vz.wui.axisKey(index, vz.options.plot.yaxes[index] || {})];
	return range ? {min: range.min, max: range.max} : {min: axis.min, max: axis.max};
};

/**
 * Set range of an axis and redraw (at most once per frame)
 */
vz.wui.setAxisRange = function(index, min, max) {
	if (!isFinite(min) || !isFinite(max) || max - min <= 1e-9 * Math.max(Math.abs(min), Math.abs(max), 1)) {
		return; // degenerate
	}

	vz.wui.axisRanges[vz.wui.axisKey(index, vz.options.plot.yaxes[index] || {})] = {min: min, max: max};
	vz.wui.redrawAxes();
};

vz.wui.resetAxis = function(index) {
	delete vz.wui.axisRanges[vz.wui.axisKey(index, vz.options.plot.yaxes[index] || {})];
	vz.wui.redrawAxes();
};

vz.wui.resetAllAxes = function() {
	vz.wui.axisRanges = {};
	vz.wui.redrawAxes();
};

vz.wui.redrawAxes = function() {
	if (!vz.wui.axisFrame) {
		vz.wui.axisFrame = window.requestAnimationFrame(function() {
			vz.wui.axisFrame = null;
			vz.wui.drawPlot();
		});
	}
};

/**
 * Marker on every manually scaled axis, click resets that axis, shift-click all axes
 */
vz.wui.updateAxisBadges = function(plot) {
	var flot = $('#flot');
	flot.find('.axis-badge').remove();

	plot.getYAxes().forEach(function(axis, index) {
		if (axis.show && axis.box && vz.wui.axisRanges[vz.wui.axisKey(index, vz.options.plot.yaxes[index] || {})]) {
			$('<div class="axis-badge" title="Skala manuell geändert, Klick: automatisch, Umschalt+Klick: alle Skalen automatisch">&#8661;</div>')
				.data('axis', index)
				// below the axis, where the time axis labels leave the corner free
				.css({left: axis.box.left + axis.box.width / 2 - 8, top: axis.box.top + axis.box.height + 3})
				.appendTo(flot);
		}
	});
};

/**
 * Mouse handling for the y axes
 */
vz.wui.initAxisNavigation = function() {
	var flot = $('#flot')[0],
		drag = null;

	// Capture phase: runs before flot's handlers on the overlay canvas, so the
	// x selection does not start when the mouse is pressed on an axis.
	flot.addEventListener('mousedown', function(event) {
		var hit = event.button === 0 && !$(event.target).closest('.axis-badge').length && vz.wui.axisAt(event);
		if (!hit) {
			return;
		}

		var range = vz.wui.axisRange(hit.index, hit.axis);
		drag = {index: hit.index, y: event.clientY, min: range.min, max: range.max, height: vz.plot.height()};
		$('body').addClass('axis-dragging');
		event.stopPropagation();
		event.preventDefault();
	}, true);

	flot.addEventListener('mousemove', function(event) {
		flot.style.cursor = vz.wui.axisAt(event) ? 'ns-resize' : '';
	}, true);

	flot.addEventListener('wheel', function(event) {
		var hit = vz.wui.axisAt(event);
		if (!hit) {
			return;
		}
		event.preventDefault(); // do not scroll the page
		event.stopPropagation();

		var range = vz.wui.axisRange(hit.index, hit.axis),
			dy = event.deltaY * (event.deltaMode === 1 ? 33 : 1), // lines to pixels
			factor = Math.exp(Math.max(-300, Math.min(300, dy)) * 0.002),
			// value under the pointer, from the same range that is going to be changed
			pos = Math.max(0, Math.min(1, (hit.y - vz.plot.getPlotOffset().top) / vz.plot.height())),
			value = range.max - pos * (range.max - range.min);

		vz.wui.setAxisRange(hit.index, value - (value - range.min) * factor, value + (range.max - value) * factor);
	}, {capture: true, passive: false});

	flot.addEventListener('dblclick', function(event) {
		var hit = vz.wui.axisAt(event);
		if (hit) {
			event.stopPropagation();
			vz.wui.resetAxis(hit.index);
		}
	}, true);

	$(document)
		.on('mousemove', function(event) {
			if (drag) {
				// content follows the pointer: moving down shows higher values
				var delta = (event.clientY - drag.y) * (drag.max - drag.min) / drag.height;
				vz.wui.setAxisRange(drag.index, drag.min + delta, drag.max + delta);
			}
		})
		.on('mouseup', function() {
			if (drag) {
				drag = null;
				$('body').removeClass('axis-dragging');
			}
		});

	$(flot).on('click', '.axis-badge', function(event) {
		if (event.shiftKey) {
			vz.wui.resetAllAxes();
		}
		else {
			vz.wui.resetAxis($(this).data('axis'));
		}
	});
};

$(function() {
	vz.wui.initAxisNavigation();
});
