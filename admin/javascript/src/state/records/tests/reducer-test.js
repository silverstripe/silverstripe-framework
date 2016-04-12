/* global jest, describe, beforeEach, it, expect */

jest.dontMock('deep-freeze');
jest.dontMock('../reducer');
jest.dontMock('../action-types');

const recordsReducer = require('../reducer').default;
const ACTION_TYPES = require('../action-types').default;

describe('recordsReducer', () => {
  describe('FETCH_RECORD_SUCCESS', () => {
    it('adds a new record', () => {
      const initialState = {
        TypeA: {
          11: { ID: 11 },
        },
        TypeB: {
          11: { ID: 11 },
        },
      };

      const nextState = recordsReducer(initialState, {
        type: ACTION_TYPES.FETCH_RECORD_SUCCESS,
        payload: { recordType: 'TypeA', data: { ID: 12 } },
      });

      expect(nextState.TypeA).toEqual({
        11: { ID: 11 },
        12: { ID: 12 },
      });
      expect(nextState.TypeB).toEqual({
        11: { ID: 11 },
      });
    });
  });

  describe('DELETE_RECORD_SUCCESS', () => {
    const initialState = {
      TypeA: {
        11: { ID: 11 },
        12: { ID: 12 },
      },
      TypeB: {
        11: { ID: 11 },
        12: { ID: 12 },
      },
    };

    it('removes records from the declared type', () => {
      const nextState = recordsReducer(initialState, {
        type: ACTION_TYPES.DELETE_RECORD_SUCCESS,
        payload: { recordType: 'TypeA', id: 12 },
      });

      expect(nextState.TypeA).toEqual({
        11: { ID: 11 },
      });
      expect(nextState.TypeB).toEqual({
        11: { ID: 11 },
        12: { ID: 12 },
      });
    });
  });
});
