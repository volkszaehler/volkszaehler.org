HTTP_GET_VARS = new Array();
strGET = document.location.search.substr(1,document.location.search.length);
if(strGET != '') {
	gArr=strGET.split('&');
	for(i=0;i<gArr.length;++i) {
		v='';vArr=gArr[i].split('=');
		if(vArr.length>1) {
			v=vArr[1];
		}
		HTTP_GET_VARS[unescape(vArr[0])] = unescape(v);
	}
}

var myUUID = '';
if(HTTP_GET_VARS['uuid'])
	myUUID = HTTP_GET_VARS['uuid'];


// easy access to formular with f
var f;

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
	f = document.formular;
	
	// resize chart area for low resolution displays
	// works fine with HTC hero
	// perhaps you have to reload after display rotation
	if($(window).width()<800) {
		$("#Chart").animate({
			width:$(window).width()-40,
			height:$(window).height()-3,
		},0);
		$("#options").animate({
			height:$(window).height()-3,
		},0);
	}
	
	// load channel list
	// loadChannelList();
	
	// start autoReload timer
	window.setInterval("autoReload()",5000);
	
	// code for adding a channel
	var 	uuid = $("#uuid"),
		allFields = $([]).add(uuid),
		tips = $(".validateTips");

	getData();
});
