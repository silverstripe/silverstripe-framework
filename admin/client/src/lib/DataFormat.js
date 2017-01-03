import i18n from 'i18n';
import qs from 'qs';

/**
 * Merge existing querystring with new querystring object.
 * Does not deep merge.
 *
 * Example:
 * `urlQuery({ foo: 1 }, { bar: 2 })` returns `'?foo=1&bar=2'`
 *
 * @param {Object} locationQuery - Current browser querystring object (window.location.query)
 * @param {Object} newQuery - New object to update. Set to null to clear instead of merge.
 * @returns {String}
 */
export function urlQuery(locationQuery, newQuery) {
  if (newQuery === null) {
    return '';
  }
  let mergedQuery = locationQuery || {};
  if (newQuery) {
    mergedQuery = Object.assign({}, mergedQuery, newQuery);
  }
  const query = qs.stringify(mergedQuery);
  if (query) {
    return `?${query}`;
  }
  return '';
}

/**
 * Turn flatterned querystring object into recursive nested objects,
 * similarly to how PHP handles nested querystring objects.
 *
 * Example:
 * `decodeQuery('query[val]=bob')` returns `{query: { val: bob }}`
 *
 * @param {String} query - Querystring string
 * @return {Object} - Unflattened query object
 */
export function decodeQuery(query) {
  return qs.parse(query.replace(/^\?/, ''));
}

export function fileSize(size) {
  let number = null;
  let metric = '';

  if (size < 1024) {
    number = size;
    metric = 'bytes';
  } else if (size < 1024 * 10) {
    number = Math.round(size / 1024 * 10) / 10;
    metric = 'KB';
  } else if (size < 1024 * 1024) {
    number = Math.round(size / 1024);
    metric = 'KB';
  } else if (size < 1024 * 1024 * 10) {
    number = Math.round(size / 1024 * 1024 * 10) / 10;
    metric = 'MB';
  } else if (size < 1024 * 1024 * 1024) {
    number = Math.round(size / 1024 * 1024);
    metric = 'MB';
  }
  if (!number || !metric) {
    number = Math.round(size / (1024 * 1024 * 1024) * 10) / 10;
    metric = 'GB';
  }

  if (isNaN(number)) {
    return i18n._t('File.NO_SIZE', 'N/A');
  }
  return `${number} ${metric}`;
}

export function getFileExtension(filename) {
  return /[.]/.exec(filename)
    ? filename.replace(/^.+[.]/, '')
    : '';
}
