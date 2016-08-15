/* global jest, describe, beforeEach, it, expect */

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../AddToCampaignModal');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import AddToCampaignModal from '../AddToCampaignModal';

describe('AddToCampaignModal', () => {
  let props;

  beforeEach(() => {
    props = {
      title: '',
      show: false,
      handleHide: jest.genMockFunction(),
    };
  });

  describe('getResponse()', () => {
    let addToCampaignModal;
    let response;
    let message;

    beforeEach(() => {
      addToCampaignModal = ReactTestUtils.renderIntoDocument(
        <AddToCampaignModal {...props} />
      );
      response = addToCampaignModal.getResponse();
      message = 'My message';
    });

    it('should show no response initially', () => {
      expect(response).toBeNull();
    });

    it('should show error message', () => {
      message = 'This is an error';

      addToCampaignModal.state = {
        response: message,
        error: true,
      };
      const responseDom = ReactTestUtils.renderIntoDocument(addToCampaignModal.getResponse());
      expect(responseDom.classList.contains('add-to-campaign__response--error')).toBe(true);
      expect(responseDom.textContent).toBe(message);
    });

    it('should show success message', () => {
      message = 'This is a success';

      addToCampaignModal.state = {
        response: message,
        error: false,
      };
      const responseDom = ReactTestUtils.renderIntoDocument(addToCampaignModal.getResponse());
      expect(responseDom.classList.contains('add-to-campaign__response--good')).toBe(true);
      expect(responseDom.textContent).toBe(message);
    });
  });
});
