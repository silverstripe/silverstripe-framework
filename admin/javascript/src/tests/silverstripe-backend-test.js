/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */


jest.unmock('isomorphic-fetch');
jest.unmock('../silverstripe-backend');
jest.unmock('qs');

import backend from '../silverstripe-backend';

/**
 * Return a mock function that returns a promise
 */
function getMockPromise(data) {
  const mock = jest.genMockFunction();
  mock.mockImplementation(() => Promise.resolve(data));
  return mock;
}

describe('SilverStripeBackend', () => {
  beforeEach(() => {
    backend.fetch = getMockPromise({
      status: 200,
      statusText: 'OK',
    });
  });

  describe('get()', () => {
    it('should return a promise', () => {
      const promise = backend.get('http://example.com');
      expect(typeof promise).toBe('object');
    });

    it('should send a GET request to an endpoint', () => {
      backend.get('http://example.com');
      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        { method: 'get', credentials: 'same-origin' }
      );
    });
  });

  describe('post()', () => {
    it('should return a promise', () => {
      const promise = backend.get('http://example.com/item');
      expect(typeof promise).toBe('object');
    });

    it('should send a POST request to an endpoint', () => {
      const postData = { id: 1 };

      backend.post('http://example.com', postData);

      expect(backend.fetch).toBeCalled();

      expect(backend.fetch.mock.calls[0][0]).toEqual('http://example.com');
      expect(backend.fetch.mock.calls[0][1]).toEqual(jasmine.objectContaining({
        method: 'post',
        body: postData,
        credentials: 'same-origin',
      }));
    });
  });

  describe('put()', () => {
    it('should return a promise', () => {
      const promise = backend.get('http://example.com/item');
      expect(typeof promise).toBe('object');
    });

    it('should send a PUT request to an endpoint', () => {
      const putData = { id: 1 };

      backend.put('http://example.com', putData);

      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        { method: 'put', body: putData, credentials: 'same-origin' }
      );
    });
  });

  describe('delete()', () => {
    it('should return a promise', () => {
      const promise = backend.get('http://example.com/item');
      expect(typeof promise).toBe('object');
    });

    it('should send a DELETE request to an endpoint', () => {
      const deleteData = { id: 1 };

      backend.delete('http://example.com', deleteData);

      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        { method: 'delete', body: deleteData, credentials: 'same-origin' }
      );
    });
  });

  describe('createEndpointFetcher()', () => {
    // Mock out the get/post/put/delete methods in the backend
    // So that we can isolate our test to the behaviour of createEndpointFetcher()
    // The mocked getters will pass returnValue to the resulting promise's then() call
    function getBackendMock(returnValue) {
      return Object.assign(backend, {
        get: getMockPromise(returnValue),
        post: getMockPromise(returnValue),
        put: getMockPromise(returnValue),
        delete: getMockPromise(returnValue),
      });
    }

    it('should add querystring to the URL with payloadFormat=querystring', () => {
      const mock = getBackendMock({
        text: () => Promise.resolve('{"status":"ok","message":"happy"}'),
        headers: new Headers({
          'Content-Type': 'application/json',
        }),
      });
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.org',
        method: 'get',
        payloadFormat: 'querystring',
        responseFormat: 'json',
      });

      endpoint({ id: 1, values: { a: 'aye', b: 'bee' } });

      expect(mock.get).toBeCalledWith(
        'http://example.org?id=1&values%5Ba%5D=aye&values%5Bb%5D=bee',
        null,
        {
          Accept: 'application/json',
        }
      );
    });

    pit('should pass a JSON payload', () => {
      const mock = getBackendMock({
        text: () => Promise.resolve('{"status":"ok","message":"happy"}'),
        headers: new Headers({
          'Content-Type': 'application/json',
        }),
      });
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.org',
        method: 'get',
        payloadFormat: 'json',
        responseFormat: 'json',
      });

      const promise = endpoint({ id: 1, values: { a: 'aye', b: 'bee' } });
      expect(mock.get).toBeCalledWith(
        'http://example.org',
        '{"id":1,"values":{"a":"aye","b":"bee"}}',
        {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        }
      );

      return promise.then((result) => {
        expect(result).toEqual({ status: 'ok', message: 'happy' });
      });
    });
  });
});
