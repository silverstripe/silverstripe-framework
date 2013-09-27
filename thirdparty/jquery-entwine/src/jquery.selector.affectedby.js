(function($) {

	// TODO:
	// Make attributes & IDs work

	var DIRECT = /DIRECT/g;
	var CONTEXT = /CONTEXT/g;
	var EITHER = /DIRECT|CONTEXT/g;

	$.selector.SelectorBase.addMethod('affectedBy', function(props) {
		this.affectedBy = new Function('props', ([
			'var direct_classes, context_classes, direct_attrs, context_attrs, t;',
			this.ABC_compile().replace(DIRECT, 'direct').replace(CONTEXT, 'context'),
			'return {classes: {context: context_classes, direct: direct_classes}, attrs: {context: context_attrs, direct: direct_attrs}};'
		]).join("\n"));

		// DEBUG: Print out the compiled funciton
		// console.log(this.selector, ''+this.affectedBy);

		return this.affectedBy(props);
	});

	$.selector.SimpleSelector.addMethod('ABC_compile', function() {
		var parts = [];

		$.each(this.classes, function(i, cls){
			parts[parts.length] = "if (t = props.classes['"+cls+"']) (DIRECT_classes || (DIRECT_classes = {}))['"+cls+"'] = t;";
		});

		$.each(this.nots, function(i, not){
			parts[parts.length] = not.ABC_compile();
		});

		return parts.join("\n");
	});

	$.selector.Selector.addMethod('ABC_compile', function(arg){
		var parts = [];
		var i = this.parts.length-1;

		parts[parts.length] = this.parts[i].ABC_compile();
		while ((i = i - 2) >= 0) parts[parts.length] = this.parts[i].ABC_compile().replace(EITHER, 'CONTEXT');

		return parts.join("\n");
	});

	$.selector.SelectorsGroup.addMethod('ABC_compile', function(){
		var parts = [];

		$.each(this.parts, function(i,part){
			parts[parts.length] = part.ABC_compile();
		});

		return parts.join("\n");
	});


})(jQuery);
