import React from 'react';

/**
 * Safely cast string to container element. Supports custom HTML values.
 *
 * See DBField::getSchemaValue()
 *
 * @param {String|Component} Container Container type
 * @param {*} value Form schema value
 * @param {object} props container props
 * @returns {Component}
 */
export default function castStringToElement(Container, value, props = {}) {
  if (value && typeof value.react !== 'undefined') {
    return <Container {...props}>{value.react}</Container>;
  }

  // HTML value
  if (value && typeof value.html !== 'undefined') {
    if (value.html !== null) {
      const html = { __html: value.html };
      return <Container {...props} dangerouslySetInnerHTML={html} />;
    }
    return null;
  }

  // Plain value
  let body = null;
  if (value && typeof value.text !== 'undefined') {
    body = value.text;
  } else {
    body = value;
  }

  if (body && typeof body === 'object') {
    throw new Error(`Unsupported string value ${JSON.stringify(body)}`);
  }

  if (body !== null && typeof body !== 'undefined') {
    return <Container {...props}>{body}</Container>;
  }
  return null;
}
