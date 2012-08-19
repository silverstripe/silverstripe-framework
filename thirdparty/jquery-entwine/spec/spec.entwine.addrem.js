describe('Entwine', function(){

	beforeEach(function(){
		$('body').append('<div id="dom_test"></div>');
	});

	afterEach(function(){
		$('#dom_test').remove();
	});

	describe('onadd and onremove', function(){

		beforeEach(function(){
			$.entwine.synchronous_mode();
			$.entwine.clear_all_rules();
			$('#dom_test').html('<div id="a" class="a b c"></div>');
		});

		describe('onremove', function(){
			it('calls onremove on a removed element', function(){
				var called = false;

				$('#a').entwine({
					onremove: function(){called = true;}
				});

				$('#a').remove();
				expect(called).toBeTruthy();
			});

			it('calls onremove only on elements that match selectors, and does so when multiple elements are removed', function(){
				var removed = [];

				$('#a').html('<div id="b" class="catchremove"></div><div id="c"></div><div id="d" class="catchremove"></div>');

				$('.catchremove').entwine({
					onremove: function(){removed.push(this.attr('id'));}
				});

				$('#a').remove();
				expect(removed).toEqual(['d', 'b']);
			});

			it('allows access to data in an onremove handler', function(){
				var val = 0;

				$('#a').data('Bam', 1);

				$('#a').entwine({
					onremove: function(){val = this.data('Bam');}
				});

				$('#a').remove();
				expect(val).toEqual(1);
			});
		});

		describe('onadd', function(){
			it('calls onadd on an appended', function(){
				var called = false;

				$('#b').entwine({
					onadd: function(){called = true;}
				});

				$('#a').append('<div id="b"></div>');
				expect(called).toBeTruthy();
			});

			it('calls onadd on an child thats nested when appended', function(){
				var called = false;

				$('#b').entwine({
					onadd: function(){called = true;}
				});

				$('#a').append('<div><div id="b"></div></div>');
				expect(called).toBeTruthy();
			});

			it('calls onadd on an item added via html', function(){
				var called = false;

				$('#b').entwine({
					onadd: function(){called = true;}
				});

				$('#a').html('<div></div><div><div id="b"></div></div>');
				expect(called).toBeTruthy();
			});

			it('calls onadd and onremove correctly via replaceWith', function(){
				var added = [], removed = [], sequence = [];

				$('#a,#b').entwine({
					onadd: function(){
						added.push(this.attr('id'));
						sequence.push(this.attr('id'));
					},
					onremove: function(){
						removed.push(this.attr('id'));
						sequence.push(this.attr('id'));
					}
				});

				$('#a').replaceWith('<div></div><div><div id="b"></div></div>');
				expect(added).toEqual(['b']);
				expect(removed).toEqual(['a']);
				expect(sequence).toEqual(['a', 'b']);
			});
		});
	});
});