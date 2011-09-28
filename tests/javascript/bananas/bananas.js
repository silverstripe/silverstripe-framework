Banana = {
	getAmount: function() {
		return 2;
	},
	getColor: function() {
		return 'brown';
	}
}
describe("The banana", function() {
	it("should have two left", function() {
		expect(Banana.getAmount()).toEqual(2);
	});
	it("should be yellow", function() {
		expect(Banana.getColor()).toEqual('yellow');
	});
});