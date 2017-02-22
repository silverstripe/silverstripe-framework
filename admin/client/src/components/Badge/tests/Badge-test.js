/* global jest, describe, beforeEach, it, expect */

jest.unmock('../Badge');

// FYI: Changing these to import statements broke jest's automocking
import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import Badge from '../Badge';

describe('Badge', () => {
  let props = null;

  beforeEach(() => {
    props = {
      status: null,
      message: '',
      className: '',
    };
  });

  describe('render()', () => {
    let badge = null;

    it('shoudl return null if status is empty', () => {
      badge = ReactTestUtils.renderIntoDocument(
        <Badge {...props} />
      );

      expect(badge).toBe(null);
    });
  });
});
