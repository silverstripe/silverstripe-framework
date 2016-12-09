import i18n from 'i18n';
import QueryString from 'query-string';

export function urlQuery(location, newQuery) {
  if (newQuery === null) {
    return null;
  }
  if (newQuery) {
    const mergedQuery = Object.assign({}, location.query, newQuery);
    const query = QueryString.stringify(mergedQuery);

    if (query) {
      return `?${query}`;
    }
    return '';
  }
  return location.search;
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
