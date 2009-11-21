
describe 'Utility'
  describe 'fixture()'
    it 'should return a files contents'
      fixture('fixtures/test.html').should.eql '<p>test</p>'
      fixture('test.html').should.eql '<p>test</p>'
      fixture('test').should.eql '<p>test</p>'
    end
    
    it 'should cache contents'
      contents = fixture('test')
      JSpec.cache['test'].should.eql contents
      JSpec.cache['test'] = 'foo'
      fixture('test').should.eql 'foo'
      delete JSpec.cache['test']
    end
  end
end