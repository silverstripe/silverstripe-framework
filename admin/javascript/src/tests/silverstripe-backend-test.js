jest.mock('jQuery');
jest.unmock('../silverstripe-backend');

import $ from 'jQuery';
import backend from '../silverstripe-backend';

describe('SilverStripeBackend', () => {

    describe('get()', () => {

        it('should return a jqXHR', () => {
            var jqxhr = backend.get('http://example.com');

            expect(typeof jqxhr).toBe('object');
            expect(typeof jqxhr.done).toBe('function');
            expect(typeof jqxhr.fail).toBe('function');
            expect(typeof jqxhr.always).toBe('function');
        });

        it('should send a GET request to an endpoint', () => {
            backend.get('http://example.com');

            expect($.ajax).toBeCalledWith({
                type: 'GET',
                url: 'http://example.com'
            });
        });

    });

    describe('post()', () => {

        it('should return a jqXHR', () => {
            var jqxhr = backend.get('http://example.com/item');

            expect(typeof jqxhr).toBe('object');
            expect(typeof jqxhr.done).toBe('function');
            expect(typeof jqxhr.fail).toBe('function');
            expect(typeof jqxhr.always).toBe('function');
        });

        it('should send a POST request to an endpoint', () => {
            const postData = { id: 1 };

            backend.post('http://example.com', postData);

            expect($.ajax).toBeCalledWith({
                type: 'POST',
                url: 'http://example.com',
                data: postData
            });
        });

    });

    describe('put()', () => {

        it('should return a jqXHR', () => {
            var jqxhr = backend.get('http://example.com/item');

            expect(typeof jqxhr).toBe('object');
            expect(typeof jqxhr.done).toBe('function');
            expect(typeof jqxhr.fail).toBe('function');
            expect(typeof jqxhr.always).toBe('function');
        });

        it('should send a PUT request to an endpoint', () => {
            const putData = { id: 1 };

            backend.put('http://example.com', putData);

            expect($.ajax).toBeCalledWith({
                type: 'PUT',
                url: 'http://example.com',
                data: putData
            });
        });

    });

    describe('delete()', () => {

        it('should return a jqXHR', () => {
            var jqxhr = backend.get('http://example.com/item');

            expect(typeof jqxhr).toBe('object');
            expect(typeof jqxhr.done).toBe('function');
            expect(typeof jqxhr.fail).toBe('function');
            expect(typeof jqxhr.always).toBe('function');
        });

        it('should send a DELETE request to an endpoint', () => {
            const deleteData = { id: 1 };

            backend.delete('http://example.com', deleteData);

            expect($.ajax).toBeCalledWith({
                type: 'DELETE',
                url: 'http://example.com',
                data: deleteData
            });
        });

    });

});
