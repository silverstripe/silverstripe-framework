/* global jest, describe, expect, it, beforeEach */

jest.unmock('merge');
jest.unmock('lib/SilverStripeComponent');
jest.unmock('../FormBuilder');

import { FormBuilderComponent } from '../FormBuilder';

describe('FormBuilderComponent', () => {
  describe('mergeFieldData()', () => {
    let formBuilder;

    beforeEach(() => {
      const props = {
        form: {},
        formActions: {},
        schemas: {},
        schemaActions: {},
        schemaUrl: 'admin/assets/schema/1',
      };

      formBuilder = new FormBuilderComponent(props);
    });

    it('should deep merge properties on the origional object', () => {
      const fieldStructure = {
        component: 'TextField',
        data: {
          someCustomData: {
            x: 1,
          },
        },
      };

      const fieldState = {
        data: {
          someCustomData: {
            y: 2,
          },
        },
        messages: [{ type: 'good' }],
        valid: true,
        value: 'My test field',
      };

      const field = formBuilder.mergeFieldData(fieldStructure, fieldState);

      expect(field.component).toBe('TextField');
      expect(field.data.someCustomData.x).toBe(1);
      expect(field.data.someCustomData.y).toBe(2);
      expect(field.messages[0].type).toBe('good');
      expect(field.valid).toBe(true);
      expect(field.value).toBe('My test field');
    });
  });
});
