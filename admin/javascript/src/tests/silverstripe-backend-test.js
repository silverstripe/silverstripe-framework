jest.mock('isomorphic-fetch');
jest.unmock('../silverstripe-backend');

import fetch from 'isomorphic-fetch';
import backend from '../silverstripe-backend';

var getFetchMock = function(data) {
  let mock = jest.genMockFunction();
  let promise = new Promise((resolve, reject) => {
    process.nextTick(() => resolve(data));
  });
  mock.mockReturnValue(promise);

  return mock;
};

describe('SilverStripeBackend', () => {

  beforeEach(() => {
    let fetchMock = getFetchMock();
    backend.fetch = fetchMock;
  });

  describe('get()', () => {

    it('should return a promise', () => {
      var promise = backend.get('http://example.com');
      expect(typeof promise).toBe('object');
    });

    it('should send a GET request to an endpoint', () => {
      backend.get('http://example.com');
      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        {method: 'get', credentials: 'same-origin'}
      );
    });

  });

  describe('post()', () => {

    it('should return a promise', () => {
      var promise = backend.get('http://example.com/item');
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
      var promise = backend.get('http://example.com/item');
      expect(typeof promise).toBe('object');
    });

    it('should send a PUT request to an endpoint', () => {
      const putData = { id: 1 };

      backend.put('http://example.com', putData);

      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        {method: 'put', body: putData, credentials: 'same-origin'}
      );
    });

  });

  describe('delete()', () => {

    it('should return a promise', () => {
      var promise = backend.get('http://example.com/item');

      expect(typeof promise).toBe('object');
    });

    it('should send a DELETE request to an endpoint', () => {
      const deleteData = { id: 1 };

      backend.delete('http://example.com', deleteData);

      expect(backend.fetch).toBeCalledWith(
        'http://example.com',
        {method: 'delete', body: deleteData, credentials: 'same-origin'}
      );
    });

  });

});
