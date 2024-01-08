window.addEventListener('load', function(){

    var touchsurface = document.getElementById('flot'),
        startX,
        startY,
        dist,
        distleft,
        threshold = 150, //required min distance traveled to be considered swipe
        allowedTime = 500, // maximum time allowed to travel that distance
        elapsedTime,
        startTime,
        zooming,
        distzoomingstart,
        distzoominglastmove;

    function handleswipe(isrightswipe){
        if (isrightswipe) {
            var btn = $('<button>').val('move-forward');
            btn.click(vz.wui.handleControls);
            btn.click();
        }
    }
    function handleswipeleft(isleftswipe){
        if (isleftswipe) {var btn = $('<button>').val('move-back');
            btn.click(vz.wui.handleControls);
            btn.click();
        }
    }

    function zoomingstart(e){
        distzoomingstart = Math.hypot(
            e.targetTouches[0].pageX - e.targetTouches[1].pageX,
            e.targetTouches[0].pageY - e.targetTouches[1].pageY);
    }
    function zoomingmove(e){
        distzoominglastmove = Math.hypot(
            e.targetTouches[0].pageX - e.targetTouches[1].pageX,
            e.targetTouches[0].pageY - e.targetTouches[1].pageY);
    }

    function zoomingend(e){
        var diff = distzoomingstart-distzoominglastmove;
        var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min,
            middle = vz.options.plot.xaxis.min + delta/2;

        if(diff < 0) {
            //zoom-in
            vz.wui.period = null;
            if (vz.wui.tmaxnow)
                vz.wui.zoom(moment().valueOf() - delta / 2, moment().valueOf());
            else
                vz.wui.zoom(middle - delta / 2, middle + delta / 2);

        }else {
            //zoom-out
            vz.wui.period = null;
            vz.wui.zoom(
                middle - delta,
                middle + delta
            );
        }
    }

    touchsurface.addEventListener('touchstart',  function(e){
        zooming = false;
        if (e.targetTouches.length == 2) {
            zooming = true;
            zoomingstart(e);
            e.preventDefault()
        }else {
            var touchobj = e.changedTouches[0];
            dist = 0;
            distleft = 0;
            startX = touchobj.pageX;
            startY = touchobj.pageY;
            startTime = new Date().getTime(); // record time when finger first makes contact with surface
        }
        //e.preventDefault()
    }, false);


    touchsurface.addEventListener('touchmove', function(e){
        if(zooming) {
            zoomingmove(e);
            e.preventDefault() // prevent scrolling when inside DIV
        }
    }, false);

    touchsurface.addEventListener('touchend', function(e){
        if (zooming) {
            zoomingend(e);
            zooming = false;
            e.preventDefault()
        }else {
            var touchobj = e.changedTouches[0];
            dist = touchobj.pageX - startX; // get total dist traveled by finger while in contact with surface
            distleft = startX - touchobj.pageX; // get total dist traveled by finger while in contact with surface
            elapsedTime = new Date().getTime() - startTime;// get time elapsed
            // check that elapsed time is within specified, horizontal dist traveled >= threshold, and vertical dist traveled <= 100
            var swipeleftBol = (elapsedTime <= allowedTime && dist >= threshold && Math.abs(touchobj.pageY - startY) <= 100);
            var swiperightBol = (elapsedTime <= allowedTime && distleft >= threshold && Math.abs(startY - touchobj.pageY) <= 100);
            handleswipe(swiperightBol);
            handleswipeleft(swipeleftBol);
        }
        // e.preventDefault()
    }, false)

}, false); // end window.onload

