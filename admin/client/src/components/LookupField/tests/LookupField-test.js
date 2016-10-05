/* global jest, describe, beforeEach, it, expect, require */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../LookupField');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import { LookupField } from '../LookupField';

describe('LookupField', () => {
  let props = null;
  let field = null;

  beforeEach(() => {
    // Set up some mocked out file info before each test
    props = {
      id: 'set',
      name: 'set',
      value: 'two',
      source: [
        { value: 'one', title: '1' },
        { value: 'two', title: '2' },
        { value: 'three', title: '3' },
        { value: 'four', title: '4' },
      ],
    };
  });

  describe('getValueCSV()', () => {
    it('should return an empty string', () => {
      props.value = [];

      field = ReactTestUtils.renderIntoDocument(
        <LookupField {...props} />
      );
      const value = field.getValueCSV();

      expect(value).toEqual('');
    });

    it('should return the string value', () => {
      field = ReactTestUtils.renderIntoDocument(
        <LookupField {...props} />
      );
      const value = field.getValueCSV();

      expect(value).toEqual('2');
    });

    it('should return the string value', () => {
      props.value = ['two', 'three'];

      field = ReactTestUtils.renderIntoDocument(
        <LookupField {...props} />
      );
      const value = field.getValueCSV();

      expect(value).toEqual('2, 3');
    });
  });
});
