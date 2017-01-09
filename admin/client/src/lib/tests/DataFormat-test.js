/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */


jest.unmock('isomorphic-fetch');
jest.unmock('../DataFormat');
jest.unmock('qs');
jest.unmock('merge');

import { decodeQuery } from '../DataFormat';


describe('DataFormat', () => {
  describe('decodeQuery', () => {
    it('should decode flat keys', () => {
      expect(decodeQuery('?foo=1')).toEqual({ foo: '1' });
    });
    it('should decode nested keys (PHP style)', () => {
      expect(decodeQuery('?foo[bar]=1')).toEqual({ foo: { bar: '1' } });
    });
  });
});
