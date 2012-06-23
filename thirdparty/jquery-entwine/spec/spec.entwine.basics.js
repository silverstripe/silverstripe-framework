describe('Entwine', function(){

	beforeEach(function(){
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Basics', function(){

		beforeEach(function(){
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c" data-fieldtype="foo"></div><div id="b" class="c d e"></div>');
		});

		it('can attach and call a base function', function(){
			$('#a').entwine({
				foo: function(){return this.attr('id');}
			});

			expect($('.a').foo()).toEqual('a');
		});

		it('can attach and call a base function on a selector using a data attribute selection', function(){
			$('[data-fieldtype=foo]').entwine({
				foo: function(){return this.attr('id');}
			});

			expect($('.a').foo()).toEqual('a');
		});

		it('can attach and call a base function on a selector using a psuedo-selector taken from jquery', function(){
			$('#a:visible').entwine({
				foo: function(){return this.attr('id');}
			});

			expect($('.a').foo()).toEqual('a');
		});

		it('can attach and call several base functions', function(){
			$('#a').entwine({
				foo: function(){return 'foo_' + this.attr('id');},
				bar: function(){return 'bar_' + this.attr('id');}
			});

			expect($('.a').foo()).toEqual('foo_a');
			expect($('.a').bar()).toEqual('bar_a');
		});

		it('can attach and call a namespaced function', function(){
			$.entwine('bar', function($){
				$('#a').entwine({
					foo: function(){return this.attr('id');}
				});
			});

			expect($('.a').entwine('bar').foo()).toEqual('a');
		});

		it('can attach and call a nested namespaced function', function(){
			$.entwine('qux.baz.bar', function($){
				$('#a').entwine({
					foo: function(){return this.attr('id');}
				});
			});

			expect($('.a').entwine('qux.baz.bar').foo()).toEqual('a');
		});

		it('can call two functions on two elements', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(this.attr('id'));}
			});
			$('#b.c').entwine({
				foo: function(){res.push(this.attr('id'));}
			});

			$('#dom_test div').foo();
			expect(res).toEqual(['b', 'a']);
		});

		it('can call two namespaced functions on two elements', function(){
			var res = [];

			$.entwine('bar', function($){
				$('#a').entwine({
					foo: function(){res.push(this.attr('id'));}
				});
				$('#b.c').entwine({
					foo: function(){res.push(this.attr('id'));}
				});
			});

			$('#dom_test div').entwine('bar').foo();
			expect(res).toEqual(['b', 'a']);
		});
	});
});