jest.unmock('silverstripe-component');
jest.unmock('../');

import { FormBuilderComponent } from '../';

describe('FormBuilderComponent', () => {

    describe('getFormSchema()', () => {

        var formBuilder;

        beforeEach(() => {
            const props = {
                store: {
                    getState: () => {}
                },
                actions: {},
                schemaUrl: 'admin/assets/schema/1',
                schema: { forms: [{ schema: { id: '1', schema_url: 'admin/assets/schema/1' } }] }
            };

            formBuilder = new FormBuilderComponent(props);
        });
    });

});
