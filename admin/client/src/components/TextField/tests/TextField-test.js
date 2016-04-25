/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../TextField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import TextField from '../TextField';

describe('TextField', () => {
  let props;

  beforeEach(() => {
    props = {
      label: '',
      name: '',
      value: '',
      onChange: jest.genMockFunction(),
    };
  });

  describe('handleChange()', () => {
    let textField;

    beforeEach(() => {
      textField = ReactTestUtils.renderIntoDocument(
        <TextField {...props} />
      );
    });

    it('should call the onChange function on props', () => {
      textField.handleChange();

      expect(textField.props.onChange.mock.calls.length).toBe(1);
    });
  });
});
