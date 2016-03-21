import deepFreeze from 'deep-freeze';
import ACTION_TYPES from './action-types';

const initialState = deepFreeze({
    forms: []
});

export default function schemaReducer(state = initialState, action = null) {

    switch (action.type) {

        case ACTION_TYPES.SET_SCHEMA:
            if (state.forms.length === 0) {
                return deepFreeze(Object.assign({}, state, { forms: [action.payload] }));
            }

            // Replace the form which has a matching `schema.id` property.
            return deepFreeze(Object.assign({}, state, {
                forms: state.forms.map((form) => {
                    if (form.schema.id === action.payload.schema.id) {
                        // Only replace the `schema` key incase other actions have updated other keys.
                        return Object.assign({}, form, action.payload);
                    }

                    return form;
                })
            }));

        default:
            return state;
    }

}
