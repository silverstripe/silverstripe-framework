/* global jest, describe, afterEach, it, expect */

jest.unmock('deep-freeze');
jest.unmock('../RouteRegister.js');

import routeRegister from '../RouteRegister.js';

describe('RouteRegister', () => {
  afterEach(() => {
    routeRegister.removeAll();
  });

  describe('add', () => {
    it('should return the register having added the route', () => {
      const callback = () => {};
      const register = routeRegister.add('test', callback);
      expect(register.test).toBeDefined();
      expect(register.test).toBe(callback);
    });

    it('should throw an error if the route already exists', () => {
      const callback = () => {};
      routeRegister.add('test', callback);
      expect(() => {
        routeRegister.add('test', callback);
      }).toThrow();
    });
  });

  describe('remove', () => {
    it('should return the register having removed the route', () => {
      const callback = () => {};
      let register = routeRegister.add('test', callback);
      expect(register.test).toBeDefined();

      register = routeRegister.remove('test');
      expect(register.test).toBeUndefined();
    });
  });

  describe('removeAll', () => {
    it('should return an empty register', () => {
      const callback = () => {};
      let register = routeRegister.add('test', callback);
      expect(register.test).toBeDefined();

      register = routeRegister.removeAll();
      expect(register.test).toBeUndefined();
    });
  });

  describe('get', () => {
    it('should return the route and associated callback if it exists', () => {
      const callback = () => {};
      routeRegister.add('test', callback);

      const register = routeRegister.get('test');
      expect(register.test).toBe(callback);
    });

    it('should return null if the route doesn\'t exist', () => {
      const register = routeRegister.get('test');
      expect(register).toBe(null);
    });
  });

  describe('getAll', () => {
    it('should return the register', () => {
      const callback = () => {};
      routeRegister.add('test1', callback);
      routeRegister.add('test2', callback);

      const register = routeRegister.getAll();
      expect(register.test1).toBeDefined();
      expect(register.test2).toBeDefined();
    });
  });
});
