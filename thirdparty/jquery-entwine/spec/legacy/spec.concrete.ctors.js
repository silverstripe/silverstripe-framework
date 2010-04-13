describe 'Concrete'
  describe 'Ctors'
  
    before
      $('body').append('<div id="dom_test"></div>')
    end

    after
      $('#dom_test').remove()
    end
   
    before_each
      $.concrete.synchronous_mode();
      $.concrete.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c"></div>')
    end
	 
    it 'calls onmatch when new element created'
      var a = false;
      $('#b').concrete({onmatch: function(){a = true;} });
      a.should.be_false
      $('#a').after('<div id="b"></div>');  
      a.should.be_true
    end
 
    it 'calls onunmatch when new element deleted'
      var a = 0;
      $('#b').concrete({onmatch: function(){a = 1;}, onunmatch: function(){a = 2;} });
		a.should.equal 0
      $('#a').after('<div id="b"></div>');
		a.should.equal 1
      $('#b').remove();
		a.should.equal 2
    end
 
    it 'calls onmatch when ruleset matches after class added'
      var a = 0;
      $('#a.foo').concrete({onmatch: function(){a = 1;} });
      a.should.equal 0
      $('#a').addClass('foo');
		a.should.equal 1
	 end
	 
    it 'calls onmatch in both direct and namespaced onmatch, does not call less specific onmatch'
      var a = 0, b=0, c=0, d=0;
      $('.foo').concrete({onmatch: function(){a = 1;}})
		$('.foo').concrete('bar', function($){return{onmatch: function(){b = 1;}}})
      $('#a.foo').concrete({onmatch: function(){c = 1;}})
		$('#a.foo').concrete('bar', function($){return{onmatch: function(){d = 1}}})
      [a, b, c, d].should.eql [0, 0, 0, 0]
      $('#a').addClass('foo');
      [a, b, c, d].should.eql [0, 0, 1, 1]
	 end

    it 'calls onmatch in both direct and namespaced onmatch, super works as expected'
      var a = 0, b=0, c=0, d=0;
      $('.foo').concrete({onmatch: function(){a += 1;}})
		$('.foo').concrete('bar', function($){return{onmatch: function(){b += 1;}}})
      $('#a.foo').concrete({onmatch: function(){this._super(); c = 1; this._super();}})
		$('#a.foo').concrete('bar', function($){return{onmatch: function(){this._super(); d = 1; this._super();}}})
      [a, b, c, d].should.eql [0, 0, 0, 0]
      $('#a').addClass('foo');
      [a, b, c, d].should.eql [2, 2, 1, 1]
	 end

	 
  end
end