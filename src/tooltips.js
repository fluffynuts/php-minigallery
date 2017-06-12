
var IWIDTH=250;				// Tip box width
var ns4;					// Are we using Netscape4?
var ie4;					// Are we using Internet Explorer Version 4?
var ie5;					// Are we using Internet Explorer Version 5 and up?
var kon;					// Are we using KDE Konqueror?
var x,y,winW,winH;  		// Current help position and main window size
var px = 'px';				// px specifier for browsers that require it
var titles = new Array();	// array of tooltip titles
var texts = new Array();	// array of tooltip texts
var tipdelay = 3500;		// how long this tooltip stays alive for if there is no movement
var tippredelay = 350;		// how long to wait before raising the tooltip
var tippy;					// the actual tooltip context
var tippytitle;				// the tooltip title context
var tippytext;				// the tooltip text context
var lasttip="";				// the last tip drawn; to check if we need to reload the tip
var winW;					// window width
var winH;					// window height
var need_snooze=true;		// if the tip should wait a little before loading. Don't touch
var movingcount=0;			// reference of moves against the tip. When it zeroes, the countdown to hide is allowed
var lasttitle='';
var lasttext='';
var autoids;

function ttinit(){
	// initialises some informational variables
	ns4=(document.layers)?true:false;
	ie4=(document.all)?true:false;
	if (ie4) {
		alert("using some variant of ie");
	} else {
		alert("doesn't seem to be ie?!");
	}
	alert(navigator.userAgent);
	ie5=((ie4)&&((navigator.userAgent.indexOf('MSIE 5')>0)||(navigator.userAgent.indexOf('MSIE 6')>0)))?true:false;
	kon=(navigator.userAgent.indexOf('konqueror')>0)?true:false;
	x=0;y=0;winW=800;winH=600;
	idiv=null;
	if(ns4&&document.captureEvents) document.captureEvents(Event.MOUSEMOVE);
	// Workaround for just another netscape bug: Fix browser confusion on resize
	// obviously conqueror has a similar problem :-(
	if(ns4||kon){ nsfix() }
	if(ns4) { px=""; }
}

function nsfix() {
	window.setTimeout("window.onresize=rebrowse", 2000);
}

function createtipbox () {
	document.write('<div id="tipdiv" name="tipdiv" style="position:absolute; visibility: hidden; z-index:20;'
		+'top:0'+px+'; left:0'+px+';"><table width='+IWIDTH+' class="tiptable" cellspacing=0 cellpadding=0>'
		+'<tr><td class="tiptd"><table width="100%" border=0 cellpadding=0 cellspacing=0><tr><th><div '
		+'id="tiptitle" name="tiptitle" class="tiptitle"></div></th></tr></table><table width="100%" '
		+'class="tiptexttable" cellpadding=0 cellspacing=0><tr><td><div id="tiptext" name="tiptext" '
		+'class="tiptext"></div></td></tr></table></td></tr></table></div>'+"\n");
	tippy=document.getElementById('tipdiv');
	tippytitle=document.getElementById('tiptitle');
	tippytext=document.getElementById('tiptext');
}

function createtip(namelist, title, text) {
	// this funciton can take a list of comma-separated elements
	//	notes about this are:
	//	1) the first node is the parent. Following nodes are children
	//	2) nodes mentioned here are mentioned by nodeid. Necessarily, this means
	//		that you have to assign node ids to the nodes in question
	names=namelist.split(',');
	
	for (i=0; i<names.length; i++) {
		titles[names[i]]= title;
		texts[names[i]]	= text;	
		if (el = document.getElementById(names[i])) {
			addEvent(el, "mousemove", showtip);
			addEvent(el, "mouseout", hidetip_force);
		}
	}
}

function deltip(namelist) {
	names=namelist.split(',');
	
	for (i=0; i<names.length; i++) {
		if (el = document.getElementById(names[i])) {
			delEvent(el, "mousemove", showtip);
			delEvent(el, "mouseout", hidetip_force);
		}
	}
}

function autoid () {
	var i=0;
	while (autoid['auto'+i]!==undefined) {i++}
	autoid['auto'+i]='taken';
	return 'auto'+i;
}

function showtip(ev) {
	if (event.srcElement) {
		el=event.srcElement; // for nasty hack browsers that don't pass through the event object (like IE)
		ie5 = true;
	} else {
		if (window.event) {
			ev=window.event;	// for good browsers that pass through an event object (like moz)
		}
		el=ev.target;
	}
	if (el) {
		tipname=el.id;
		if ((titles[tipname]!==undefined) && (texts[tipname]!==undefined)) {
			if (lasttip!=tipname) {
				// check that the content has actually changed
				if (lasttitle!=titles[tipname] || lasttext!=texts[tipname]) {
					tippytitle.innerHTML=titles[tipname];
					tippytext.innerHTML=texts[tipname];
					lasttitle=titles[tipname];
					lasttext=texts[tipname];
				} else {
					need_snooze=false;
				}
			}
			if (need_snooze) {
				crufty_sleep(tippredelay);
				need_snooze=false;
			}
			lasttip=tipname;
			if(ev)   {x=ev.pageX?ev.pageX:ev.clientX?ev.clientX:0; y=ev.pageY?ev.pageY:ev.clientY?ev.clientY:0;}
			else if(event) {x=event.clientX; y=event.clientY;}
			else {x=0; y=0;}
			if((ie4||ie5) && document.documentElement) // Workaround for scroll offset of IE
			{
				x+=document.documentElement.scrollLeft;
				y+=document.documentElement.scrollTop;
			}
			winW=(window.innerWidth)? window.innerWidth+window.pageXOffset-16:document.body.offsetWidth-20;
			winH=(window.innerHeight)?window.innerHeight+window.pageYOffset  :document.body.offsetHeight;
			tippy.style.left=(((x+260)<winW)?x+12:x-255)+px; 
			tippy.style.top=((y<winH-70)?y+12:y-tippy.height-32)+px;
			tippy.style.visibility=ns4?"show":"visible";
			movingcount++;
			window.setTimeout(hidetip, tipdelay);
		}
	}
}

function hidetip () {
	if (--movingcount<=0) {
		tippy.style.visibility=ns4?"hide":"hidden";
		need_snooze=true;
	}
}

function hidetip_force () {
	tippy.style.visibility=ns4?"hide":"hidden";
	need_snooze=true;
}

function addEvent (el, evname, func) {
	if (el.attachEvent) { // IE
		el.attachEvent("on" + evname, func);
	} else if (el.addEventListener) { // Gecko / W3C
		el.addEventListener(evname, func, true);
	} else {
		el["on" + evname] = func;
	}
};

function delEvent(el, evname, func) {
	if (el.attachEvent) {
		el.detachEvent("on" + evname, func);
	} else if (el.addEventListener) {
		el.removeEventListener(evname, func, true);
	} else {
		el["on" + evname] = "";
	}
}

function donothing () {
// does exactly that -- nothing
}

function crufty_sleep(len){ 
	var then,now; 
	then=new Date().getTime();
	now=then;
	while((now-then)<len) {
		now=new Date().getTime();
	}
}
// Initialize after loading the page
window.onload=ttinit;
