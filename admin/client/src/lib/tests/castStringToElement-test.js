/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../castStringToElement');

import castStringToElement from '../castStringToElement';
import CompositeField from 'components/CompositeField/CompositeField';

describe('castStringToElement', () => {
  it('should render a simple div with string', () => {
    const Element = castStringToElement('div', 'My div');
    expect(Element.type).toEqual('div');
    expect(Element.props.children).toEqual('My div');
  });

  it('should render a simple div with string given an object', () => {
    const Element = castStringToElement('div', { text: 'My div in an object' });

    expect(Element.type).toEqual('div');
    expect(Element.props.children).toEqual('My div in an object');
  });

  it('should render a CompositeField with string', () => {
    const Element = castStringToElement(CompositeField, 'My string content');

    expect(Element.type.name).toEqual('CompositeField');
    expect(Element.props.children).toEqual('My string content');
  });

  it('should render a CompositeField with setInnerHtml given an object', () => {
    const Element = castStringToElement(
      CompositeField,
      { html: '<div>My div content in something</div>' }
    );

    expect(Element.type.name).toEqual('CompositeField');
    expect(Element.props.dangerouslySetInnerHTML)
      .toEqual({ __html: '<div>My div content in something</div>' });
  });
});
