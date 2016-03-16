function jQuery() {
    return {
        // Add jQuery methods such as 'find', 'change', 'trigger' as needed.
    };
}

var mockAjaxFn = jest.genMockFunction();

mockAjaxFn.mockReturnValue({
    done: jest.genMockFunction(),
    fail: jest.genMockFunction(),
    always: jest.genMockFunction()
});

jQuery.ajax = mockAjaxFn;

export default jQuery;
