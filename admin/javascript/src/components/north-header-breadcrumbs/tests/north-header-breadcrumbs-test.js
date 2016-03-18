jest.dontMock('../index');

const React = require('react'),
    ReactTestUtils = require('react-addons-test-utils'),
    NorthHeaderBreadcrumbsComponent = require('../index').default;

describe('NorthHeaderBreadcrumbsComponent', () => {
    var props;

    beforeEach(() => {
        props = {};
    });

    describe('getBreadcrumbs()', () => {
        var northHeaderBreadcrumbs;

        it('should convert the props.crumbs array into jsx to be rendered', () => {
            props.crumbs = [
                { text: 'breadcrumb1', href: 'href1'},
                { text: 'breadcrumb2', href: 'href2'}
            ];

            northHeaderBreadcrumbs = ReactTestUtils.renderIntoDocument(
                <NorthHeaderBreadcrumbsComponent {...props} />
            );

            expect(northHeaderBreadcrumbs.getBreadcrumbs()[0][0].props.children).toBe('breadcrumb1');
            expect(northHeaderBreadcrumbs.getBreadcrumbs()[0][1].props.children).toBe('/');
            expect(northHeaderBreadcrumbs.getBreadcrumbs()[1].props.children).toBe('breadcrumb2');
        });

        it('should return null if props.crumbs is not set', () => {
            northHeaderBreadcrumbs = ReactTestUtils.renderIntoDocument(
                <NorthHeaderBreadcrumbsComponent {...props} />
            );

            expect(northHeaderBreadcrumbs.getBreadcrumbs()).toBe(null);
        });
    });
});
