/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../FormBuilderModal');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import FormBuilderModal from '../FormBuilderModal';

describe('FormBuilderModal', () => {
  let props = null;

  beforeEach(() => {
    props = {
      title: '',
      show: false,
      handleHide: jest.genMockFunction(),
    };
  });

  describe('getResponse()', () => {
    let formModal = null;
    let response = null;
    let message = null;

    beforeEach(() => {
      formModal = ReactTestUtils.renderIntoDocument(
        <FormBuilderModal {...props} />
      );
      response = formModal.getResponse();
      message = 'My message';
    });

    it('should show no response initially', () => {
      expect(response).toBeNull();
    });

    it('should show error message', () => {
      message = 'This is an error';

      formModal.state = {
        response: message,
        error: true,
      };
      const responseDom = ReactTestUtils.renderIntoDocument(formModal.getResponse());
      expect(responseDom.classList.contains('error')).toBe(true);
      expect(responseDom.textContent).toBe(message);
    });

    it('should show success message', () => {
      message = 'This is a success';

      formModal.state = {
        response: message,
        error: false,
      };
      const responseDom = ReactTestUtils.renderIntoDocument(formModal.getResponse());
      expect(responseDom.classList.contains('good')).toBe(true);
      expect(responseDom.textContent).toBe(message);
    });
  });
});
