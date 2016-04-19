/* global jest, describe, beforeEach, it, expect */

jest.dontMock('../index');

// FYI: Changing these to import statements broke jest's automocking
const React = require('react');
const ReactTestUtils = require('react-addons-test-utils');
const BreadcrumbsComponent = require('../index').default;

describe('BreadcrumbsComponent', () => {
  let props;

  beforeEach(() => {
    props = {};
  });

  describe('getBreadcrumbs()', () => {
    let breadcrumbs;

    it('should convert the props.crumbs array into jsx to be rendered', () => {
      props.crumbs = [
        { text: 'breadcrumb1', href: 'href1' },
        { text: 'breadcrumb2', href: 'href2' },
        { text: 'breadcrumb3', href: 'href3' },
      ];

      breadcrumbs = ReactTestUtils.renderIntoDocument(
        <BreadcrumbsComponent {...props} />
      );
      const listEls = breadcrumbs.getBreadcrumbs();
      expect(listEls[0][0].props.children.props.children).toBe('breadcrumb1');
      expect(listEls[1][0].props.children.props.children).toBe('breadcrumb2');
      expect(listEls[2][0].props.children.props.children).toBe('breadcrumb3');
    });

    it('should return null if props.crumbs is not set', () => {
      breadcrumbs = ReactTestUtils.renderIntoDocument(
        <BreadcrumbsComponent {...props} />
      );

      const listEls = breadcrumbs.getBreadcrumbs();
      expect(listEls).toBe(null);
    });
  });
});
