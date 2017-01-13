/* global jest, describe, beforeEach, it, expect */

jest.unmock('../Breadcrumb');

// FYI: Changing these to import statements broke jest's automocking
import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import { Breadcrumb } from '../Breadcrumb';

describe('BreadcrumbsComponent', () => {
  let props = null;

  beforeEach(() => {
    props = {};
  });

  describe('renderBreadcrumbs()', () => {
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
        <Breadcrumb {...props} />
      );
      const listEls = breadcrumbs.renderBreadcrumbs();
      expect(listEls[0].props.children.props.children).toBe('breadcrumb1');
      expect(listEls[1].props.children.props.children).toBe('breadcrumb2');
      expect(listEls[2].props.children).toBe(undefined);

      const lastEl = breadcrumbs.renderLastCrumb();
      expect(lastEl.props.children.props.children[0]).toBe('breadcrumb3');
      expect(lastEl.props.children.props.children[1].props.className.split(' '))
        .toContain('breadcrumb3icon');
    });

    it('should return null if props.crumbs is not set', () => {
      breadcrumbs = ReactTestUtils.renderIntoDocument(
        <Breadcrumb {...props} />
      );

      const listEls = breadcrumbs.renderBreadcrumbs();
      expect(listEls).toBe(null);
    });
  });
});
