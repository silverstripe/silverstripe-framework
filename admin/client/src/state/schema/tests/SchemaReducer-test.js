/* global jest, describe, beforeEach, it, expect */

jest.unmock('deep-freeze-strict');
jest.unmock('../SchemaActionTypes');
jest.unmock('../SchemaReducer');

import schemaReducer from '../SchemaReducer';
import ACTION_TYPES from '../SchemaActionTypes';

describe('schemaReducer', () => {
  describe('SET_SCHEMA', () => {
    it('should create a new form', () => {
      const initialState = { };
      const schema = {
        id: 'MySchema',
        schema: { id: 'TestForm' },
      };

      const nextState = schemaReducer(initialState, {
        type: ACTION_TYPES.SET_SCHEMA,
        payload: schema,
      });

      expect(nextState.MySchema).toBe(schema);
    });
  });
});
