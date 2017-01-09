/* global jest, describe, expect, it, beforeEach */

jest.unmock('merge');
jest.unmock('lib/SilverStripeComponent');
jest.unmock('lib/schemaFieldValues');
jest.unmock('../FormBuilder');
jest.unmock('redux-form');

const React = require('react');
import ReactTestUtils from 'react-addons-test-utils';
import FormBuilder from '../FormBuilder';
import schemaFieldValues, { findField, schemaMerge } from 'lib/schemaFieldValues';

describe('FormBuilder', () => {
  const baseProps = {
    form: 'MyForm',
    baseFormComponent: () => <form />,
    baseFieldComponent: (props) => {
      // eslint-disable-next-line react/prop-types
      const Component = props.component;
      return <Component {...props} />;
    },
    schema: {
      id: 'MyForm',
      schema: {
        attributes: {},
        fields: [],
        actions: [],
      },
      state: {
        fields: [],
      },
    },
  };

  describe('mergeFieldData()', () => {
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
        message: { type: 'good' },
        valid: true,
        value: 'My test field',
      };

      const field = schemaMerge(fieldStructure, fieldState);

      expect(field.component).toBe('TextField');
      expect(field.data.someCustomData.x).toBe(1);
      expect(field.data.someCustomData.y).toBe(2);
      expect(field.message.type).toBe('good');
      expect(field.valid).toBe(true);
      expect(field.value).toBe('My test field');
    });
  });

  describe('getFieldValues()', () => {
    let fieldValues = null;
    const props = Object.assign({}, baseProps);

    it('should retrieve field values based on schema', () => {
      props.schema.schema.fields = [
        { id: 'fieldOne', name: 'fieldOne' },
        { id: 'fieldTwo', name: 'fieldTwo' },
      ];
      props.schema.state.fields = [
        { id: 'fieldOne', name: 'fieldOne', value: 'valOne' },
        { id: 'fieldTwo', name: 'fieldTwo', value: null },
        { id: 'notInSchema', name: 'notInSchema', value: 'invalid' },
      ];
      fieldValues = schemaFieldValues(props.schema.schema, props.schema.state);
      expect(fieldValues).toEqual({
        fieldOne: 'valOne',
        fieldTwo: null,
      });
    });
  });

  describe('handleSubmit', () => {
    let formBuilder = null;
    const props = baseProps;

    beforeEach(() => {
      formBuilder = ReactTestUtils.renderIntoDocument(<FormBuilder {...props} />);

      props.schema.schema.fields = [
        { id: 'fieldOne', name: 'fieldOne' },
        { id: 'fieldTwo', name: 'fieldTwo' },
      ];
      props.schema.schema.actions = [
        { id: 'actionOne', name: 'actionOne' },
        { id: 'actionTwo', name: 'actionTwo' },
      ];
      props.schema.state.fields = [
        { id: 'fieldOne', name: 'fieldOne', value: 'valOne' },
        { id: 'fieldTwo', name: 'fieldTwo', value: null },
        { id: 'notInSchema', name: 'notInSchema', value: 'invalid' },
      ];
    });

    it('should include submitted action from schema', () => {
      formBuilder.setState({ submittingAction: 'actionTwo' });

      const submitApiMock = jest.genMockFunction();
      submitApiMock.mockImplementation(() => Promise.resolve({}));
      formBuilder.submitApi = submitApiMock;

      formBuilder.handleSubmit(schemaFieldValues(props.schema.schema, props.schema.state));

      expect(formBuilder.submitApi.mock.calls[0][0]).toEqual(
        {
          fieldOne: 'valOne',
          fieldTwo: null,
          actionTwo: 1,
        }
      );
    });

    it('should default to first button when none is specified', () => {
      const submitApiMock = jest.genMockFunction();
      submitApiMock.mockImplementation(() => Promise.resolve({}));
      formBuilder.submitApi = submitApiMock;

      formBuilder.handleSubmit(schemaFieldValues(props.schema.schema, props.schema.state));

      expect(formBuilder.submitApi.mock.calls[0][0]).toEqual(
        {
          fieldOne: 'valOne',
          fieldTwo: null,
          actionOne: 1,
        }
      );
    });
  });

  describe('findField()', () => {
    let fields = null;

    it('should retrieve the field in the shallow fields list', () => {
      fields = [
        { id: 'fieldOne', name: 'fieldOne' },
        { id: 'fieldTwo', name: 'fieldTwo' },
        { id: 'fieldThree', name: 'fieldThree' },
        { id: 'fieldFour', name: 'fieldFour' },
      ];
      const field = findField(fields, 'fieldThree');

      expect(field).toBeTruthy();
      expect(field.name).toBe('fieldThree');
    });

    it('should retrieve the field that is a grandchild in the fields list', () => {
      fields = [
        { id: 'fieldOne', name: 'fieldOne' },
        { id: 'fieldTwo', name: 'fieldTwo', children: [
          { id: 'fieldTwoOne', name: 'fieldTwoOne' },
          { id: 'fieldTwoTwo', name: 'fieldTwoTwo', children: [
            { id: 'fieldTwoOne', name: 'fieldTwoOne' },
            { id: 'fieldTwoTwo', name: 'fieldTwoTwo' },
            { id: 'fieldTwoThree', name: 'fieldTwoThree' },
          ] },
        ] },
        { id: 'fieldThree', name: 'fieldThree' },
        { id: 'fieldFour', name: 'fieldFour' },
      ];
      const field = findField(fields, 'fieldTwoThree');

      expect(field).toBeTruthy();
      expect(field.name).toBe('fieldTwoThree');
    });
  });
});
