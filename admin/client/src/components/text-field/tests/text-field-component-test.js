/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import TextFieldComponent from '../';

describe('TextFieldComponent', () => {
  let props;

  beforeEach(() => {
    props = {
      label: '',
      name: '',
      value: '',
      handleFieldUpdate: jest.genMockFunction(),
    };
  });

  describe('handleChange()', () => {
    let textField;

    beforeEach(() => {
      textField = ReactTestUtils.renderIntoDocument(
        <TextFieldComponent {...props} />
      );
    });

    it('should call the handleFieldUpdate function on props', () => {
      textField.handleChange({ target: { value: '' } });

      expect(textField.props.handleFieldUpdate).toBeCalled();
    });
  });
});
