
describe 'Entwine'
  describe 'Properties'
    before
      $('body').append('<div id="dom_test"></div>')
    end
    after
      $('#dom_test').remove()
    end
  
    before_each
      $.entwine.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c"></div><div id="b" class="b c"></div>')
    end

    it 'can define and get a basic property'
      $('#a').entwine({
        Foo: null
      });
      $('.a').getFoo().should.be_null
    end

    it 'can define and set a basic property'
      $('#a').entwine({
        Foo: null
      });
      $('.a').setFoo(1);
      $('.a').getFoo().should.equal 1
    end

    it 'can define a default value'
      $('#a').entwine({
        Foo: 1
      });
      $('.a').getFoo().should.equal 1
    end

    it 'should manage proprties in namespaces without clashing'
      $('#a').entwine({
        Foo: 1
      });

      $.entwine('test', function($){
        $('#a').entwine({
          Foo: 2
        });
      });

      $('.a').getFoo().should.equal 1
      $('.a').entwine('test').getFoo().should.equal 2

      $('.a').setFoo(4);
      $('.a').entwine('test').setFoo(8);

      $('.a').getFoo().should.equal 4
      $('.a').entwine('test').getFoo().should.equal 8
    end

    it 'should manage directly setting proprties in namespaces without clashing'
      $('#a').entwine({
        Foo: null
      });

      $.entwine('test', function($){
        $('#a').entwine({
          Foo: null
        });
      });

      $('.a').entwineData('Foo', 4);
      $('.a').entwine('test').entwineData('Foo', 8);

      $('.a').entwineData('Foo').should.equal 4
      $('.a').entwine('test').entwineData('Foo').should.equal 8
    end
    
    describe 'jQuery style accessors'
      it 'can define and get a basic property'
        $('#a').entwine({
          Foo: null
        });
        $('.a').Foo().should.be_null
      end
     
      it 'can define and set a basic property'
        $('#a').entwine({
          Foo: null
        });
        $('.a').Foo(1);
        $('.a').Foo().should.equal 1
      end

      it 'can define a default value'
        $('#a').entwine({
          Foo: 1
        });
        $('.a').Foo().should.equal 1
      end
    end

  end
end
