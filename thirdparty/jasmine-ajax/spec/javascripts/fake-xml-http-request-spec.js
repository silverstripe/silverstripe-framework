describe("FakeXMLHttpRequest", function() {
  var xhr;
  beforeEach(function() {
    xhr = new FakeXMLHttpRequest();
  });
  it("should have an initial readyState of 0 (uninitialized)", function() {
    expect(xhr.readyState).toEqual(0);
  });
  describe("when opened", function() {
    beforeEach(function() {
      xhr.open("GET", "http://example.com")
    });
    it("should have a readyState of 1 (open)", function() {
      expect(xhr.readyState).toEqual(1);
    });

    describe("when sent", function() {
      it("should have a readyState of 2 (sent)", function() {
        xhr.send(null);
        expect(xhr.readyState).toEqual(2);
      });
    });

    describe("when a response comes in", function() {
      it("should have a readyState of 4 (loaded)", function() {
        xhr.response({status: 200});
        expect(xhr.readyState).toEqual(4);
      });
    });

    describe("when aborted", function() {
      it("should have a readyState of 0 (uninitialized)", function() {
        xhr.abort();
        expect(xhr.readyState).toEqual(0);
      });
    });
  });
});
