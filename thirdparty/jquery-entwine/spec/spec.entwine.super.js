describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Super', function(){

		beforeEach(function(){
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c">Foo</div><div id="b" class="c d e">Bar</div>');
		});

		it('can call the super function', function(){
			var a = 1;

			$('#a').entwine({
				foo: function(){a *= 2;}
			});
			$('#a.a').entwine({
				foo: function(){a += 2; this._super();}
			});

			$('#a').foo();
			expect(a).toEqual(6)
		});

		it('super to a non-existant class should be ignored', function(){
			var a = 1;

			$('#a').entwine({
				foo: function(){a *= 2; this._super();}
			});
			$('#a.a').entwine({
				foo: function(){a += 2; this._super();}
			});

			$('#a').foo();
			expect(a).toEqual(6)
		});

		it('can call super from two different functions without screwing up what super points to', function(){
			var list = [];

			$('#a').entwine({
				foo: function(){list.push('foo'); this.bar();},
				bar: function(){list.push('bar');}
			});
			$('#a.a').entwine({
				foo: function(){list.push('foo2'); this._super(); list.push('foo2');	this._super();},
				bar: function(){list.push('bar2'); this._super();}
			});

			$('#a').foo();
			expect(list).toEqual(['foo2', 'foo', 'bar2', 'bar', 'foo2', 'foo', 'bar2', 'bar'])
		});

		it('can override (and call via super) a non-entwine jquery function', function(){
			var a = 1;

			$('#a').entwine({
				text: function(){a = this._super();}
			});

			expect($('#a').text()).toBeUndefined();
			expect(a).toEqual('Foo')

			expect($('#b').text()).toEqual('Bar')
		});
	});
});