
describe 'Concrete'
  describe 'Properties'
    before
      $('body').append('<div id="dom_test"></div>')
    end
    after
      $('#dom_test').remove()
    end
  
    before_each
      $.concrete.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c"></div><div id="b" class="b c"></div>')
    end

    it 'can define and get a basic property'
      $('#a').concrete({
        Foo: null
      });
      $('.a').getFoo().should.be_null
    end

    it 'can define and set a basic property'
      $('#a').concrete({
        Foo: null
      });
      $('.a').setFoo(1);
      $('.a').getFoo().should.equal 1
    end

    it 'can define a default value'
      $('#a').concrete({
        Foo: 1
      });
      $('.a').getFoo().should.equal 1
    end

    it 'should manage proprties in namespaces without clashing'
      $('#a').concrete({
        Foo: 1
      });

      $.concrete('test', function($){
        $('#a').concrete({
          Foo: 2
        });
      });

      $('.a').getFoo().should.equal 1
      $('.a').concrete('test').getFoo().should.equal 2

      $('.a').setFoo(4);
      $('.a').concrete('test').setFoo(8);

      $('.a').getFoo().should.equal 4
      $('.a').concrete('test').getFoo().should.equal 8
    end

    it 'should manage directly setting proprties in namespaces without clashing'
      $('#a').concrete({
        Foo: null
      });

      $.concrete('test', function($){
        $('#a').concrete({
          Foo: null
        });
      });

      $('.a').concreteData('Foo', 4);
      $('.a').concrete('test').concreteData('Foo', 8);

      $('.a').concreteData('Foo').should.equal 4
      $('.a').concrete('test').concreteData('Foo').should.equal 8
    end
    
    describe 'jQuery style accessors'
      it 'can define and get a basic property'
        $('#a').concrete({
          Foo: null
        });
        $('.a').Foo().should.be_null
      end
     
      it 'can define and set a basic property'
        $('#a').concrete({
          Foo: null
        });
        $('.a').Foo(1);
        $('.a').Foo().should.equal 1
      end

      it 'can define a default value'
        $('#a').concrete({
          Foo: 1
        });
        $('.a').Foo().should.equal 1
      end
    end

  end
end
