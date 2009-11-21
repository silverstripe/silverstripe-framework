describe 'Concrete'
  describe 'Events'
  
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
	 	 
	 it 'calls onfoo when foo triggered'
      var a = 0;
      $('#a').concrete({onfoo: function(){a = 1;} });
      a.should.equal 0
      $('#a').trigger('foo');
      a.should.equal 1
    end
	 
    it 'only calls most specific onfoo when foo triggered'
      var a = 0, b = 0;
      $('#a.a').concrete({onfoo: function(){a = 1;} });
      $('#a').concrete({onfoo: function(){b = 1;} });
      a.should.equal 0
      b.should.equal 0
      $('#a').trigger('foo');
      a.should.equal 1
      b.should.equal 0
    end
	 
    it 'calls namespaced onfoo when foo triggered'
      var a = 0;
      $('#a').concrete('bar', function($){return{onfoo: function(){a = 1;} }});
      a.should.equal 0
      $('#a').trigger('foo');
      a.should.equal 1
    end
	 
    it 'calls most specific namespaced onfoo and most specific non-namespaced onfoo when foo triggered'
      var a = 0, b = 0, c = 0, d = 0;
      $('#a.a').concrete({onfoo: function(){a = 1;} });
      $('#a').concrete({onfoo: function(){b = 1;} });
      $('#a.a').concrete('bar', function($){return{onfoo: function(){c = 1;} }});
      $('#a').concrete('bar', function($){return{onfoo: function(){d = 1;} }});
      [a, b, c, d].should.eql [0, 0, 0, 0] 

      $('#a').trigger('foo');
      [a, b, c, d].should.eql [1, 0, 1, 0] 
    end
	 
    it 'calls up correctly on _super'
      var a = 0, b = 0;
      $('#a').concrete({onfoo: function(){a += 1;} });
      $('#a.a').concrete({onfoo: function(){this._super(); b += 1; this._super();} });
		
      [a, b].should.eql [0, 0]
      $('#a').trigger('foo')
      [a, b].should.eql [2, 1]
    end
	 
  end
end