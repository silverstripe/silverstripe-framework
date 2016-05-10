/* global jest, describe, beforeEach, it, expect */

jest.unmock('deep-freeze');
jest.unmock('../SchemaActionTypes');
jest.unmock('../SchemaReducer');

import schemaReducer from '../SchemaReducer';
import ACTION_TYPES from '../SchemaActionTypes';

describe('schemaReducer', () => {
  describe('SET_SCHEMA', () => {
    it('should create a new form', () => {
      const initialState = { };
      const serverResponse = { id: 'TestForm', schema_url: 'URL' };

      const nextState = schemaReducer(initialState, {
        type: ACTION_TYPES.SET_SCHEMA,
        payload: { schema: serverResponse },
      });

      expect(nextState.URL.schema.id).toBe('TestForm');
    });
  });
});
