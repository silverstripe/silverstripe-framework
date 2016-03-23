jest.unmock('deep-freeze');
jest.unmock('../action-types.js');
jest.unmock('../reducer.js');

import schemaReducer from '../reducer.js';
import ACTION_TYPES from '../action-types';

describe('schemaReducer', () => {

    describe('SET_SCHEMA', () => {

        it('should create a new form', () => {
            const initialState = { };
            const serverResponse = { id: 'TestForm', schema_url: 'URL' };

            const nextState = schemaReducer(initialState, { 
                type: ACTION_TYPES.SET_SCHEMA,
                payload: { schema: serverResponse }
            });

            expect(nextState.URL.schema.id).toBe('TestForm');
        });
    });
});
