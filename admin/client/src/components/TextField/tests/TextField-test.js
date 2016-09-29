/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../TextField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import { TextField } from '../TextField';

describe('TextField', () => {
  let props = null;

  beforeEach(() => {
    props = {
      title: '',
      name: '',
      value: '',
      onChange: jest.genMockFunction(),
    };
  });

  describe('onChange()', () => {
    let textField = null;
    let inputField = null;

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

  describe('isMultiline()', () => {
    let textField = null;

    it('should not be multi-line for empty data', () => {
      textField = ReactTestUtils.renderIntoDocument(
        <TextField {...props} />
      );

      const newProps = textField.getInputProps();

      expect(textField.isMultiline()).toBeFalsy();
      expect(newProps.componentClass).toBe('input');
    });

    it('should be multi-line for three rows', () => {
      props.data = { rows: 3 };
      textField = ReactTestUtils.renderIntoDocument(
        <TextField {...props} />
      );

      const newProps = textField.getInputProps();

      expect(textField.isMultiline()).toBeTruthy();
      expect(newProps.componentClass).toBe('textarea');
    });
  });
});
