describe( 'Entwine', function() {

  beforeEach(function() {
    $('body').append('<div id="dom_test"></div>')
  });
  
  afterEach(function() {
    $('#dom_test').remove()
  });

  describe( 'Ctors', function() {
    
    beforeEach(function() {
      $.entwine.synchronous_mode();
      $.entwine.clear_all_rules()
      $('#dom_test').html('<div id="a" class="a b c"></div>')
    });
    
    it( 'calls onmatch when new element created', function() {
      var a = false;
      $('#b').entwine({onmatch: function(){a = true;} });
      expect(a).toBeFalsy();
      $('#a').after('<div id="b"></div>');  
      expect(a).toBeTruthy();
    });
      
    it( 'calls onunmatch when new element deleted', function() {
      var a = 0;
      $('#b').entwine({onmatch: function(){a = 1;}, onunmatch: function(){a = 2;} });
		  expect(a).toEqual( 0);
      $('#a').after('<div id="b"></div>');
		  expect(a).toEqual( 1);
      $('#b').remove();
		  expect(a).toEqual( 2);
    });
      
    it( 'calls onmatch when ruleset matches after class added', function() {
      var a = 0;
      $('#a.foo').entwine({onmatch: function(){a = 1;} });
      expect(a).toEqual(0);
      $('#a').addClass('foo');
      expect(a).toEqual( 1);
    });

    it( 'calls onmatch in both direct and namespaced onmatch, does not call less specific onmatch', function() {
      var a = 0, b=0, c=0, d=0;
      $('.foo').entwine({onmatch: function(){a = 1;}})
      $('.foo').entwine('bar', function($){return{onmatch: function(){b = 1;}}})
      $('#a.foo').entwine({onmatch: function(){c = 1;}})
      $('#a.foo').entwine('bar', function($){return{onmatch: function(){d = 1}}})
      expect([a, b, c, d]).toEqual( [0, 0, 0, 0]);
      $('#a').addClass('foo');
      expect([a, b, c, d]).toEqual( [0, 0, 1, 1]);
    });
      
    it( 'calls onmatch in both direct and namespaced onmatch, super works as expected', function() {
      var a = 0, b=0, c=0, d=0;
      $('.foo').entwine({onmatch: function(){a += 1;}})
      $('.foo').entwine('bar', function($){return{onmatch: function(){b += 1;}}})
      $('#a.foo').entwine({onmatch: function(){this._super(); c = 1; this._super();}})
      $('#a.foo').entwine('bar', function($){return{onmatch: function(){this._super(); d = 1; this._super();}}})
      expect([a, b, c, d]).toEqual( [0, 0, 0, 0]);
      $('#a').addClass('foo');
      expect([a, b, c, d]).toEqual( [2, 2, 1, 1]);
    });
    
  });
});