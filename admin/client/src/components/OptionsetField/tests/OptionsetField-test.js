/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../OptionsetField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import { OptionsetField } from '../OptionsetField';

describe('OptionsetField', () => {
  let props = null;
  let setField = null;

  beforeEach(() => {
    props = {
      id: 'set',
      title: '',
      name: 'set',
      value: 'two',
      source: [
        { value: 'one', title: '1' },
        { value: 'two', title: '2' },
        { value: 'three', title: '3' },
        { value: 'four', title: '4' },
      ],
      onChange: jest.genMockFunction(),
    };

    setField = ReactTestUtils.renderIntoDocument(
      <OptionsetField {...props} />
    );
  });

  describe('getItemKey()', () => {
    it('should generate a key for field', () => {
      const key = setField.getItemKey({ value: 'two' });

      expect(key).toEqual('set-two');
    });
  });

  describe('onChange()', () => {
    it('should set the selected value', () => {
      const event = new Event('click');

      setField.handleChange(event, { id: 'set-one', value: 1 });

      expect(setField.props.onChange).toBeCalledWith(
        'one'
      );
    });
  });
});
