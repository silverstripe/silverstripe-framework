/* global jest, describe, beforeEach, it, expect */

jest.dontMock('../index');

// FYI: Changing these to import statements broke jest's automocking
const React = require('react');
const ReactTestUtils = require('react-addons-test-utils');
const NorthHeaderBreadcrumbsComponent = require('../index').default;

describe('NorthHeaderBreadcrumbsComponent', () => {
  let props;

  beforeEach(() => {
    props = {};
  });

  describe('getBreadcrumbs()', () => {
    let northHeaderBreadcrumbs;

    it('should convert the props.crumbs array into jsx to be rendered', () => {
      props.crumbs = [
        { text: 'breadcrumb1', href: 'href1' },
        { text: 'breadcrumb2', href: 'href2' },
        { text: 'breadcrumb3', href: 'href3' },
      ];

      northHeaderBreadcrumbs = ReactTestUtils.renderIntoDocument(
        <NorthHeaderBreadcrumbsComponent {...props} />
      );
      expect(northHeaderBreadcrumbs.getBreadcrumbs()[0][0].props.children).toBe('breadcrumb1');
      expect(northHeaderBreadcrumbs.getBreadcrumbs()[0][1].props.children).toBe('/');
      expect(northHeaderBreadcrumbs.getBreadcrumbs()[1][0].props.children).toBe('breadcrumb2');
      expect(northHeaderBreadcrumbs.getBreadcrumbs()[1][1].props.children).toBe('/');
      expect(northHeaderBreadcrumbs.getBreadcrumbs()[2][0].props.children).toBe('breadcrumb3');
    });

    it('should return null if props.crumbs is not set', () => {
      northHeaderBreadcrumbs = ReactTestUtils.renderIntoDocument(
        <NorthHeaderBreadcrumbsComponent {...props} />
      );

      expect(northHeaderBreadcrumbs.getBreadcrumbs()).toBe(null);
    });
  });
});
