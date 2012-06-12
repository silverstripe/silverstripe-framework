describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('Event Capture', function(){

		beforeEach(function(){
			$.entwine.synchronous_mode();
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c"><div id="d" class="d e f"></div></div>');
		});

		it('can capture by direct selector', function(){
			var triggers = [];

			$('#d').entwine({
				'from#a': {
					onsynthetic: function(){ triggers.push(this.attr('id')); }
				}
			});

			$('#a').trigger('synthetic');
			expect(triggers).toEqual(['d'])

			$('#a').trigger('synthetic');
			expect(triggers).toEqual(['d', 'd']);

			$('#dom_test').trigger('synthetic');
			expect(triggers).toEqual(['d', 'd']);
		});

		it('can capture by indirect selector', function(){
			var triggercount = 0;

			$('#d').entwine({
				A: '#a',
				fromA: {
					onsynthetic: function(){ triggercount += 1;}
				}
			});

			$('#a').trigger('synthetic');
			expect(triggercount).toEqual(1);

			$('#a').trigger('synthetic');
			expect(triggercount).toEqual(2);

			$('#dom_test').trigger('synthetic');
			expect(triggercount).toEqual(2);
		});

		it('can capture by returning element set', function(){
			var triggercount = 0;

			$('#d').entwine({
				getA: function(){ return this.parent(); },
				fromA: {
					onsynthetic: function(){ triggercount += 1;}
				}
			});

			$('#a').trigger('synthetic');
			expect(triggercount).toEqual(1);

			$('#a').trigger('synthetic');
			expect(triggercount).toEqual(2);

			$('#dom_test').trigger('synthetic');
			expect(triggercount).toEqual(2);
		});

		it('can capture by selector, more specific rules override correct', function(){
			var triggerlist = [];

			$('.d').entwine({
				'from#a': {
					onsynthetic: function(){ triggerlist.push(1); }
				}
			});
			$('#d').entwine({
				'from#a': {
					onsynthetic: function(){ triggerlist.push(2); }
				}
			});

			$('#a').trigger('synthetic');
			expect(triggerlist).toEqual([2]);
		});

		it('gets events and data passed through correctly', function(){
			var a, b;

			$('#d').entwine({
				'from#a': {
					onsynthetic: function(e, data){ a = e.passthrough; b = data; }
				}
			});

			var e = $.Event('synthetic'); e.passthrough = 'foo';
			$('#a').trigger(e, ['bam']);

			expect(a).toEqual('foo');
			expect(b).toEqual('bam');
		});

		it('can handle different from endings', function(){
			var triggercount = 0;

			$('#d').entwine({
				'from#a': {
					onsynthetic: function(){ triggercount += 1;}
				},
				'from.b': {
					onsynthetic: function(){ triggercount += 1;}
				}
			});

			$('#a').trigger('synthetic');
			expect(triggercount).toEqual(2);
		});

		it('can capture from window', function(){
			var triggercount = 0;

			$('#d').entwine({
				fromWindow: {
					onsynthetic: function(){ triggercount += 1;}
				}
			});

			$(window).trigger('synthetic');
			expect(triggercount).toEqual(1);
		});
	});
});