jest.mock('isomorphic-fetch');
jest.unmock('../silverstripe-backend');

import fetch from 'isomorphic-fetch';
import backend from '../silverstripe-backend';

describe('SilverStripeBackend', () => {

    describe('get()', () => {

        it('should return a promise', () => {
            var promise = backend.get('http://example.com');

            expect(typeof promise).toBe('object');
            expect(typeof promise.done).toBe('function');
            expect(typeof promise.fail).toBe('function');
            expect(typeof promise.always).toBe('function');
        });

        it('should send a GET request to an endpoint', () => {
            backend.get('http://example.com');

            expect(fetch).toBeCalledWith({
                type: 'GET',
                url: 'http://example.com'
            });
        });

    });

    describe('post()', () => {

        it('should return a promise', () => {
            var promise = backend.get('http://example.com/item');

            expect(typeof promise).toBe('object');
            expect(typeof promise.done).toBe('function');
            expect(typeof promise.fail).toBe('function');
            expect(typeof promise.always).toBe('function');
        });

        it('should send a POST request to an endpoint', () => {
            const postData = { id: 1 };

            backend.post('http://example.com', postData);

            expect(fetch).toBeCalledWith({
                type: 'POST',
                url: 'http://example.com',
                data: postData
            });
        });

    });

    describe('put()', () => {

        it('should return a promise', () => {
            var promise = backend.get('http://example.com/item');

            expect(typeof promise).toBe('object');
            expect(typeof promise.done).toBe('function');
            expect(typeof promise.fail).toBe('function');
            expect(typeof promise.always).toBe('function');
        });

        it('should send a PUT request to an endpoint', () => {
            const putData = { id: 1 };

            backend.put('http://example.com', putData);

            expect(fetch).toBeCalledWith({
                type: 'PUT',
                url: 'http://example.com',
                data: putData
            });
        });

    });

    describe('delete()', () => {

        it('should return a promise', () => {
            var promise = backend.get('http://example.com/item');

            expect(typeof promise).toBe('object');
            expect(typeof promise.done).toBe('function');
            expect(typeof promise.fail).toBe('function');
            expect(typeof promise.always).toBe('function');
        });

        it('should send a DELETE request to an endpoint', () => {
            const deleteData = { id: 1 };

            backend.delete('http://example.com', deleteData);

            expect(fetch).toBeCalledWith({
                type: 'DELETE',
                url: 'http://example.com',
                data: deleteData
            });
        });

    });

});
