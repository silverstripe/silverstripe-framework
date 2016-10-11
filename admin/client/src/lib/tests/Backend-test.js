/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */


jest.unmock('isomorphic-fetch');
jest.unmock('../Backend');
jest.unmock('qs');
jest.unmock('merge');

import backend from '../Backend';

/**
 * Return a mock function that returns a promise
 */
function getMockPromise(data) {
  const mock = jest.genMockFunction();
  mock.mockImplementation(() => Promise.resolve(data));
  return mock;
}

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

describe('Backend', () => {
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
        {
          method: 'get',
          credentials: 'same-origin',
          headers: {},
        }
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

      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        {
          method: 'post',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: JSON.stringify(postData),
        }
      );
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
        {
          method: 'put',
          credentials: 'same-origin',
          headers: {},
          body: JSON.stringify(putData),
        }
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
        {
          method: 'delete',
          credentials: 'same-origin',
          headers: {},
          body: JSON.stringify(deleteData),
        }
      );
    });
  });

  describe('createEndpointFetcher()', () => {
    let mock = null;

    beforeEach(() => {
      mock = getBackendMock({
        // mock response body and headers
        text: () => Promise.resolve('{"status":"ok"}'),
        headers: {
          get: () => 'application/json',
        },
      });
    });

    it('should add querystring to the URL for GET requests', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.org',
        method: 'get',
        responseFormat: 'json',
      });

      const promise = endpoint({ id: 1, values: { a: 'aye', b: 'bee' } });

      expect(mock.get)
        .toBeCalledWith('http://example.org?id=1&values%5Ba%5D=aye&values%5Bb%5D=bee', {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
        });

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });

    it('should pass a JSON payload', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.org',
        method: 'post',
        payloadFormat: 'json',
        responseFormat: 'json',
      });

      const promise = endpoint({ id: 1, values: { a: 'aye', b: 'bee' } });

      expect(mock.post)
        .toBeCalledWith(
          'http://example.org',
          '{"id":1,"values":{"a":"aye","b":"bee"}}', {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          }
        );

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });

    it('should replace url template parameters', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.com/:one/:two/?foo=bar',
        method: 'post',
        payloadSchema: {
          one: { urlReplacement: ':one', remove: true },
          two: { urlReplacement: ':two' },
        },
      });
      const promise = endpoint({
        one: 1,
        two: 2,
        three: 3,
      });

      expect(mock.post)
        .toBeCalledWith(
          'http://example.com/1/2/?foo=bar',
          'two=2&three=3', {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
          }
        );

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });

    it('should add query parameters from spec for non-GET data', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.com/:one/:two/?foo=bar',
        method: 'post',
        payloadFormat: 'json',
        payloadSchema: {
          one: { urlReplacement: ':one', remove: true },
          two: { urlReplacement: ':two' },
          three: { querystring: true },
        },
      });
      const promise = endpoint({
        one: 1,
        two: 2,
        three: 3,
      });

      expect(mock.post)
        .toBeCalledWith(
          'http://example.com/1/2/?foo=bar&three=3',
          '{"two":2}', {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          }
        );

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });

    it('should add query parameters from payload for GET data', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.com/:one/:two/?foo=bar',
        method: 'get',
        payloadSchema: {
          one: { urlReplacement: ':one', remove: true },
          two: { urlReplacement: ':two' },
          three: { querystring: true },
        },
      });
      const promise = endpoint({
        one: 1,
        two: 2,
        three: 3,
      });

      expect(mock.get)
        .toBeCalledWith('http://example.com/1/2/?foo=bar&two=2&three=3', {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
        });

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });

    it('should merge defaultData into data argument', (done) => {
      const endpoint = mock.createEndpointFetcher({
        url: 'http://example.com/',
        method: 'post',
        payloadFormat: 'json',
        defaultData: { one: 1, two: 2, four: { fourOne: true } },
      });
      const promise = endpoint({
        two: 'updated',
        three: 3,
        four: { fourTwo: true },
      });

      expect(mock.post)
        .toBeCalledWith(
          'http://example.com/',
          JSON.stringify({
            one: 1,
            two: 'updated',
            four: { fourOne: true, fourTwo: true },
            three: 3,
          }), {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          }
        );

      return promise
        .catch((e) => expect(e).toBeFalsy())
        .then(done);
    });
  });
});
