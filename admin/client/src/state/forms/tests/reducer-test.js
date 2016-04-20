jest.unmock('deep-freeze');
jest.unmock('../reducer');
jest.unmock('../action-types');

import deepFreeze from 'deep-freeze';
import { ACTION_TYPES } from '../action-types';
import formsReducer from '../reducer';

describe('formsReducer', () => {

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

      const nextState = formsReducer(initialState, {
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
      const nextState = formsReducer(initialState, {
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
      const nextState = formsReducer(initialState, {
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
      },
    });

    it('should add top level form messages', () => {
      const nextState = formsReducer(initialState, {
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
    });
  });
});
