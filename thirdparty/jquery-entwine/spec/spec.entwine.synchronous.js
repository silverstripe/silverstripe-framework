describe('Entwine', function(){

	beforeEach(function() {
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
		$.entwine.synchronous_mode(true);
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
		$.entwine.synchronous_mode(false);
	});

	describe('Synchronous Mode', function(){

		beforeEach(function(){
			// $.entwine.clear_all_rules();
		});

		it('can modify the DOM in onmatch', function(){
			$('#a').entwine({
				onmatch: function(){this.append('<div class="appended"></div>');}
			});

			$('#dom_test').append('<div id="a" class="a b c" data-fieldtype="foo"></div><div id="b" class="c d e"></div>');
			expect($('#a .appended').length).toEqual(1);
		});

		it('can modify the DOM in onunmatch', function(){
			$('#a').entwine({
				onmatch: function(){ /* NOP */ },
				onunmatch: function(){$('#dom_test').append('<div class="appended"></div>');}
			});

			$('#dom_test').append('<div id="a" class="a b c" data-fieldtype="foo"></div><div id="b" class="c d e"></div>');
			$('#dom_test').find('#a').remove();
			expect($('#dom_test .appended').length).toEqual(1);
		});

	});
});