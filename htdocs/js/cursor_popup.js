$(function(){
    if(vz.options.followingLegend){
        $('#plot').on('mousemove', function(e){
            if(vz.options.followingLegend){
                var offset = $('#plot').offset();
                w = $('.flot-base').width()

                if(e.pageX > (w/2)){
                    $('.legend table, .legend div').css({
                        right:  (w+40)-e.pageX,
                        left: 'unset',
                        top:   e.pageY - offset.top -20,
                        'z-index': 2
                    });
                }else{
                    $('.legend table, .legend div').css({
                        right: 'unset',
                        left:  e.pageX+20 - offset.left,
                        top:   e.pageY - offset.top -20,
                        'z-index': 2
                    });
                }
            }
        });
        $('#plot').on('mouseout', function(e){
            if(vz.options.followingLegend){
                $('.legend table, .legend div').css({
                    right: 'unset',
                    left:  '40px',
                    top:   '13px'
                });
            }
        });
    }
});
