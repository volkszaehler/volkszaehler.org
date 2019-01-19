vz.dialog.open = function (e) {
    e.addClass('visible');
    wui_dialog_setpostion($(e));
};

vz.dialog.close = function (e) {
    if(e.attr('id') === 'entity-add') {
        e.removeClass('visible');
    }else {
        e.remove();
    }
    vz.wui.errorDialog = false;
};

vz.dialog.btnclose = function (e) {
    var y = e.parentElement.parentElement;
    this.close($(y));
};

function wui_dialog_close(item) {
    item.closest('dialog').remove();
    vz.wui.errorDialog = false;
}


function wui_dialog(title, content, buttons, entity, css, newwindow = false) {

    if (buttons.toString() !== "") {
        var btn_div = getButtons(buttons);
    }

    winheight = window.innerHeight;

    if ($('.wui_dialog').length > 0 && !newwindow) {
        var dialog = $('.wui_dialog')
            .empty()
            .addClass('visible')
            .append(
                $('<div>')
                    .addClass('dialog-titlebar')
                    .append(
                        $('<span>').html(title),
                        $('<input>')
                            .addClass('dialog-close')
                            .attr('type', 'image')
                            .attr('src', 'img/ic_close_white.png')
                            .click(function () {
                                vz.dialog.btnclose(this)
                            })
                    ),
                $('<div>')
                    .addClass('dialog-content')
                    .addClass(css)
                    .append(content)
                    .append(btn_div)
            )
        var height = dialog.height();
        var topheight = (winheight - height) / 2;
        dialog.css('top', topheight);
    } else {
        var dialog = $('<dialog>')
            .addClass('wui_dialog')
            .addClass('visible')
            .append(
                $('<div>')
                    .addClass('dialog-titlebar')
                    .append(
                        $('<span>').html(title),
                        $('<input>')
                            .addClass('dialog-close')
                            .attr('type', 'image')
                            .attr('src', 'img/ic_close_white.png')
                            .click(function () {
                                vz.dialog.btnclose(this)
                            })
                    ),
                $('<div>')
                    .addClass('dialog-content')
                    .addClass(css)
                    .append(content)
                    .append(btn_div)
            );
        $('body')
            .append(
                dialog
            )

        var height = dialog.height();
        var topheight = (winheight - height) / 2;
        dialog.css('top', topheight);

    }
}

function wui_dialog_setpostion(dialog){
    var winheight = window.innerHeight;
    var height = dialog.height();
    var topheight = (winheight - height) / 2;
    dialog.css('top', topheight);
}


function getButtons(buttons) {
    var div = $('<div>');
    for (var m in buttons) {
        if (typeof buttons[m] == "function") {
            div.append(
                $('<button>')
                    .html(m)
                    .click(buttons[m])
            );
        }
    }
    return div;
}


$(function () {
    $('#tab_entity-public, #tab_entity-subscribe, #tab_entity-create').click(function () {
        $('#tab_entity-public, #tab_entity-subscribe, #tab_entity-create').removeClass('tab-selected');
        $(this).addClass('tab-selected');

        switch ($(this).attr('id')) {
            case 'tab_entity-public':
                $('#entity-public').attr("class", "visible");
                $('#entity-subscribe').attr("class", "invisible");
                $('#entity-create').attr("class", "invisible");
                break;
            case 'tab_entity-subscribe':
                $('#entity-public').attr("class", "invisible");
                $('#entity-subscribe').attr("class", "visible");
                $('#entity-create').attr("class", "invisible");
                break;
            case 'tab_entity-create':
                $('#entity-public').attr("class", "invisible");
                $('#entity-subscribe').attr("class", "invisible");
                $('#entity-create').attr("class", "visible");
                wui_dialog_setpostion($('#entity-create').closest('dialog'));
                break;
        }
    });

    if(vz.options.fullscreen) {
        $('#fullscreen').click(function (e) {
            x = document.getElementsByTagName('section')[0];
            var fs = window.fullScreen;
            if (fs) {
                closeFullscreen(document);
            } else {
                openFullscreen(x);
            }
        });
    }else{
        var full = document.getElementById('fullscreen');
        full.className='invisible';
    }
    var changeHandler = function(){
        var fs = window.fullScreen;
        if (fs) {
            hs = $('#controls').closest('section').height();
            $('#flot').height(hs-100);
        }
        else {
            $('#flot').height(300);
        }
    };

    document.addEventListener("fullscreenchange", changeHandler, false);
    document.addEventListener("webkitfullscreenchange", changeHandler, false);
    document.addEventListener("mozfullscreenchange", changeHandler, false);

});


function openFullscreen(elem) {
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.mozRequestFullScreen) { /* Firefox */
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) { /* IE/Edge */
        elem.msRequestFullscreen();
    }
}

function closeFullscreen(elem){
    if (elem.exitFullscreen) {
        elem.exitFullscreen();
    } else if (elem.webkitExitFullscreen) {
        elem.webkitExitFullscreen();
    } else if (elem.mozCancelFullScreen) {
        elem.mozCancelFullScreen();
    } else if (elem.msExitFullscreen) {
        elem.msExitFullscreen();
    }
}



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
        $('#zooming').html(distzoomingstart);
        console.log(distzoomingstart);
    }
    function zoomingmove(e){
        distzoominglastmove = Math.hypot(
            e.targetTouches[0].pageX - e.targetTouches[1].pageX,
            e.targetTouches[0].pageY - e.targetTouches[1].pageY);
        $('#zooming').html(distzoominglastmove);
        console.log(distzoominglastmove);
    }

    function zoomingend(e){
        $('#zooming').html(distzoomingstart + ";"+distzoominglastmove+"; "+(distzoomingstart-distzoominglastmove));
        console.log(distzoominglastmove);

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
            var swiperightBol = (elapsedTime <= allowedTime && dist >= threshold && Math.abs(touchobj.pageY - startY) <= 100);
            var swipeleftBol = (elapsedTime <= allowedTime && distleft >= threshold && Math.abs(startY - touchobj.pageY) <= 100);
            handleswipe(swipeleftBol);
            handleswipeleft(swiperightBol);
        }
       // e.preventDefault()
    }, false)

}, false); // end window.onload



