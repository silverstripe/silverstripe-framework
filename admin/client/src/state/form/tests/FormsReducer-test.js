/* global jest, describe, expect, it, beforeEach */

jest.unmock('deep-freeze');
jest.unmock('../FormReducer');
jest.unmock('../FormActionTypes');

import deepFreeze from 'deep-freeze';
import { ACTION_TYPES } from '../FormActionTypes';
import formReducer from '../FormReducer';

describe('formReducer', () => {
  describe('ADD_FORM', () => {
    const initialState = deepFreeze({
      DetailEditForm: {
        fields: [
          {
            data: [],
            id: 'Form_DetailEditForm_Name',
            messages: [],
            valid: true,
            value: 'Test',
          },
        ],
        submitting: false,
      },
    });

    it('should add a form', () => {
      const payload = {
        formState: {
          fields: [
            {
              data: [],
              id: 'Form_EditForm_Name',
              messages: [],
              valid: true,
              value: 'Test',
            },
          ],
          id: 'EditForm',
          messages: [],
        },
      };

      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.ADD_FORM,
        payload,
      });

      expect(nextState.DetailEditForm).toBeDefined();
      expect(nextState.EditForm).toBeDefined();
      expect(nextState.EditForm.fields).toBeDefined();
      expect(nextState.EditForm.fields[0].data).toBeDefined();
      expect(nextState.EditForm.fields[0].id).toBe('Form_EditForm_Name');
      expect(nextState.EditForm.fields[0].messages).toBeDefined();
      expect(nextState.EditForm.fields[0].valid).toBe(true);
      expect(nextState.EditForm.fields[0].value).toBe('Test');
      expect(nextState.EditForm.submitting).toBe(false);
    });
  });

  describe('REMOVE_FORM', () => {
    const initialState = deepFreeze({
      DetailEditForm: {
        fields: [
          {
            data: [],
            id: 'Form_DetailEditForm_Name',
            messages: [],
            valid: true,
            value: 'Test',
          },
        ],
      },
      EditForm: {
        fields: [
          {
            data: [],
            id: 'Form_EditForm_Name',
            messages: [],
            valid: true,
            value: 'Test',
          },
        ],
      },
    });

    it('should remove the form', () => {
      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.REMOVE_FORM,
        payload: { formId: 'DetailEditForm' },
      });

      expect(nextState.DetailEditForm).toBeUndefined();
      expect(nextState.EditForm).toBeDefined();
    });
  });

  describe('UPDATE_FIELD', () => {
    const initialState = deepFreeze({
      DetailEditForm: {
        fields: [
          {
            data: [],
            id: 'Form_DetailEditForm_Name',
            messages: [],
            valid: true,
            value: 'Test',
          },
        ],
      },
    });

    it('should update properties of a form field', () => {
      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.UPDATE_FIELD,
        payload: {
          formId: 'DetailEditForm',
          updates: {
            id: 'Form_DetailEditForm_Name',
            value: 'Updated',
          },
        },
      });

      expect(nextState.DetailEditForm.fields[0].value).toBe('Updated');
    });
  });

  describe('SUBMIT_FORM_SUCCESS', () => {
    const initialState = deepFreeze({
      DetailEditForm: {
        fields: [
          {
            data: [],
            id: 'Form_DetailEditForm_Name',
            messages: [],
            valid: true,
            value: 'Test',
          },
        ],
        submitting: true,
      },
    });

    it('should add top level form messages', () => {
      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.SUBMIT_FORM_SUCCESS,
        payload: {
          id: 'DetailEditForm',
          response: {
            id: 'DetailEditForm',
            state: {
              fields: [
                {
                  data: [],
                  id: 'Form_DetailEditForm_Name',
                  messages: [],
                  valid: true,
                  value: 'Test',
                },
              ],
              messages: [
                {
                  type: 'good',
                  value: 'Saved.',
                },
              ],
            },
          },
        },
      });

      expect(nextState.DetailEditForm.messages).toBeDefined();
      expect(nextState.DetailEditForm.messages.length).toBe(1);
      expect(nextState.DetailEditForm.messages[0].type).toBe('good');
      expect(nextState.DetailEditForm.messages[0].value).toBe('Saved.');
      expect(nextState.DetailEditForm.submitting).toBe(false);
    });
  });

  describe('SUBMIT_FORM_REQUEST', () => {
    it('should set submitting to true', () => {
      const initialState = deepFreeze({
        DetailEditForm: {
          fields: [
            {
              data: [],
              id: 'Form_DetailEditForm_Name',
              messages: [],
              valid: true,
              value: 'Test',
            },
          ],
          submitting: false,
        },
      });

      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.SUBMIT_FORM_REQUEST,
        payload: { formId: 'DetailEditForm' },
      });

      expect(nextState.DetailEditForm.submitting).toBe(true);
    });
  });

  describe('SUBMIT_FORM_FAILURE', () => {
    it('should set submitting to false', () => {
      const initialState = deepFreeze({
        DetailEditForm: {
          fields: [
            {
              data: [],
              id: 'Form_DetailEditForm_Name',
              messages: [],
              valid: true,
              value: 'Test',
            },
          ],
          submitting: true,
        },
      });

      const nextState = formReducer(initialState, {
        type: ACTION_TYPES.SUBMIT_FORM_FAILURE,
        payload: { formId: 'DetailEditForm' },
      });

      expect(nextState.DetailEditForm.submitting).toBe(false);
    });
  });
});
