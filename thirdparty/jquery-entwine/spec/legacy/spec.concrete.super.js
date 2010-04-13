
describe 'Concrete'
  describe 'Super'
    before
      $('body').append('<div id="dom_test"></div>')
    end
    after
      $('#dom_test').remove()
    end
  
    before_each
      $.concrete.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c">Foo</div><div id="b" class="c d e">Bar</div>')
    end

    it 'can call the super function'
      var a = 1;
      $('#a').concrete({
        foo: function(){a *= 2;}
      });
      $('#a.a').concrete({
        foo: function(){a += 2; this._super();}
      });
      $('#a').foo();
      a.should.equal 6
    end
    
    it 'super to a non-existant class should be ignored'
      var a = 1;
      $('#a').concrete({
        foo: function(){a *= 2; this._super();}
      });
      $('#a.a').concrete({
        foo: function(){a += 2; this._super();}
      });
      $('#a').foo();
      a.should.equal 6
    end
    
    it 'can call super from two different functions without screwing up what super points to'
      var list = [];
      $('#a').concrete({
        foo: function(){ list.push('foo'); this.bar(); },
        bar: function(){ list.push('bar'); }
      });
      $('#a.a').concrete({
        foo: function(){ list.push('foo2'); this._super(); list.push('foo2'); this._super(); },
        bar: function(){ list.push('bar2'); this._super(); }
      });
      $('#a').foo();
      list.should.eql [ 'foo2', 'foo', 'bar2', 'bar', 'foo2', 'foo', 'bar2', 'bar' ]
    end
    
    it 'can override (and call via super) a non-concrete jquery function'
      var a = 1
      $('#a').concrete({
        text: function(){ a = this._super(); }
      });
      
      $('#a').text();
      a.should.equal 'Foo'
      
      $('#b').text().should.equal 'Bar'
    end
  end
end
