jest.unmock('deep-freeze');
jest.unmock('../action-types.js');
jest.unmock('../reducer.js');

import schemaReducer from '../reducer.js';
import ACTION_TYPES from '../action-types';

describe('schemaReducer', () => {

    describe('SET_SCHEMA', () => {

        it('should create a new form when none exist', () => {
            const initialState = { forms: [] };
            const serverResponse = { id : 'TestForm' };

            const nextState = schemaReducer(initialState, { 
                type: ACTION_TYPES.SET_SCHEMA,
                payload: { schema: serverResponse }
            });

            expect(nextState.forms.length).toBe(1);
            expect(JSON.stringify(nextState.forms[0])).toBe(JSON.stringify({ schema: { id: 'TestForm' } }));
        });

        it('should update an existing form', () => {
            const initialState = {
                forms: [{
                    schema: {
                        id: 'TestForm',
                        name: 'TestForm'
                    }
                }]
            };

            const serverResponse = { id: 'TestForm', name: 'BetterTestForm' };

            const nextState = schemaReducer(initialState, { 
                type: ACTION_TYPES.SET_SCHEMA,
                payload: { schema: serverResponse }
            });

            expect(nextState.forms.length).toBe(1);
            expect(JSON.stringify(nextState.forms[0])).toBe(JSON.stringify({ schema: { id: 'TestForm', name: 'BetterTestForm' } }));
        });

        it("should only update the the form's 'schema' key", () => {
            const initialState = {
                forms: [{
                    schema: {
                        id: 'TestForm',
                        name: 'TestForm'
                    },
                    state: {
                        error: 'Oops!'
                    }
                }]
            };

            const serverResponse = { id: 'TestForm', name: 'BetterTestForm' };

            const nextState = schemaReducer(initialState, { 
                type: ACTION_TYPES.SET_SCHEMA,
                payload: { schema: serverResponse }
            });

            expect(nextState.forms.length).toBe(1);
            expect(JSON.stringify(nextState.forms[0])).toBe(JSON.stringify({ schema: { id: 'TestForm', name: 'BetterTestForm' }, state: { error: 'Oops!' } }));
        });

    });

});
