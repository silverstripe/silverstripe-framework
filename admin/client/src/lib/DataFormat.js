import i18n from 'i18n';
import qs from 'qs';

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
  if ((!number && number !== 0) || !metric) {
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
