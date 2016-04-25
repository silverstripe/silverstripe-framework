/* global jest, describe, beforeEach, it, expect */

jest.dontMock('../index');
jest.dontMock('../table');
jest.dontMock('react');

// FYI: Changing these to import statements broke jest's automocking
const React = require('react');
const ReactTestUtils = require('react-addons-test-utils');
const GridFieldTableComponent = require('../table.js').default;

describe('GridFieldTableComponent', () => {
  let props;

  beforeEach(() => {
    props = {
    };
  });

  describe('generateHeader()', () => {
    let gridfield;

    it('should return props.header if it is set', () => {
      props.header = <tr className="header"></tr>;

      gridfield = ReactTestUtils.renderIntoDocument(
        <GridFieldTableComponent {...props} />
      );

      expect(gridfield.generateHeader().props.className).toBe('header');
    });

    it('should generate and return a header from props.data if it is set', () => {

    });

    it('should return null if props.header and props.data are both not set', () => {
      gridfield = ReactTestUtils.renderIntoDocument(
        <GridFieldTableComponent {...props} />
      );

      expect(gridfield.generateHeader()).toBe(null);
    });
  });

  describe('generateRows()', () => {
    let gridfield;

    it('should return props.rows if it is set', () => {
      props.rows = [<tr className="row" key="row1"><td>row1</td></tr>];

      gridfield = ReactTestUtils.renderIntoDocument(
        <GridFieldTableComponent {...props} />
      );

      expect(gridfield.generateRows()[0].props.className).toBe('row');
    });

    it('should generate and return rows from props.data if it is set', () => {

    });

    it('should return null if props.rows and props.data are both not set', () => {
      gridfield = ReactTestUtils.renderIntoDocument(
        <GridFieldTableComponent {...props} />
      );

      expect(gridfield.generateRows()).toBe(null);
    });
  });
});
