/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */

jest.dontMock('../Router.js');

// FYI: Changing this to an import statements broke jest's automocking
const router = require('../Router.js').default;

describe('Router', () => {
  // Mock base dir
  router.getAbsoluteBase = () => 'http://www.testsite.com/base/';

  it('URLS resolve to the correct base route', () => {
    expect(router.resolveURLToBase('http://www.testsite.com/base/somepage/subpage')).toBe('/somepage/subpage');
    expect(router.resolveURLToBase('/base/somepage/subpage')).toBe('/somepage/subpage');
    expect(router.resolveURLToBase('somepage/subpage')).toBe('/somepage/subpage');
  });

  it('External URLS are normalised to absolute paths', () => {
    expect(router.resolveURLToBase('http://www.anotherpage.com/base/')).toBe('http://www.anotherpage.com/base/');
    expect(router.resolveURLToBase('/wrong-base/page')).toBe('http://www.testsite.com/wrong-base/page');
    expect(router.resolveURLToBase('../wrong-base/page')).toBe('http://www.testsite.com/wrong-base/page');
  });
});
