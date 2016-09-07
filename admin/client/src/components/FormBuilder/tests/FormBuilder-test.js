/* global jest, describe, expect, it, beforeEach */

jest.unmock('merge');
jest.unmock('lib/SilverStripeComponent');
jest.unmock('../FormBuilder');

import { FormBuilderComponent } from '../FormBuilder';

describe('FormBuilderComponent', () => {
  describe('mergeFieldData()', () => {
    let formBuilder = null;

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

    it('should deep merge properties on the originalobject', () => {
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

  describe('getFieldValues()', () => {
    let formBuilder = null;
    let fieldValues = null;
    let props = null;

    it('should retrieve field values based on schema', () => {
      props = {
        form: {
          MyForm: {
            fields: [
              { id: 'fieldOne', value: 'valOne' },
              { id: 'fieldTwo', value: null },
              { id: 'notInSchema', value: 'invalid' },
            ],
          },
        },
        formActions: {},
        schemas: {
          'admin/assets/schema/1': {
            id: 'MyForm',
            schema: {
              fields: [
                { id: 'fieldOne', name: 'fieldOne' },
                { id: 'fieldTwo', name: 'fieldTwo' },
              ],
            },
          },
        },
        schemaActions: {},
        schemaUrl: 'admin/assets/schema/1',
      };
      formBuilder = new FormBuilderComponent(props);

      fieldValues = formBuilder.getFieldValues();
      expect(fieldValues).toEqual({
        fieldOne: 'valOne',
        fieldTwo: null,
      });
    });
  });

  describe('findField()', () => {
    let formBuilder = null;
    let fields = null;
    const props = {
      form: {
        myForm: {},
        formActions: {},
        schemas: {
          'admin/assets/schema/1': {
            id: 'myForm',
            schema: {},
          },
        },
        schemaActions: {},
        schemaUrl: 'admin/assets/schema/1',
      },
    };

    beforeEach(() => {
      formBuilder = new FormBuilderComponent(props);
    });

    it('should retrieve the field in the shallow fields list', () => {
      fields = [
        { id: 'fieldOne' },
        { id: 'fieldTwo' },
        { id: 'fieldThree' },
        { id: 'fieldFour' },
      ];
      const field = formBuilder.findField(fields, 'fieldThree');

      expect(field).toBeTruthy();
      expect(field.id).toBe('fieldThree');
    });

    it('should retrieve the field that is a grandchild in the fields list', () => {
      fields = [
        { id: 'fieldOne' },
        { id: 'fieldTwo', children: [
          { id: 'fieldTwoOne' },
          { id: 'fieldTwoTwo', children: [
            { id: 'fieldTwoOne' },
            { id: 'fieldTwoTwo' },
            { id: 'fieldTwoThree' },
          ] },
        ] },
        { id: 'fieldThree' },
        { id: 'fieldFour' },
      ];
      const field = formBuilder.findField(fields, 'fieldTwoThree');

      expect(field).toBeTruthy();
      expect(field.id).toBe('fieldTwoThree');
    });
  });
});
