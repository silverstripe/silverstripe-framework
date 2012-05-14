describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Properties', function(){

		beforeEach(function(){
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c"></div><div id="b" class="b c"></div>');
		});

		it('can define and get a basic property', function(){
			$('#a').entwine({
				Foo: null
			});

			expect($('.a').getFoo()).toBeNull();
		});

		it('can define and set a basic property', function(){
			$('#a').entwine({
				Foo: null
			});

			$('.a').setFoo(1);
			expect($('.a').getFoo()).toEqual(1)
		});

		it('can define a default value', function(){
			$('#a').entwine({
				Foo: 1
			});

			expect($('.a').getFoo()).toEqual(1);
		});

		it('can override a default value with a true-ish value', function(){
			$('#a').entwine({
				Foo: 1
			});

			$('#a').setFoo(2);
			expect($('.a').getFoo()).toEqual(2);
		});

		it('can override a default value with a false-ish value', function(){
			$('#a').entwine({
				Foo: 1
			});

			$('#a').setFoo(0);
			expect($('.a').getFoo()).toEqual(0);
		});

		it('should manage proprties in namespaces without clashing', function(){
			$('#a').entwine({
				Foo: 1
			});

			$.entwine('test', function($){
				$('#a').entwine({
					Foo: 2
				});
			});

			expect($('.a').getFoo()).toEqual(1);
			expect($('.a').entwine('test').getFoo()).toEqual(2)

			$('.a').setFoo(4);
			$('.a').entwine('test').setFoo(8);
			expect($('.a').getFoo()).toEqual(4)
			expect($('.a').entwine('test').getFoo()).toEqual(8)
		});

		it('should manage directly setting properties in namespaces without clashing', function(){
			$('#a').entwine({
				Foo: null
			});

			$.entwine('test', function($){
				$('#a').entwine({
					Foo: null
				});
			});

			$('.a').entwineData('Foo', 4);
			$('.a').entwine('test').entwineData('Foo', 8);
			expect($('.a').entwineData('Foo')).toEqual(4);
			expect($('.a').entwine('test').entwineData('Foo')).toEqual(8);
		});

		describe('jQuery style accessors', function(){

			it('can define and get a basic property', function(){
				$('#a').entwine({
					Foo: null
				});

				expect($('.a').Foo()).toBeNull();
			});

			it('can define and set a basic property', function(){
				$('#a').entwine({
					Foo: null
				});

				$('.a').Foo(1);
				expect($('.a').Foo()).toEqual(1)
			});

			it('can define a default value', function(){
				$('#a').entwine({
					Foo: 1
				});

				expect($('.a').Foo()).toEqual(1)
			});
		});
	});
});