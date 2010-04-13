
describe 'Entwine'
  describe 'Basics'
    before
      $.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
      $('body').append('<div id="dom_test"></div>');
    end
    after
      $('#dom_test').remove();
    end
  
    before_each
      $.entwine.clear_all_rules();
      $('#dom_test').html('<div id="a" class="a b c" data-fieldtype="foo"></div><div id="b" class="c d e"></div>');
    end

    it 'can attach and call a base function'
      $('#a').entwine({
        foo: function(){return this.attr('id');}
      });
      $('.a').foo().should.equal 'a'
    end
    
    it 'can attach and call a base function on a selector using a data attribute selection'
      $('[data-fieldtype=foo]').entwine({
        foo: function(){return this.attr('id');}
      });
      $('.a').foo().should.equal 'a'
    end

    it 'can attach and call several base functions'
      $('#a').entwine({
        foo: function(){return 'foo_' + this.attr('id');},
        bar: function(){return 'bar_' + this.attr('id');}
      }); 
      $('.a').foo().should.equal 'foo_a'
      $('.a').bar().should.equal 'bar_a'
    end

    it 'can attach and call a namespaced function'
      $.entwine('bar', function($){
        $('#a').entwine({
          foo: function(){return this.attr('id');}
        });
      });
      $('.a').entwine('bar').foo().should.equal 'a'
    end

    it 'can attach and call a nested namespaced function'
      $.entwine('qux.baz.bar', function($){
        $('#a').entwine({
          foo: function(){return this.attr('id');}
        });
      });
      $('.a').entwine('qux.baz.bar').foo().should.equal 'a'
    end

    it 'can call two functions on two elements'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(this.attr('id'));}
      });
      $('#b.c').entwine({
        foo: function(){res.push(this.attr('id'));}
      });
      $('#dom_test div').foo();
      res.should.eql ['b', 'a']
    end

    it 'can call two namespaced functions on two elements'
      var res = []
      $.entwine('bar', function($){
        $('#a').entwine({
          foo: function(){res.push(this.attr('id'));}
        });
        $('#b.c').entwine({
          foo: function(){res.push(this.attr('id'));}
        });
      });
      $('#dom_test div').entwine('bar').foo();
      res.should.eql ['b', 'a']
    end

  end
end
