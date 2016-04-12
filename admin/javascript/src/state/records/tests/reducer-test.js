/* global jest, describe, beforeEach, it, expect */

jest.dontMock('deep-freeze');
jest.dontMock('../reducer');
jest.dontMock('../action-types');

const recordsReducer = require('../reducer').default;
const ACTION_TYPES = require('../action-types').default;

describe('recordsReducer', () => {
  describe('DELETE_RECORD_SUCCESS', () => {
    const initialState = {
      TypeA: [
        { ID: 1 },
        { ID: 2 },
      ],
      TypeB: [
        { ID: 1 },
        { ID: 2 },
      ],
    };

    it('removes records from the declared type', () => {
      const nextState = recordsReducer(initialState, {
        type: ACTION_TYPES.DELETE_RECORD_SUCCESS,
        payload: { recordType: 'TypeA', id: 2 },
      });

      expect(nextState.TypeA.length).toBe(1);
      expect(nextState.TypeA[0].ID).toBe(1);
      expect(nextState.TypeB.length).toBe(2);
      expect(nextState.TypeB[0].ID).toBe(1);
      expect(nextState.TypeB[1].ID).toBe(2);
    });
  });
});
