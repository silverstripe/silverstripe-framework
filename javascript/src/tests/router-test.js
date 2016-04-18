/* global jest, jasmine, describe, beforeEach, it, pit, expect, process */

jest.unmock('../router');

// FYI: Changing this to an import statements broke jest's automocking
const router = require('../router').default;

describe('Router', () => {
  // Mock base dir
  router.getAbsoluteBase = () => 'http://www.testsite.com/base/';

  it('URLS resolve to the correct base route', () => {
    expect(router.normalise('http://www.testsite.com/base/somepage/subpage')).toBe('/somepage/subpage');
    expect(router.normalise('/base/somepage/subpage')).toBe('/somepage/subpage');
    expect(router.normalise('somepage/subpage')).toBe('/somepage/subpage');
  });

  it('External URLS are normalised to absolute paths', () => {
    expect(router.normalise('http://www.anotherpage.com/base/')).toBe('http://www.anotherpage.com/base/');
    expect(router.normalise('/wrong-base/page')).toBe('http://www.testsite.com/wrong-base/page');
    expect(router.normalise('../wrong-base/page')).toBe('http://www.testsite.com/wrong-base/page');
  });
});
