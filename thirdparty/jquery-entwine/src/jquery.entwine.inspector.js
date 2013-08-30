
jQuery(function($){
	// Create a new style element
	var styleEl = document.createElement('style');
	styleEl.setAttribute('type', 'text/css');
	(document.head || document.getElementsByTagName('head')[0]).appendChild(styleEl);

	var inspectorCSS = [
		'#entwine-inspector { position: fixed; z-index: 1000001; left: 0; right: 0; height: 400px; background: white; -webkit-box-shadow: 0 5px 40px 0 black; -moz-box-shadow: 0 5px 40px 0 black; }',
		'#entwine-inspector li { list-style: none; margin: 2px 0; padding: 2px 0; }',
		'#entwine-inspector li:hover { background: #eee; }',
		'#entwine-inspector li.selected { background: #ddd; }',

		'#ei-columns { overflow: hidden; display: -webkit-box; display: -moz-box; width: 100%; height: 380px; }',

		'.ei-column { height: 380px; width: 1px; -webkit-box-flex: 1; -moz-box-flex: 1; }',
		'#entwine-inspector .ei-column h1 { display: block; margin: 0; padding: 5px 2px; height: 20px; text-align: center; background: #444; color: #eee; font-size: 14px; font-weight: bold; }',
		'#entwine-inspector .ei-column ul { overflow-y: scroll; height: 350px; }',

		'#ei-options { overflow: hidden; height: 20px; background: #444; color: #eee; }',
		'#ei-options label { padding-right: 5px; border-right: 1px solid #eee; }',

		'.ei-entwined:hover, .ei-selected { background: rgba(128,0,0,0.2); }',
		'.ei-hovernode { position: absolute; z-index: 1000000; background: rgba(0,0,0,0.3); border: 1px solid white; outline: 1px solid white; }',

		'#ei-selectors li { color: #aaa; display: none; }',
		'#ei-selectors li.matching, #entwine-inspector.show-unmatched #ei-selectors li { display: block; }',
		'#ei-selectors li.matching { color: black; }'
	].join("\n");

	// Set the style element to style up the inspector panel
	if(styleEl.styleSheet){
		styleEl.styleSheet.cssText = inspectorCSS;
	}else{
		styleEl.appendChild(document.createTextNode(inspectorCSS));
	}

	var inspectorPanel = $('<div id="entwine-inspector" class="show-unmatched"></div>').appendTo('body');
	var columnHolder = $('<div id="ei-columns"></div>').appendTo(inspectorPanel);
	var optionsHolder = $('<div id="ei-options"></div>').appendTo(inspectorPanel);

	inspectorPanel.css({
		top: -400,
		visibility: 'hidden'
	});

	$('body').bind('keypress', function(e){
		if (e.ctrlKey && e.which == 96) {
			if (inspectorPanel.css('visibility') != 'visible') {
				inspectorPanel.css({top: 0, visibility: 'visible'});
				$('body').css({marginTop: 400});
				initialise();
			}
			else {
				inspectorPanel.css({top: -400, visibility: 'hidden'});
				$('body').css({marginTop: 0});
				reset();
			}

			return false;
		}
	});

	var showUnmatching = $('<input id="ei-option-showunmatching" type="checkbox" checked="checked" />').appendTo(optionsHolder);
	var showUnmatchingLabel = $('<label>Show selectors that dont match</label>').appendTo(optionsHolder);

	showUnmatching.bind('click', function(){
		inspectorPanel.toggleClass('show-unmatched', $(this).val());
	});

	var hovernode;

	var reset = function() {
		$('.ei-entwined').unbind('.entwine-inspector').removeClass('ei-entwined');
		if (hovernode) hovernode.remove();
	}

	var initialise = function(){
		reset();

		$.each($.entwine.namespaces, function(name, namespace){
			$.each(namespace.store, function(name, list){
				$.each(list, function(i, rule){
					var match = $(rule.selector.selector);
					match.addClass('ei-entwined').bind('click.entwine-inspector', displaydetails);
				})
			});
		});
	};

	var dumpElement = function(el) {
		var frag = document.createDocumentFragment();
		var div = document.createElement('div'); frag.appendChild(div);

		var clone = el.cloneNode(false); $(clone).removeClass('ei-entwined').removeAttr('style');

		var i = clone.attributes.length;
		while (i--) {
			var attr = clone.attributes.item(i);
			if (attr.name != 'class' && attr.name != 'id' && attr.value.length > 20) attr.value = attr.value.substr(0, 18)+'..'+attr.value.substr(-2);
		}

		div.appendChild(clone);
		return div.innerHTML;
	};

	var displaydetails = function(e){
		e.preventDefault(); e.stopPropagation();

		columnHolder.empty();

		var columns = {};
		$.each(['elements', 'namespaces', 'methods', 'selectors'], function(i, col){
			columns[col] = $('<div id="ei-'+col+'" class="ei-column"><h1>'+col+'</h1></div>').appendTo(columnHolder);
		})

		var lists = {};

		var ctr = 0;

		lists.elements = $('<ul></ul>').appendTo(columns.elements);

		var displayelement = function(){
			var target = $(this);

			var li = $('<li></li>');
			li.text(dumpElement(this)).attr('data-id', ++ctr).data('el', target).prependTo(lists.elements);

			var namespaces = $('<ul data-element="'+ctr+'"></ul>').appendTo(columns.namespaces);

			$.each($.entwine.namespaces, function(name, namespace){
				var methods = $('<ul data-namespace="'+ctr+'-'+name+'"></ul>');

				$.each(namespace.store, function(method, list){

					if (method == 'ctors') {
						var matchselectors = $('<ul data-method="'+ctr+'-'+name+'-onmatch"></ul>');
						var unmatchselectors = $('<ul data-method="'+ctr+'-'+name+'-onunmatch"></ul>');

						$.each(list, function(i, rule){
							var matchitem = $('<li>'+rule.selector.selector+'</li>').prependTo(matchselectors);
							var unmatchitem = rule.onunmatch ? $('<li>'+rule.selector.selector+'</li>').prependTo(unmatchselectors) : null;

							if (target.is(rule.selector.selector)) {
								matchitem.addClass('matching'); unmatchitem && unmatchitem.addClass('matching');

								if (!methods.parent().length) {
									$('<li data-namespace="'+ctr+'-'+name+'">'+name+'</li>').prependTo(namespaces);
									methods.appendTo(columns.methods);
								}

								if (!matchselectors.parent().length) {
									$('<li data-method="'+ctr+'-'+name+'-onmatch">onmatch</li>').prependTo(methods);
									matchselectors.appendTo(columns.selectors);
								}

								if (rule.onunmatch && !unmatchselectors.parent().length) {
									$('<li data-method="'+ctr+'-'+name+'-onunmatch">onunmatch</li>').prependTo(methods);
									unmatchselectors.appendTo(columns.selectors);
								}
							}
						});
					}
					else {
						var selectors = $('<ul data-method="'+ctr+'-'+name+'-'+method+'"></ul>');

						$.each(list, function(i, rule){
							var ruleitem = $('<li>'+rule.selector.selector+'</li>').prependTo(selectors);

							if (target.is(rule.selector.selector)){
								ruleitem.addClass('matching');

								if (!methods.parent().length) {
									$('<li data-namespace="'+ctr+'-'+name+'">'+name+'</li>').prependTo(namespaces);
									methods.appendTo(columns.methods);
								}

								if (!selectors.parent().length) {
									$('<li data-method="'+ctr+'-'+name+'-'+method+'">'+method+'</li>').prependTo(methods);
									selectors.appendTo(columns.selectors);
								}
							}
						})
					}
				});
			});
		};

		$.each($(e.target).parents().andSelf().filter('.ei-entwined'), displayelement);
		$('#ei-elements > ul:first > li:first').click();
	}

	var activatelist = function(list) {
		list = $(list);

		list.siblings('ul').css('display', 'none');

		list.css('display', 'block');
		list.children().first().click();
	}

	$('#entwine-inspector').live('mouseleave', function(){
		if (hovernode) hovernode.hide();
	})

	$('#entwine-inspector').live('mouseenter', function(){
		if (hovernode) hovernode.show();
	})

	$('#ei-elements > ul > li').live('click', function(e){
		var target = $(e.target), id = target.attr('data-id');
		target.addClass('selected').siblings().removeClass('selected');

		if (!hovernode) {
			hovernode = $('<div class="ei-hovernode"></div>').appendTo('body');
		}

		var hover = target.data('el');
		hovernode.css({width: hover.outerWidth()-2, height: hover.outerHeight()-2, top: hover.offset().top, left: hover.offset().left});

		$('.ei-selected').removeClass('ei-selected');

		activatelist('#ei-namespaces ul[data-element="'+id+'"]');
	});

	$('#ei-namespaces > ul > li').live('click', function(e){
		var target = $(e.target), namespace = target.attr('data-namespace');
		target.addClass('selected').siblings().removeClass('selected');

		activatelist('#ei-methods ul[data-namespace="'+namespace+'"]');
	});

	$('#ei-methods > ul > li').live('click', function(e){
		var target = $(e.target), method = target.attr('data-method');
		target.addClass('selected').siblings().removeClass('selected');

		activatelist('#ei-selectors ul[data-method="'+method+'"]');
	});

});