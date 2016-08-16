/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('components/FieldHolder/FieldHolder');
jest.unmock('../TextField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import TextField from '../TextField';

describe('TextField', () => {
  let props;

  beforeEach(() => {
    props = {
      title: '',
      name: '',
      value: '',
      onChange: jest.genMockFunction(),
    };
  });

  describe('onChange()', () => {
    let textField;
    let inputField;

    beforeEach(() => {
      textField = ReactTestUtils.renderIntoDocument(
        <TextField {...props} />
      );
      inputField = ReactTestUtils.findRenderedDOMComponentWithTag(textField, 'input');
    });

    it('should call the onChange function on props', () => {
      ReactTestUtils.Simulate.change(inputField);
      expect(textField.props.onChange).toBeCalled();
    });
  });
});
