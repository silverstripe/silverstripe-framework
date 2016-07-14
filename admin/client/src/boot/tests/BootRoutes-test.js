/* global jest, jasmine, describe, afterEach, it, expect */

jest.unmock('../BootRoutes.js');
jest.unmock('lib/Config');
jest.unmock('lib/Router');

import BootRoutes from '../BootRoutes.js';

describe('Bootroutes', () => {
  beforeEach(() => {
    // Set window config
    window.ss.config = {
      baseUrl: '/subdir',
      absoluteBaseUrl: 'http://www.mypage.com/subdir/',
      sections: {
        MySection: {
          url: 'admin/mysection',
          reactRouter: true,
        },
        OldSection: {
          url: 'admin/old-section',
        },
      },
    };
  });

  describe('add', () => {
    it('loading react section should boot react', () => {
      const routes = new BootRoutes({});
      expect(routes.matchesLegacyRoute('/subdir/admin/mysection'))
        .toEqual(false);
      expect(routes.matchesLegacyRoute('/subdir/admin/mysection/subpage'))
        .toEqual(false);
      expect(routes.matchesLegacyRoute('/subdir/admin/old-section'))
        .toEqual(true);

      // It doesn't really matter what unknown sections are known as, as
      // either router will do a full page redirect to unknown routes.
      expect(routes.matchesLegacyRoute('/subdir/admin/anothersection'))
        .toEqual(false);
    });
  });
});
