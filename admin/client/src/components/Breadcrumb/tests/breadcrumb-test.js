/* global jest, describe, beforeEach, it, expect */

jest.dontMock('../Breadcrumb');

// FYI: Changing these to import statements broke jest's automocking
const React = require('react');
const ReactTestUtils = require('react-addons-test-utils');
const BreadcrumbsComponent = require('../Breadcrumb').default;

describe('BreadcrumbsComponent', () => {
  let props = null;

  beforeEach(() => {
    props = {};
  });

  describe('getBreadcrumbs()', () => {
    let breadcrumbs = null;

    it('should convert the props.crumbs array into jsx to be rendered', () => {
      props.crumbs = [
        { text: 'breadcrumb1', href: 'href1' },
        { text: 'breadcrumb2', href: 'href2' },
        { text: 'breadcrumb3', href: 'href3',
          icon: {
            className: 'breadcrumb3icon',
            action: jest.genMockFunction(),
          },
        },
      ];

      breadcrumbs = ReactTestUtils.renderIntoDocument(
        <BreadcrumbsComponent {...props} />
      );
      const listEls = breadcrumbs.getBreadcrumbs();
      expect(listEls[0][0].props.children.props.children).toBe('breadcrumb1');
      expect(listEls[1][0].props.children.props.children).toBe('breadcrumb2');
      expect(listEls[2][0].props.children.props.children[0]).toBe('breadcrumb3');
      expect(listEls[2][0].props.children.props.children[1].props.className.split(' '))
        .toContain('breadcrumb3icon');
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
