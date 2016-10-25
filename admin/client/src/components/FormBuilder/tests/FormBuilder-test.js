/* global jest, describe, expect, it, beforeEach */

jest.unmock('merge');
jest.unmock('lib/SilverStripeComponent');
jest.unmock('../FormBuilder');
jest.unmock('redux-form');

const React = require('react');
import ReactTestUtils from 'react-addons-test-utils';
import FormBuilder from '../FormBuilder';

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
    let formBuilder = null;

    beforeEach(() => {
      formBuilder = new FormBuilder(baseProps);
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
    const props = Object.assign({}, baseProps);

    it('should retrieve field values based on schema', () => {
      props.schema.schema.fields = [
        { id: 'fieldOne', name: 'fieldOne' },
        { id: 'fieldTwo', name: 'fieldTwo' },
      ];
      props.schema.state.fields = [
        { id: 'fieldOne', value: 'valOne' },
        { id: 'fieldTwo', value: null },
        { id: 'notInSchema', value: 'invalid' },
      ];
      formBuilder = new FormBuilder(baseProps);

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

    beforeEach(() => {
      formBuilder = new FormBuilder(baseProps);
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
        { id: 'fieldOne', value: 'valOne' },
        { id: 'fieldTwo', value: null },
        { id: 'notInSchema', value: 'invalid' },
      ];
    });

    it('should include submitted action from schema', () => {
      formBuilder.setState({ submittingAction: 'actionTwo' });

      const submitApiMock = jest.genMockFunction();
      submitApiMock.mockImplementation(() => Promise.resolve({}));
      formBuilder.submitApi = submitApiMock;

      formBuilder.handleSubmit(formBuilder.getFieldValues());

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

      formBuilder.handleSubmit(formBuilder.getFieldValues());

      expect(formBuilder.submitApi.mock.calls[0][0]).toEqual(
        {
          fieldOne: 'valOne',
          fieldTwo: null,
          actionOne: 1,
        }
      );
    });
  });
});
