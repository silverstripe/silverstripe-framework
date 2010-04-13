
describe 'Concrete'
  describe 'Basics'
    before
      $.concrete.warningLevel = $.concrete.WARN_LEVEL_BESTPRACTISE;
      $('body').append('<div id="dom_test"></div>');
    end
    after
      $('#dom_test').remove();
    end
  
    before_each
      $.concrete.clear_all_rules();
      $('#dom_test').html('<div id="a" class="a b c" data-fieldtype="foo"></div><div id="b" class="c d e"></div>');
    end

    it 'can attach and call a base function'
      $('#a').concrete({
        foo: function(){return this.attr('id');}
      });
      $('.a').foo().should.equal 'a'
    end
    
    it 'can attach and call a base function on a selector using a data attribute selection'
      $('[data-fieldtype=foo]').concrete({
        foo: function(){return this.attr('id');}
      });
      $('.a').foo().should.equal 'a'
    end

    it 'can attach and call several base functions'
      $('#a').concrete({
        foo: function(){return 'foo_' + this.attr('id');},
        bar: function(){return 'bar_' + this.attr('id');}
      }); 
      $('.a').foo().should.equal 'foo_a'
      $('.a').bar().should.equal 'bar_a'
    end

    it 'can attach and call a namespaced function'
      $.concrete('bar', function($){
        $('#a').concrete({
          foo: function(){return this.attr('id');}
        });
      });
      $('.a').concrete('bar').foo().should.equal 'a'
    end

    it 'can attach and call a nested namespaced function'
      $.concrete('qux.baz.bar', function($){
        $('#a').concrete({
          foo: function(){return this.attr('id');}
        });
      });
      $('.a').concrete('qux.baz.bar').foo().should.equal 'a'
    end

    it 'can call two functions on two elements'
      var res = []
      $('#a').concrete({
        foo: function(){res.push(this.attr('id'));}
      });
      $('#b.c').concrete({
        foo: function(){res.push(this.attr('id'));}
      });
      $('#dom_test div').foo();
      res.should.eql ['b', 'a']
    end

    it 'can call two namespaced functions on two elements'
      var res = []
      $.concrete('bar', function($){
        $('#a').concrete({
          foo: function(){res.push(this.attr('id'));}
        });
        $('#b.c').concrete({
          foo: function(){res.push(this.attr('id'));}
        });
      });
      $('#dom_test div').concrete('bar').foo();
      res.should.eql ['b', 'a']
    end

  end
end
