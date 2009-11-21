
describe 'jQuery'
  describe '.ajax()'
    it "should call the success function when 200"
      mock_request().and_return('{ foo: "bar" }', 'application/json')
      var successCalled = false
      $.ajax({
        type: "POST",
        url: 'foo',
        dataType: 'json',
        success: function() {
          successCalled = true
        }
      })
      successCalled.should.be_true
    end
  
    it "should call the error function when 404"
      mock_request().and_return('{ foo: "bar" }', 'application/json', 404)
      var errorCalled = false
      $.ajax({
        type: "POST",
        url: 'foo',
        dataType: 'json',
        error: function() {
          errorCalled = true
        }
      })
      errorCalled.should.be_true
    end
  end

  describe '.getJSON()'
    it 'should work with mockRequest'
      mockRequest().and_return('{ foo : "bar" }')
      $.getJSON('foo', function(response, statusText){
        response.foo.should.eql 'bar'
        statusText.should.eql 'success'
      })
    end
    
    it 'should work with a json fixture'
      mockRequest().and_return(fixture('test.json'))
      $.getJSON('foo', function(response){
        response.users.tj.email.should.eql 'tj@vision-media.ca'
      })
    end
    
    it 'should not invoke callback when response status is 4xx'
      mockRequest().and_return('foo', 'text/plain', 404)
      $.getJSON('foo', function(){
        fail('callback was invoked')
      })
    end
  end
  
  describe '.post()'
    it 'should work with mockRequest'
      mockRequest().and_return('<p></p>', 'text/html')
      $.post('foo', function(response){
        response.should.eql '<p></p>'
      })
    end
  end
end