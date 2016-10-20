/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../CheckboxSetField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
// get non-default because it uses FieldHolder by default
import { CheckboxSetField } from '../CheckboxSetField';

describe('CheckboxSetField', () => {
  let props = null;

  beforeEach(() => {
    props = {
      id: 'checkbox',
      title: '',
      name: 'checkbox',
      value: '',
      source: [
        { value: 'one', title: '1' },
        { value: 'two', title: '2' },
        { value: 'three', title: '3' },
        { value: 'four', title: '4' },
      ],
      onChange: jest.genMockFunction(),
    };
  });

  describe('getValues()', () => {
    let checkboxSetField = null;

    it('should convert string value to array', () => {
      props.value = 'abc';
      checkboxSetField = ReactTestUtils.renderIntoDocument(
        <CheckboxSetField {...props} />
      );

      expect(checkboxSetField.getValues()).toEqual(['abc']);
    });

    it('should convert number value to array string', () => {
      props.value = 123;
      checkboxSetField = ReactTestUtils.renderIntoDocument(
        <CheckboxSetField {...props} />
      );

      expect(checkboxSetField.getValues()).toEqual(['123']);
    });

    it('should convert null value to empty array', () => {
      props.value = null;
      checkboxSetField = ReactTestUtils.renderIntoDocument(
        <CheckboxSetField {...props} />
      );

      expect(checkboxSetField.getValues()).toEqual([]);
    });
  });

  describe('getItemKey()', () => {
    let checkboxSetField = null;
    beforeEach(() => {
      checkboxSetField = ReactTestUtils.renderIntoDocument(
        <CheckboxSetField {...props} />
      );
    });

    it('should generate a key for field', () => {
      const key = checkboxSetField.getItemKey({ value: 'two' });

      expect(key).toEqual('checkbox-two');
    });
  });

  describe('onChange()', () => {
    let checkboxSetField = null;

    beforeEach(() => {
      props.value = ['one', 'four'];
      checkboxSetField = ReactTestUtils.renderIntoDocument(
        <CheckboxSetField {...props} />
      );
    });

    it('should add the selected value', () => {
      const event = new Event('click');

      checkboxSetField.handleChange(event, { id: 'checkbox-two', value: 1 });

      expect(checkboxSetField.props.onChange).toBeCalledWith(
        ['one', 'two', 'four']
      );
    });

    it('should remove the unselected value', () => {
      const event = new Event('click');

      checkboxSetField.handleChange(event, { id: 'checkbox-one', value: 0 });

      expect(checkboxSetField.props.onChange).toBeCalledWith(
        ['four']
      );
    });
  });
});
