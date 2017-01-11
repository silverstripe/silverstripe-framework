/* global jest, describe, beforeEach, it, expect, console */

// Needs to be set before requiring other libraries
global.console = { warn: jest.fn() };

jest.unmock('react');
jest.unmock('react-addons-test-utils');
jest.unmock('../Validator');

import Validator from '../Validator';

describe('Validator', () => {
  let validator = new Validator({});
  let values = {};

  it('should set values on construct', () => {
    values = { one: '' };
    validator = new Validator(values);

    expect(validator.values).toBe(values);
  });

  describe('getFieldValue()', () => {
    beforeEach(() => {
      validator = new Validator({});
      values = {
        two: null,
        three: 0,
        four: false,
      };
      validator.setValues(values);
    });

    it('should return blank string for undefined', () => {
      const value = validator.getFieldValue('one');

      expect(value).toBe('');
    });

    it('should return blank string for null', () => {
      const value = validator.getFieldValue('two');

      expect(value).toBe('');
    });

    it('should return "0" for 0', () => {
      const value = validator.getFieldValue('three');

      expect(value).toBe('0');
    });

    it('should return blank string for false', () => {
      const value = validator.getFieldValue('four');

      expect(value).toBe('');
    });
  });

  describe('validateValue()', () => {
    it('should throw an error', () => {
      validator = new Validator({});
      validator.validateValue('one', 'ruledoesnotexist');
      // eslint-disable-next-line no-console
      expect(console.warn).toBeCalled();
    });
  });

  describe('validateFieldSchema()', () => {
    it('should call validateField() with proper params', () => {
      validator = new Validator({});
      validator.validateField = jest.genMockFunction();

      validator.validateFieldSchema({
        name: 'one',
        validation: {},
        leftTitle: null,
        title: 'Title',
        customValidationMessage: 'message',
      });

      expect(validator.validateField).toBeCalledWith('one', {}, 'Title', 'message');
    });
  });

  describe('validateField', () => {
    const rules = {
      required: true,
      numeric: true,
    };

    beforeEach(() => {
      validator = new Validator({});
      validator.validateValue = (value, rule) => {
        switch (rule) {
          case 'required': {
            return value !== '';
          }
          case 'numeric': {
            return !isNaN(value);
          }
          default: {
            return null;
          }
        }
      };
    });

    it('should return only required message if blank and required', () => {
      validator.setValues({ one: '' });
      validator.validateValue = jest.genMockFunction();

      validator.validateField('one', rules, 'One Field');

      expect(validator.validateValue.mock.calls.length).toBe(0);
    });

    it('should return only a numeric error if alpha characters were provided', () => {
      validator.setValues({ one: 'abc' });
      validator.validateValue = jest.genMockFunction();

      validator.validateField('one', rules, 'One Field');

      expect(validator.validateValue).toBeCalled();
    });

    it('should not return the override message when valid', () => {
      validator.setValues({ one: '123' });

      const results = validator.validateField('one', rules, 'One Field', 'My override message');

      expect(results.valid).toBe(true);
      expect(results.errors.length).toBe(0);
    });

    it('should return only the override message when invalid', () => {
      validator.setValues({ one: 'abc' });

      const results = validator.validateField('one', rules, 'One Field', 'My override message');

      expect(results.valid).toBe(false);
      expect(results.errors[0]).toBe('My override message');
      expect(results.errors.length).toBe(1);
    });
  });

  describe('getMessage()', () => {
    let config = {};
    validator = new Validator({});

    it('should return config message', () => {
      config = { message: 'My config message' };
      const message = validator.getMessage('whatever rule', config);

      expect(message).toBe('My config message');
    });

    it('should return config message with custom title', () => {
      config = { title: 'This field', message: '{name} is my config message' };
      const message = validator.getMessage('whatever rule', config);

      expect(message).toBe('This field is my config message');
    });

    it('should return default required message', () => {
      config = {};
      const message = validator.getMessage('required', config);

      expect(message).toMatch(/required/i);
      expect(message).toMatch(/\{name\}/i);
    });

    it('should return default numeric message', () => {
      config = {};
      const message = validator.getMessage('numeric', config);

      expect(message).toMatch(/number/i);
    });

    it('should return default message for no matching rules', () => {
      config = {};
      const message = validator.getMessage('notavalidruledefined', config);

      expect(message).toMatch(/not a valid value/i);
    });

    it('should return default required message with title placed', () => {
      config = { title: 'This field' };
      const message = validator.getMessage('required', config);

      expect(message).toMatch(/required/i);
      expect(message).toMatch(/This field/i);
    });
  });
});
