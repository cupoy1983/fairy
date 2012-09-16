
//** jQuery Scroll to Top Control script- (c) Dynamic Drive DHTML code library: http://www.dynamicdrive.com.
//** Available/ usage terms at http://www.dynamicdrive.com (March 30th, 09')
//** v1.1 (April 7th, 09'):
//** 1) Adds ability to scroll to an absolute position (from top of page) or specific element on the page instead.
//** 2) Fixes scroll animation not working in Opera.
var scrolltotop={	// startline: Integer. Number of pixels from top of doc
					// scrollbar is scrolled before showing control
	// scrollto: Keyword (Integer, or "Scroll_to_Element_ID"). How far to scroll
	// document up when control is clicked on (0=top).
	setting : {
		startline : 100,
		scrollto : 0,
		scrollduration : 100,
		fadeduration : [ 50, 100 ]
	},
	controlHTML : '<div class="nreturn"><a class="ntel fabu" href="/me" id="sider_fabu" target="_blank"></a><a class="ntel" href="http://wpa.qq.com/msgrd?v=3&uin=11102637&site=qq&menu=yes" id="sider_talkonline" target="_blank"></a><a class="ntel nbook" href="javascript:void(0);" id="sider_addmark"></a><a class="nrebtn" href="javascript:void(0);" id="sider_returntop"></a></div>',
	controlattrs : {
		offsetx : 5,
		offsety : 5
	}, // offset of control relative to right/ bottom of window corner
	anchorkeyword : '#top', // Enter href value of HTML anchors on the page that
							// should also act as "Scroll Up" links
	state : {
		isvisible : false,
		shouldvisible : false
	},
	scrollup : function() {
		if (!this.cssfixedsupport) // if control is positioned using JavaScript
			this.$control.css({
				opacity : 0
			}) // hide control immediately after clicking it
		var dest = isNaN(this.setting.scrollto) ? this.setting.scrollto
				: parseInt(this.setting.scrollto)
		if (typeof dest == "string" && jQuery('#' + dest).length == 1) // check
																		// element
																		// set
																		// by
																		// string
																		// exists
			dest = jQuery('#' + dest).offset().top
		else
			dest = 0
		this.$body.animate({
			scrollTop : dest
		}, this.setting.scrollduration);
	},
	keepfixed : function() {
		var $window = jQuery(window)
		var controlx = $window.scrollLeft() + $window.width()
				- this.$control.width() - this.controlattrs.offsetx
		var controly = $window.scrollTop() + $window.height()
				- this.$control.height() - this.controlattrs.offsety
		this.$control.css({
			left : controlx + 'px',
			top : controly + 'px'
		})
	},
	togglecontrol : function() {
		var scrolltop = jQuery(window).scrollTop()
		if (!this.cssfixedsupport)
			this.keepfixed()
		this.state.shouldvisible = (scrolltop >= this.setting.startline) ? true
				: false
		if (this.state.shouldvisible && !this.state.isvisible) {
			this.$control.stop().animate({
				opacity : 1
			}, this.setting.fadeduration[0])
			this.state.isvisible = true
		} else if (this.state.shouldvisible == false && this.state.isvisible) {
			this.$control.stop().animate({
				opacity : 0
			}, this.setting.fadeduration[1])
			this.state.isvisible = false
		}
	},
	addBookmark : function(sURL, sTitle) {
		try {
			window.external.addFavorite(sURL, sTitle);
		} catch (e) {
			try {
				window.sidebar.addPanel(sTitle, sURL, "");
			} catch (e) {
				alert("您的浏览器暂不支持此功能,加入收藏失败，请使用Ctrl+D进行添加");
			}
		}
	},
	init : function() {
		jQuery(document)
				.ready(
						function($) {
							var mainobj = scrolltotop
							var iebrws = document.all
							mainobj.cssfixedsupport = !iebrws || iebrws
									&& document.compatMode == "CSS1Compat"
									&& window.XMLHttpRequest // not IE or
																// IE7+ browsers
																// in standards
																// mode
							mainobj.$body = (window.opera) ? (document.compatMode == "CSS1Compat" ? $('html')
									: $('body'))
									: $('html,body')
							mainobj.$control = $(
									'<div id="topcontrol">'
											+ mainobj.controlHTML + '</div>')
									.css(
											{
												position : mainobj.cssfixedsupport ? 'fixed'
														: 'absolute',
												bottom : mainobj.controlattrs.offsety,
												right : mainobj.controlattrs.offsetx,
												opacity : 0,
												cursor : 'pointer'
											}).attr({
										title : '返回顶部'
									})
									/*
									 * .click(function(){mainobj.scrollup();
									 * return false})
									 */
									.appendTo('body')
							if (document.all && !window.XMLHttpRequest
									&& mainobj.$control.text() != '') // loose
																		// check
																		// for
																		// IE6
																		// and
																		// below,
																		// plus
																		// whether
																		// control
																		// contains
																		// any
																		// text
								mainobj.$control.css({
									width : mainobj.$control.width()
								}) // IE6- seems to require an explicit width
									// on a DIV containing text
							mainobj.togglecontrol()
							$('a[href="' + mainobj.anchorkeyword + '"]').click(
									function() {
										mainobj.scrollup()
										return false
									})
							var isexist_live800 = jQuery('#live12223').attr(
									'id');
							if (String(isexist_live800) == 'undefined') {
								$('#sider_talkonline').css('display', 'block');
							}
							$('#sider_returntop').click(function() {
								mainobj.scrollup();
								return false
							});
							$('#sider_addmark')
									.click(
											function() {
												mainobj
														.addBookmark(
																'http://www.yaojingmao.com',
																'精品女装，时尚搭配，当季服装，淘宝导购，非主流搭配，妖精猫购物分享');
											});
							$(window).bind('scroll resize', function(e) {
								mainobj.togglecontrol()
							})
						})
	}
}
scrolltotop.init()
