describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Events', function(){

		beforeEach(function(){
			$.entwine.synchronous_mode();
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c"></div>');
		});

		it('calls onfoo when foo triggered', function(){
			var a = 0;

			$('#a').entwine({
				onfoo: function(){a = 1;}
			});

			expect(a).toEqual(0);

			$('#a').trigger('foo');
			expect(a).toEqual(1);
		});

		it('only calls most specific onfoo when foo triggered', function(){
			var a = 0, b = 0;

			$('#a.a').entwine({
				onfoo: function(){a = 1;}
			});
			$('#a').entwine({
				onfoo: function(){b = 1;}
			});

			expect(a).toEqual(0);
			expect(b).toEqual(0);

			$('#a').trigger('foo');
			expect(a).toEqual(1);
			expect(b).toEqual(0);
		});

		it('calls namespaced onfoo when foo triggered', function(){
			var a = 0;

			$('#a').entwine('bar', function($){return{
				onfoo: function(){a = 1;}
			};});

			expect(a).toEqual(0);

			$('#a').trigger('foo');
			expect(a).toEqual(1);
		});

		it('calls most specific namespaced onfoo and most specific non-namespaced onfoo when foo triggered', function(){
			var a = 0, b = 0, c = 0, d = 0;

			$('#a.a').entwine({
				onfoo: function(){a = 1;}
			});
			$('#a').entwine({
				onfoo: function(){b = 1;}
			});
			$('#a.a').entwine('bar', function($){return{
				onfoo: function(){c = 1;}
			};});
			$('#a').entwine('bar', function($){return{
				onfoo: function(){d = 1;}
			};});

			expect([a, b, c, d]).toEqual([0, 0, 0, 0]);

			$('#a').trigger('foo');
			expect([a, b, c, d]).toEqual([1, 0, 1, 0]);
		});

		it('calls up correctly on _super', function(){
			var a = 0, b = 0;

			$('#a').entwine({
				onfoo: function(){a += 1;}
			});
			$('#a.a').entwine({
				onfoo: function(){this._super(); b += 1; this._super();}
			});

			expect([a, b]).toEqual([0, 0]);

			$('#a').trigger('foo')
			expect([a, b]).toEqual([2, 1]);
		});

		it('passes event object', function(){
			var event;

			$('#a').entwine({
				onfoo: function(e){event = e;}
			});

			$('#a').trigger('foo');
			expect(event.type).toBeDefined();
			expect(event.type).toEqual('foo');
			expect(event.target).toHaveAttr('id', 'a');
		});

		it('delegates submit events to forms', function(){
			var a = 0;
			$('<form class="foo" action="javascript:undefined">').appendTo('#dom_test');

			$('.foo').entwine({
				onsubmit: function(e, d){a = 1;}
			});

			expect(a).toEqual(0);

			$('.foo').trigger('submit');
			expect(a).toEqual(1);
		});

		describe('can pass event data', function(){

			it('on custom events', function(){
				var data;

				$('#a').entwine({
					onfoo: function(e, d){data = d;}
				});

				$('#a').trigger('foo', {cheese: 'burger'});
				expect(data.cheese).toEqual('burger');
			});

			it('on normal events', function(){
				var data;

				$('#a').entwine({
					onclick: function(e, d){data = d;}
				});

				$('#a').trigger('click', {finger: 'left'});
				expect(data.finger).toEqual('left');
			});

			it('on submit', function(){
				var data;
				$('<form class="foo" action="javascript:undefined">').appendTo('#dom_test');

				$('.foo').entwine({
					onsubmit: function(e, d){data = d; return false;}
				});

				$('.foo').trigger('submit', {cheese: 'burger'});
				expect(data.cheese).toEqual('burger');
			});
		});

		describe('calls onchange on checkboxes properly', function(){

			beforeEach(function(){
				$('#dom_test').html('<input id="i" type="checkbox" name="test_input_i"  value="i" />');
			});

			it('calls onchange', function(){
				var a = 0;

				$('#i').entwine({
					onchange: function(){a += 1;}
				});

				// Can't just "click()" - it's not the same as an actual click event
				$('#i').trigger('focusin');
				$('#i')[0].click();
				expect(a).toEqual(1);
			});

			it('calls onchange only once per change', function(){
				var a = 0;

				$('#i').entwine({
					onchange: function(){a += 1;}
				});

				$('#i').trigger('focusin');
				$('#i')[0].click();
				expect(a).toEqual(1);

				$('#i').trigger('focusout');
				$('#i').trigger('focusin');
				$('#i').trigger('focusout');
				expect(a).toEqual(1);

				$('#i')[0].click();
				expect(a).toEqual(2);
			});

			it('calls onchange even if checked attribute altered in mean time', function(){
				var a = 0;

				$('#i').entwine({
					onchange: function(){a += 1;}
				});

				$('#i').trigger('focusin');
				$('#i')[0].click();
				expect(a).toEqual(1);

				$('#i').removeAttr('checked');

				$('#i').trigger('focusin');
				$('#i')[0].click();
				expect(a).toEqual(2);
			});
		});
	});
});