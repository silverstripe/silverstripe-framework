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
  if (size < 1024) {
    return `${size} bytes`;
  }
  if (size < 1024 * 10) {
    return `${Math.round(size / 1024 * 10) / 10} KB`;
  }
  if (size < 1024 * 1024) {
    return `${Math.round(size / 1024)} KB`;
  }
  if (size < 1024 * 1024 * 10) {
    return `${Math.round((size / 1024) / 1024 * 10) / 10} MB`;
  }
  if (size < 1024 * 1024 * 1024) {
    return `${Math.round((size / 1024) / 1024)} MB`;
  }
  return `${Math.round(size / (1024 * 1024 * 1024) * 10) / 10} GB`;
}
