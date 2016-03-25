jest.dontMock('../index');
jest.dontMock('../table');

const React = require('react'),
    ReactTestUtils = require('react-addons-test-utils'),
    GridFieldTableComponent = require('../table.js').default;

describe('GridFieldTableComponent', () => {
    var props;

    beforeEach(function () {
        props = {
        }
    });

    describe('generateHeader()', function () {
        var gridfield;

        it('should return props.header if it is set', function () {
            props.header = <div className='header'></div>;

            gridfield = ReactTestUtils.renderIntoDocument(
                <GridFieldTableComponent {...props} />
            );

            expect(gridfield.generateHeader().props.className).toBe('header');
        });

        it('should generate and return a header from props.data if it is set', function () {

        });

        it('should return null if props.header and props.data are both not set', function () {
            gridfield = ReactTestUtils.renderIntoDocument(
                <GridFieldTableComponent {...props} />
            );

            expect(gridfield.generateHeader()).toBe(null);
        });
    });

    describe('generateRows()', function () {
        var gridfield;

        it('should return props.rows if it is set', function () {
            props.rows = ['row1'];

            gridfield = ReactTestUtils.renderIntoDocument(
                <GridFieldTableComponent {...props} />
            );

            expect(gridfield.generateRows()[0]).toBe('row1');
        });

        it('should generate and return rows from props.data if it is set', function () {

        });

        it('should return null if props.rows and props.data are both not set', function () {
            gridfield = ReactTestUtils.renderIntoDocument(
                <GridFieldTableComponent {...props} />
            );

            expect(gridfield.generateRows()).toBe(null);
        });
    });
});
