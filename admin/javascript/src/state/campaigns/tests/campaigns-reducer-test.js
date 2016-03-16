jest.dontMock('deep-freeze');
jest.dontMock('../reducer');
jest.dontMock('../action-types');

var campaignsReducer = require('../reducer').default,
    ACTION_TYPES = require('../action-types').default;

describe('campaignsReducer', () => {

    describe('CREATE_CAMPAIGN', () => {

    });

    describe('UPDATE_CAMPAIGN', () => {
        
    });

    describe('DELETE_CAMPAIGN', () => {
        
    });

    describe('FETCH_CAMPAIGN_REQUEST', () => {

        it('should set the "isFetching" flag', () => {
            const initialState = {
                isFetching: false
            };

            const action = { type: ACTION_TYPES.FETCH_CAMPAIGN_REQUEST };

            const nextState = campaignsReducer(initialState, action);

            expect(nextState.isFetching).toBe(true);
        });

    });

    describe('FETCH_CAMPAIGN_FAILURE', () => {

        it('should unset the "isFetching" flag', () => {
            const initialState = {
                isFetching: true
            };

            const action = { type: ACTION_TYPES.FETCH_CAMPAIGN_FAILURE };

            const nextState = campaignsReducer(initialState, action);

            expect(nextState.isFetching).toBe(false);
        });

    });

    describe('FETCH_CAMPAIGN_SUCCESS', () => {

        it('should unset the "isFetching" flag', () => {
            const initialState = {
                isFetching: true
            };

            const action = { type: ACTION_TYPES.FETCH_CAMPAIGN_FAILURE };

            const nextState = campaignsReducer(initialState, action);

            expect(nextState.isFetching).toBe(false);
        });

    });

});
