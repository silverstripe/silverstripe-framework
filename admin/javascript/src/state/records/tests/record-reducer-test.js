jest.dontMock('deep-freeze');
jest.dontMock('../reducer');
jest.dontMock('../action-types');

var recordsReducer = require('../reducer').default,
    ACTION_TYPES = require('../action-types').default;

describe('recordsReducer', () => {

    describe('CREATE_RECORD', () => {

    });

    describe('UPDATE_RECORD', () => {
        
    });

    describe('DELETE_RECORD', () => {
        
    });

    describe('FETCH_RECORD_REQUEST', () => {

        it('should set the "isFetching" flag', () => {
            const initialState = {
                isFetching: false
            };

            const action = { type: ACTION_TYPES.FETCH_RECORD_REQUEST };

            const nextState = recordsReducer(initialState, action);

            expect(nextState.isFetching).toBe(true);
        });

    });

    describe('FETCH_RECORD_FAILURE', () => {

        it('should unset the "isFetching" flag', () => {
            const initialState = {
                isFetching: true
            };

            const action = { type: ACTION_TYPES.FETCH_RECORD_FAILURE };

            const nextState = recordsReducer(initialState, action);

            expect(nextState.isFetching).toBe(false);
        });

    });

    describe('FETCH_RECORD_SUCCESS', () => {

        it('should unset the "isFetching" flag', () => {
            const initialState = {
                isFetching: true
            };

            const action = { type: ACTION_TYPES.FETCH_RECORD_FAILURE };

            const nextState = recordsReducer(initialState, action);

            expect(nextState.isFetching).toBe(false);
        });

    });

});
