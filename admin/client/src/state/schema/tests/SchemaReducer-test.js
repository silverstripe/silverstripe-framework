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

      expect(nextState.MySchema.id).toBe(schema.id);
      expect(nextState.MySchema.schema).toBe(schema.schema);
      expect(nextState.MySchema.state).toBe(schema.state);
    });
  });

  describe('SET_SCHEMA_STATE_OVERRIDES', () => {
    const type = ACTION_TYPES.SET_SCHEMA_STATE_OVERRIDES;

    it('should set the stateOverride for a given form', () => {
      const initialState = { };

      const stateOverride = [
        { id: 'Text', name: 'Text', value: 'Bob here' },
        { id: 'Age', name: 'Age', value: '99' },
      ];
      const newState = schemaReducer(initialState, {
        type,
        payload: { id: 'MySchema', stateOverride },
      });

      const override = newState.MySchema.stateOverride;
      expect(override[0].name).toBe('Text');
      expect(override[0].value).toBe('Bob here');
      expect(override[1].name).toBe('Age');
      expect(override.length).toBe(2);
    });
  });

  describe('SET_SCHEMA_LOADING', () => {
    const type = ACTION_TYPES.SET_SCHEMA_LOADING;

    it('should mark the form as loading', () => {
      const initialState = { };

      const newState = schemaReducer(initialState, {
        type,
        payload: { id: 'MySchema', loading: true },
      });

      expect(newState.MySchema.metadata.loading).toBe(true);
    });

    it('should mark the form as no longer loading', () => {
      const initialState = { };

      const newState = schemaReducer(initialState, {
        type,
        payload: { id: 'MySchema', loading: false },
      });

      expect(newState.MySchema.metadata.loading).toBe(false);
    });
  });
});
