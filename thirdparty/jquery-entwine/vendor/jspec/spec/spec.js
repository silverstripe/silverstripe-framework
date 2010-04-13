
describe 'Failing specs'

  it 'should fail'
    spec = mock_it(function(){
      'test'.should.not.eql 'test'
    })
    spec.should.have_failure_message("expected 'test' to not eql 'test'")
  end

  it 'should fail with one faulty assertion'
    spec = mock_it(function() {
      'test'.should.equal 'test' 
      'test'.should.equal 'foo'
    })
    spec.should.have_failure_message("expected 'test' to be 'foo'")
  end
  
  it 'should fail and print array with square braces'
    spec = mock_it(function() {
      [1,2].should.equal [1,3]
    })
    spec.should.have_failure_message("expected [ 1, 2 ] to be [ 1, 3 ]")
  end
  
  it 'should fail and print nested array'
    spec = mock_it(function() {
      [1, ['foo']].should.equal [1, ['bar', ['whatever', 1.0, { foo : 'bar', bar : { 1 : 2 } }]]]
    })
    spec.should.have_failure_message(/^expected \[\s*1,\s*\[\s*'foo'/)
  end
  
  it 'should fail with selector for jQuery objects'
    spec = mock_it(function() {
      elem = { jquery : '1.3.1', selector : '.foobar' } 
      elem.should.eql 'foo'  
    })
    spec.should.have_failure_message("expected selector '.foobar' to eql 'foo'")
  end
  
  it 'should fail with negated message'
    spec = mock_it(function(){
      '1'.should.not.be_true
    })
    spec.should.have_failure_message(/expected '1' to not be true/)
  end
  
  it 'should fail with positive message'
    spec = mock_it(function() {
      false.should.be_true
    })
    spec.should.have_failure_message(/expected false to be true/)
  end
  
  it 'should fail saying which error has been thrown'
    spec = mock_it(function() {
      -{ throw 'foo' }.should.throw_error 'bar'
    })
    spec.should.have_failure_message("expected exception of 'bar' to be thrown, but got 'foo'")
  end
  
  it 'should fail saying no error was thrown'
    spec = mock_it(function() {
      -{ }.should.throw_error 'foo'
    })
    spec.should.have_failure_message("expected exception of 'foo' to be thrown, but nothing was")
  end
  
  it 'should fail saying no error matching was thrown'
    spec = mock_it(function() {
      -{ throw 'bar' }.should.throw_error(/foo/)
    })
    spec.should.have_failure_message("expected exception matching /foo/ to be thrown, but got 'bar'")
  end
  
  it 'should fail saying constructors'
    spec = mock_it(function() {
      -{ throw new TypeError('oh no') }.should.throw_error(Error)
    })
    spec.should.have_failure_message("expected Error to be thrown, but got TypeError: oh no")
  end
  
  it 'should fail saying multiple arg messages'
    spec = mock_it(function() {
      -{ throw new TypeError('oh no') }.should.throw_error(TypeError, /foo/)
    })
    spec.should.have_failure_message("expected TypeError and exception matching /foo/ to be thrown, but got TypeError: oh no")
  end
  
  it 'should fail with constructor name'
    spec = mock_it(function() {
      function Foo(){}
      function Bar(){}
      Bar.prototype.toString = function(){ return 'Bar: oh no' }
      -{ throw new Bar }.should.throw_error Foo
    })
    spec.should.have_failure_message("expected Foo to be thrown, but got Bar: oh no")
  end
  
  it 'should fail with constructor name'
    spec = mock_it(function() {
      function Foo(){ this.toString = function(){ return '<Foo>' }}
      foo = new Foo
      foo.should.not.be_an_instance_of Foo
    })
    spec.should.have_failure_message("expected <Foo> to not be an instance of Foo")
  end
  
  it 'should fail with message of first failure'
    spec = mock_it(function() {
      true.should.be_true
      'bar'.should.match(/foo/gm)
      'bar'.should.include 'foo'
    })
    spec.should.have_failure_message("expected 'bar' to match /foo/gm")
  end
  
  it 'should fail with list'
    spec = mock_it(function() {
      ['foo', 'bar'].should.include 'foo', 'car'
    })
    spec.should.have_failure_message("expected [ 'foo', 'bar' ] to include 'foo', 'car'")
  end
  
  it 'should catch exceptions throw within specs'
    spec = mock_it(function() {
      throw new Error('Oh noes!')
    })
    spec.should.have_failure_message(/Error: Oh noes!/)
  end
  
  it 'should catch exceptions without constructors'
    spec = mock_it(function() {
      throw 'oh noes'
    })
    spec.should.have_failure_message(/oh noes/)
  end
  
  it 'should catch indirect exceptions'
    spec = mock_it(function() {
      iDoNotExist.neitherDoI()
    })
    spec.should.have_failure_message(/iDoNotExist/)
  end
  
end

describe 'Contexts'
  before 
    JSpec.context = { iLike : 'cookies' }
  end

  after
    JSpec.context = null
  end

  it 'should be replaceable'
    iLike.should.equal 'cookies'
  end
end

describe 'Misc'
  it 'requires implementation'
  end
end

