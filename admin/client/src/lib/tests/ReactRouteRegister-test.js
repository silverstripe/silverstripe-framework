/* global jest, jasmine, describe, afterEach, it, expect */

jest.unmock('deep-freeze-strict');
jest.unmock('../ReactRouteRegister.js');

import reactRouteRegister from '../ReactRouteRegister.js';

const DummyComponent = () => {};

describe('ReactRouteRegister', () => {
  afterEach(() => {
    reactRouteRegister.reset();
  });

  describe('add', () => {
    it('should return the register having added the route', () => {
      reactRouteRegister.add({
        path: 'test',
        component: DummyComponent,
      });

      // splat route should be added as default
      expect(reactRouteRegister.getChildRoutes())
        .toEqual([{
          path: 'test',
          component: DummyComponent,
          childRoutes: [{ path: '**' }],
        }]);

      // Check root route
      expect(reactRouteRegister.getRootRoute())
        .toEqual({
          path: '/',
          getChildRoutes: jasmine.any(Function),
        });

      // Add nested routes
      reactRouteRegister.add({
        path: 'nested',
        component: DummyComponent,
      }, ['test']);
      reactRouteRegister.add({
        path: 'leaf',
        component: DummyComponent,
      }, ['test', 'nested']);

      // Check each route exists
      expect(reactRouteRegister.getChildRoutes())
        .toEqual([{
          path: 'test',
          component: DummyComponent,
          childRoutes: [
            {
              path: 'nested',
              component: DummyComponent,
              childRoutes: [
                {
                  path: 'leaf',
                  component: DummyComponent,
                  childRoutes: [{ path: '**' }],
                },
                { path: '**' },
              ],
            },
            { path: '**' },
          ],
        }]);
    });
  });

  describe('remove', () => {
    it('should return the register having removed the route', () => {
      reactRouteRegister.add({
        path: 'test',
        component: DummyComponent,
        childRoutes: [{
          path: 'nested',
          component: DummyComponent,
        }],
      });

      expect(reactRouteRegister.remove('nested', ['test']))
        .toEqual({
          path: 'nested',
          component: DummyComponent,
        });

      expect(reactRouteRegister.getChildRoutes())
        .toEqual([{
          path: 'test',
          component: DummyComponent,
          childRoutes: [
            { path: '**' },
          ],
        }]);
    });
  });

  describe('reset', () => {
    it('should return an empty register', () => {
      reactRouteRegister.add({
        path: 'test',
        component: DummyComponent,
        childRoutes: [{
          path: 'nested',
          component: DummyComponent,
        }],
      });

      reactRouteRegister.reset();
      expect(reactRouteRegister.getChildRoutes())
        .toEqual([]);
    });
  });

  describe('updateRootRoute', () => {
    it('should modify and replace root route', () => {
      const MyApp = () => {};
      reactRouteRegister.updateRootRoute({
        component: MyApp,
      });

      expect(reactRouteRegister.getRootRoute())
        .toEqual({
          path: '/',
          component: MyApp,
          getChildRoutes: jasmine.any(Function),
        });
    });
  });
});
