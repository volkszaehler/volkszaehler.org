$(function(){
	if(vz.options.followingLegend){
        var offset = $('#plot').offset();

        $('#plot').on('mousemove', function(e){
            w = $('.flot-base').width()

            if(e.pageX > (w/2)){
                $('.legend table, .legend div').css({
                    right:  (w+40)-e.pageX,
                    left: 'unset',
                    top:   e.pageY - offset.top -20
                });
            }else{
                $('.legend table, .legend div').css({
                    right: 'unset',
                    left:  e.pageX+20 - offset.left,
                    top:   e.pageY - offset.top -20
                });
            }
        });
        $('#plot').on('mouseout', function(e){
            $('.legend table, .legend div').css({
                right: 'unset',
                left:  '40px',
                top:   '13px'
            });
        });
    }
});