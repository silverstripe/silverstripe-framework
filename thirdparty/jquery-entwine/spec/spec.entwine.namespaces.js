describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Namespaces', function(){

		beforeEach(function() {
			$.entwine.synchronous_mode();
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c"></div><div id="b" class="c d e"></div>');
		});

		it('namespaced functions work (single definition mode)', function(){
			$('#a').entwine('bar', function($){return{
				bar: function(){return 'a';}
			};});

			expect($('#a').entwine('bar').bar()).toEqual('a');
		});

		it('namespaced functions work (block definition mode)', function(){
			$.entwine('zap', function($){
				$('#a').entwine({
					bar: function(){return 'a';}
				});
			});

			expect($('#a').entwine('zap').bar()).toEqual('a');
		});

		it('double-namespaced functions work (block definition mode)', function(){
			$.entwine('zap', function($){
				$.entwine('pow', function($){
					$('#a').entwine({
						bar: function(){return 'a';}
					});
				});
			});

			expect($('#a').entwine('zap.pow').bar()).toEqual('a');
		})

		it('revert to base namespacing work (block definition mode)', function(){
			$.entwine('zap', function($){
				$.entwine('.pow', function($){
					$('#a').entwine({
						bar: function(){return 'a';}
					});
				});
			});

			expect($('#a').entwine('pow').bar()).toEqual('a');
		});

		it('internal to namespace, will look up functions in namespace before in base', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);},
				bar: function(){res.push(2); this.foo();}
			});
			$('#a').entwine('bar', function($){return{
				foo: function(){res.push(3);},
				bar: function(){res.push(4); $(this).foo();}
			};});

			$('#dom_test div').bar();
			expect(res).toEqual([2, 1]);

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1, 4, 3]);
		});

		it('internal to namespace, will look up functions in namespace before in base, even in closure', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);},
				bar: function(){res.push(2); this.foo();}
			});

			$('#a').entwine('bar', function($){return{
				foo: function(){res.push(3);},
				bar: function(){
					res.push(4);
					$('#a').each(function(){$(this).foo();});
				}
			};});

			$('#dom_test div').bar();
			expect(res).toEqual([2, 1]);

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1, 4, 3]);
		});

		it('internal to namespace, will look up functions in namespace before in base, even in onmatch', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);},
				bar: function(){res.push(2); this.foo();}
			});
			$('#a').entwine('bar', function($){return{
				foo: function(){res.push(3);}
			};});
			$('#a.d').entwine('bar', function($){return{
				onmatch: function(){res.push(4); this.foo();}
			};});

			$('#dom_test div').bar();
			expect(res).toEqual([2, 1]);

			$('#a').addClass('d');
			expect(res).toEqual([2, 1, 4, 3]);
		});

		it('internal to namespace, will look up functions in base when not present in namespace', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);}
			});
			$('#a').entwine('bar', function($){return{
				bar: function(){res.push(2); this.foo();}
			};});

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1]);
		});

		it('internal to namespace, will not look up functions in base if present in namespace, even when not applicable to selector', function(){
			var res = [];

			$('#a').entwine('bar', function($){return{
				foo: function(){this.bar();}
			};});
			$('#a').entwine({
				bar: function(){res.push(1);}
			});
			$('span').entwine('bar', function($){return{
				bar: function(){res.push(2);}
			};});

			$('#a').entwine('bar').foo()
			expect(res).toEqual([]);
		});

		it('internal to namespace, can be directed to base namespace', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);},
				bar: function(){res.push(2); this.foo();}
			});
			$('#a').entwine('bar', function($){return{
				foo: function(){res.push(3);},
				bar: function(){res.push(4); this.foo(); this.entwine('.').foo();}
			};});

			$('#dom_test div').bar();
			expect(res).toEqual([2, 1]);

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1, 4, 3, 1]);
		});

		it('internal to namespace, will look up functions in namespace called the same as a regular jQuery base function', function(){
			var res = [];

			$('#a').entwine('bar', function($){return{
				load: function(){res.push(1);},
				bar: function(){res.push(2); this.load();}
			};});

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1]);
		});

		it('internal to namespace, can be directed to regular jQuery base function', function(){
			var res = [];

			$.fn.testy = function(){res.push(1);}

			$('#a').entwine('bar', function($){return{
				testy: function(){res.push(3);},
				bar: function(){res.push(2); this.entwine('.').testy();}
			};});

			$('#dom_test div').entwine('bar').bar();
			expect(res).toEqual([2, 1]);
		});

		it('internal to namespace, can be directed to sub namespace', function(){
			var res = [];

			$.entwine('zap', function($){
				$('#a').entwine({
					foo: function(){res.push(1); this.entwine('pow').bar();}
				});
				$.entwine('pow', function($){
					$('#a').entwine({
						bar: function(){res.push(2);}
					});
				});
			});

			$('#dom_test div').entwine('zap').foo();
			expect(res).toEqual([1, 2]);
		});

		it('internal to namespace, can be directed to unrelated namespace', function(){
			var res = [];

			$.entwine('zap', function($){
				$('#a').entwine({
					foo: function(){res.push(1); this.entwine('.pow').bar();}
				});
				$.entwine('pow', function($){
					$('#a').entwine({
						bar: function(){res.push(2);}
					});
				});
			});
			$.entwine('pow', function($){
				$('#a').entwine({
					bar: function(){res.push(3);}
				});
			});

			$('#dom_test div').entwine('zap').foo();
			expect(res).toEqual([1, 3]);
		});

		it('a function passed out of a namespace will remember its namespace', function(){
			var res = [];
			var func = function(func){
				func.call($('#a, #b'));
			};

			$('#a, #b').entwine('bar', function($){return{
				zap: function(){res.push($(this).attr('id'));},
				bar: function(){res.push(2); func(this.zap);}
			};});

			$('#dom_test #a').entwine('bar').bar();
			expect(res).toEqual([2, 'b', 'a']);
		});

		it('using block functions', function(){
			var res = [];

			$('#a').entwine({
				foo: function(){res.push(1);}
			});
			$('#a').entwine('bar', function($){return{
				foo: function(){res.push(3);}
			};});

			$('#dom_test div').foo();
			expect(res).toEqual([1]);

			$('#dom_test div').entwine('bar', function($){$(this).foo();});
			expect(res).toEqual([1, 3]);
		});

	});

});