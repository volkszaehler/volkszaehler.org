/**
 * Flot plugin for adding gaps to the line in a line graph when a certain x threashold has been reached.
 *
 * Usage:
 *
 * To configure this plugin, values must be added to two areas.
 * 
 * The first is in the global x-axis options:
 * xaxis: {
 *   insertGaps: true,  // enable or disable this plugin
 *   gapColor: rgba(100,100,100,0.2) // the color to use for gaps - undefined is no indication
 * }
 *
 * The second is in the series object for a set of data.
 * var series1 = {
 *   data: [ ... ],
 *   label: 'Series 1',
 *   xGapThresh: 300 // A value of 300 here indicates that a x-gap > 300 will insert a gap
 * }
 *
 * Enjoy!
 *
 * @author Joel Oughton
 */
(function($){
    function init(plot){
        var _options = plot.getOptions();

        function checkXgapEnabled(plot, options){
        
            if (options.xaxis.insertGaps) {
                plot.hooks.processRawData.push(insertGaps);
            }
        }

        /**
         * Indicates line gaps by drawing a vertical rectangle in its place
         */
        function indicateGaps(plot, ctx) {
            if (!_options.xaxis.gapColor) return;

            var gaps = _options.xaxis.gaps, p2c = plot.getAxes().xaxis.p2c, 
            offset = plot.getPlotOffset(), container = plot.getPlaceholder(),
            drawWidth = container.width() - offset.left - offset.right, plotHeight = container.height();
            
            if (!gaps) return;

            ctx.fillStyle = _options.xaxis.gapColor;

            // draw the gaps
            $.each(gaps, function(index, gap) {
                var x1, x2;

                x1 = p2c(gap.start);
                x2 = p2c(gap.end);

                // keep the bar within the graph range
                if (x1 < 0) x1 = 0;
                if (x2 < 0) x2 = 0;
                if (x1 > drawWidth) x1 = drawWidth;
                if (x2 > drawWidth) x2 = drawWidth;

                // only draw if need be
                if (x1 == x2) return;
                
                ctx.fillRect(x1 + offset.left, offset.top, x2 - x1, plotHeight - offset.bottom - offset.top);
            });
        }
        
        function insertGaps(plot, series, data, datapoints){
            if (series.xGapThresh) {
                var prev = -1;
                var holes = [];
                var offset = 0;
                var bin = series.xGapThresh;
                var gaps = [];
                
                // loop through the datapoints
                for (var i = 0; i < data.length; i++) {
                    // check if the data has already been processed
                    if (data[i][0] == null) return;

                    // find a hole in the data
                    if (prev != -1 &&
                    data[i][0] - prev > bin) {
                        // carefully add each hole found
                        holes.push(i + offset);
                        offset++;
                    }
                    prev = data[i][0];
                }
                
                // output all the holes as nulls
                // this breaks the line in flot
                for (var i = 0; i < holes.length; i++) {
                    data.splice(holes[i], 0, [null, null]);
                }
                
                // build up array of gaps
                for (var i = 0; i < data.length; i++) {
                    if (data[i][0] != null) continue;

                    if (data[i - 1] && data[i + 1]) {
                        gaps.push({ start: data[i - 1][0], end: data[i + 1][0] });
                    }
                }
                _options.xaxis.gaps = gaps;
            }
        }
        
        plot.hooks.processOptions.push(checkXgapEnabled);
        plot.hooks.draw.push(indicateGaps);
    }
    
    var options = {
        xaxis: {
            insertGaps: false,  // enable or disable this plugin
            gapColor: undefined // the color to use for gaps - undefined is no indication
        }
    };
    
    $.plot.plugins.push({
        init: init,
        options: options,
        name: "xgapthreshold",
        version: "0.3"
    });
})(jQuery);
