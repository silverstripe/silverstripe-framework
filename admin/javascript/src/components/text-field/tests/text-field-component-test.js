jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import TextFieldComponent from '../';

describe('TextFieldComponent', function() {

    var props;

    beforeEach(function () {
        props = {
            label: '',
            name: '',
            value: '',
            onChange: jest.genMockFunction()
        };
    });

    describe('handleChange()', function () {
        var textField;

        beforeEach(function () {
            textField = ReactTestUtils.renderIntoDocument(
                <TextFieldComponent {...props} />
            );
        });

        it('should call the onChange function on props', function () {
            textField.handleChange();

            expect(textField.props.onChange.mock.calls.length).toBe(1);
        });
    });
});
