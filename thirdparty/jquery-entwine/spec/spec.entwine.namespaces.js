
describe 'Entwine'
  describe 'Namespaces'
    before
      $('body').append('<div id="dom_test"></div>')
    end
    after
      $('#dom_test').remove()
    end
  
    before_each
      $.entwine.synchronous_mode();
      $.entwine.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c"></div><div id="b" class="c d e"></div>')
    end

    it 'namespaced functions work (single definition mode)'
      $('#a').entwine('bar', function($){return{
        bar: function(){return 'a';}
      }})
      $('#a').entwine('bar').bar().should.equal 'a'
    end
    
    it 'namespaced functions work (block definition mode)'
      $.entwine('zap', function($){
        $('#a').entwine({
          bar: function(){return 'a';}
        })
      });
      $('#a').entwine('zap').bar().should.equal 'a'
    end
    
    it 'double-namespaced functions work (block definition mode)'
      $.entwine('zap', function($){
        $.entwine('pow', function($){
          $('#a').entwine({
            bar: function(){return 'a';}
          })
        })
      })
      $('#a').entwine('zap.pow').bar().should.equal 'a'
    end

    it 'revert to base namespacing work (block definition mode)'
      $.entwine('zap', function($){
        $.entwine('.pow', function($){
          $('#a').entwine({
            bar: function(){return 'a';}
          })
        })
      })
      $('#a').entwine('pow').bar().should.equal 'a'
    end
    
    it 'internal to namespace, will look up functions in namespace before in base'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);},
        bar: function(){res.push(2); this.foo();}
      })
      $('#a').entwine('bar', function($){return{
        foo: function(){res.push(3);},
        bar: function(){res.push(4); $(this).foo();}
      }})
      
      $('#dom_test div').bar();
      res.should.eql [2, 1]
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1, 4, 3]
    end

    it 'internal to namespace, will look up functions in namespace before in base, even in closure'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);},
        bar: function(){res.push(2); this.foo();}
      })
      $('#a').entwine('bar', function($){return{
        foo: function(){res.push(3);},
        bar: function(){res.push(4); $('#a').each(function(){ $(this).foo(); })}
      }})
      
      $('#dom_test div').bar();
      res.should.eql [2, 1]
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1, 4, 3]
    end

    it 'internal to namespace, will look up functions in namespace before in base, even in onmatch'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);},
        bar: function(){res.push(2); this.foo();}
      })
      $('#a').entwine('bar', function($){return{
        foo: function(){res.push(3);}
      }})
      $('#a.d').entwine('bar', function($){return{
        onmatch: function(){res.push(4); this.foo();}
      }})
      
      $('#dom_test div').bar();
      res.should.eql [2, 1]
      
      $('#a').addClass('d');
      res.should.eql [2, 1, 4, 3]
    end
    
    it 'internal to namespace, will look up functions in base when not present in namespace'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);}
      })
      $('#a').entwine('bar', function($){return{
        bar: function(){res.push(2); this.foo();}
      }})
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1]
    end
    
    it 'internal to namespace, will not look up functions in base if present in namespace, even when not applicable to selector'
      var res = []
      $('#a').entwine('bar', function($){return{
        foo: function(){this.bar();}
      }})
      $('#a').entwine({
        bar: function(){res.push(1);}
      })
      $('span').entwine('bar', function($){return{
        bar: function(){res.push(2);}
      }})
      
      $('#a').entwine('bar').foo()
      res.should.eql []
    end
    
    it 'internal to namespace, can be directed to base namespace'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);},
        bar: function(){res.push(2); this.foo();}
      })
      $('#a').entwine('bar', function($){return{
        foo: function(){res.push(3);},
        bar: function(){res.push(4); this.foo(); this.entwine('.').foo();}
      }})
      $('#dom_test div').bar();
      res.should.eql [2, 1]
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1, 4, 3, 1]
    end
    
    it 'internal to namespace, will look up functions in namespace called the same as a regular jQuery base function'
      var res = []
      $('#a').entwine('bar', function($){return{
        load: function(){res.push(1);},
        bar: function(){res.push(2); this.load();}
      }})
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1]
    end

    it 'internal to namespace, can be directed to regular jQuery base function'
      var res = []
      $.fn.testy = function(){ res.push(1); }
      $('#a').entwine('bar', function($){return{
        testy: function(){res.push(3);},
        bar: function(){res.push(2); this.entwine('.').testy();}
      }})
      $('#dom_test div').entwine('bar').bar();
      res.should.eql [2, 1]
    end
    
    it 'internal to namespace, can be directed to sub namespace'
      var res = []
      $.entwine('zap', function($){
        $('#a').entwine({
          foo: function(){ res.push(1); this.entwine('pow').bar(); }   
        })
         
        $.entwine('pow', function($){
          $('#a').entwine({
             bar: function(){ res.push(2); }
          })   
        })
      })
      $('#dom_test div').entwine('zap').foo();
      res.should.eql [1, 2]
    end

    it 'internal to namespace, can be directed to unrelated namespace'
      var res = []
      $.entwine('zap', function($){
        $('#a').entwine({
          foo: function(){ res.push(1); this.entwine('.pow').bar(); }   
        })
         
        $.entwine('pow', function($){
          $('#a').entwine({
            bar: function(){ res.push(2); }
          })   
        })
      })
      $.entwine('pow', function($){
        $('#a').entwine({
          bar: function(){ res.push(3); }
        })
      })
      
      $('#dom_test div').entwine('zap').foo();
      res.should.eql [1, 3]
    end

    it 'a function passed out of a namespace will remember its namespace'
      var res = []
      var func = function(func) {
        func.call($('#a, #b'));
      }
      $('#a, #b').entwine('bar', function($){return{
        zap: function(){res.push($(this).attr('id'));},
        bar: function(){res.push(2); func(this.zap);}
      }})
      $('#dom_test #a').entwine('bar').bar();
      res.should.eql [2, 'b', 'a']
    end

    it 'using block functions'
      var res = []
      $('#a').entwine({
        foo: function(){res.push(1);}
      })
      $('#a').entwine('bar', function($){return{
        foo: function(){res.push(3);}
      }})
      
      $('#dom_test div').foo();
      res.should.eql [1]
      
      $('#dom_test div').entwine('bar', function($){ 
         $(this).foo();
      })
      res.should.eql [1, 3]
    end
    
  end
end
