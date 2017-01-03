/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */


jest.unmock('isomorphic-fetch');
jest.unmock('../DataFormat');
jest.unmock('qs');
jest.unmock('merge');

import { urlQuery, decodeQuery } from '../DataFormat';


describe('DataFormat', () => {

  describe('urlQuery()', () => {
    it('should return empty string when no newQuery is given', () => {
      expect(urlQuery({}, null)).toBe('');
    });
    it('should add new keys', () => {
      expect(urlQuery({ foo: 1 }, { bar: 2 })).toBe('?foo=1&bar=2');
    });
    it('should overwrite existing keys', () => {
      expect(urlQuery({ foo: 1 }, { foo: 2 })).toBe('?foo=2');
    });
    it('should not deep merge nested keys', () => {
      expect(urlQuery({ foo: { bar: 1 } }, { foo: { baz: 2 } })).toBe('?foo%5Bbaz%5D=2');
    });
  });

  describe('decodeQuery', () => {
    it('should decode flat keys', () => {
      expect(decodeQuery('?foo=1')).toEqual({ foo: '1' });
    });
    it('should decode nested keys (PHP style)', () => {
      expect(decodeQuery('?foo[bar]=1')).toEqual({ foo: { bar: '1' } });
    });
  });

});
