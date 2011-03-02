describe('jasmine-dom-fixtures', function(){
  it('should load the fixture without errors', function() {
    loadFixtures('fixture.html');
    var div = document.getElementById('testdiv');
    expect(div).toHaveId('testdiv');
  });
});
