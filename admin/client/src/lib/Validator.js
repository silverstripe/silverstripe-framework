import validator from 'validator';

/**
 * Provides methods for handling client-side validation for Forms.
 * Handles logic for repetitive validation tasks using a list of Form values and a given field or
 * form schema, to determine if a field is valid.
 *
 * @class
 */
class Validator {
  constructor(values) {
    this.setValues(values);
  }

  setValues(values) {
    this.values = values;
  }

  getFieldValue(name) {
    let value = this.values[name];

    if (typeof value !== 'string') {
      // we'll consider it an empty value if it's false, undefined or null
      if (typeof value === 'undefined' || value === null || value === false) {
        value = '';
      } else {
        // CSVs the array by default, may look at doing something else for objects
        value = value.toString();
      }
    }

    return value;
  }

  validateValue(value, rule, config) {
    switch (rule) {
      case 'equals': {
        const otherValue = this.getFieldValue(config.field);
        return validator.equals(value, otherValue);
      }
      case 'numeric': {
        return validator.isNumeric(value);
      }
      case 'date': {
        return validator.isDate(value);
      }
      case 'alphanumeric': {
        return validator.isAlphanumeric(value);
      }
      case 'alpha': {
        return validator.isAlpha(value);
      }
      case 'regex': {
        return validator.matches(value, config.pattern);
      }
      case 'max': {
        return value.length <= config.length;
      }
      case 'email': {
        return validator.isEmail(value);
      }
      default: {
        // eslint-disable-next-line no-console
        console.warn(`Unknown validation rule used: '${rule}'`);
        return false;
      }
    }
  }

  /**
   * Validates a give field schema with the values given in the instance.
   *
   * @param fieldSchema
   * @returns {*}
   */
  validateFieldSchema(fieldSchema) {
    return this.validateField(
      fieldSchema.name,
      fieldSchema.validation,
      fieldSchema.leftTitle !== null ? fieldSchema.leftTitle : fieldSchema.title,
      fieldSchema.customValidationMessage
    );
  }

  getMessage(rule, config) {
    let message = '';

    if (typeof config.message === 'string') {
      message = config.message;
    } else {
      // TODO use i18n to get message instead of this switch
      switch (rule) {
        case 'required': {
          message = '{name} is required.';
          break;
        }
        case 'equals': {
          message = '{name} are not equal.';
          break;
        }
        case 'numeric': {
          message = '{name} is not a number.';
          break;
        }
        case 'date': {
          message = '{name} is not a proper date format.';
          break;
        }
        case 'alphanumeric': {
          message = '{name} is not an alpha-numeric value.';
          break;
        }
        case 'alpha': {
          message = '{name} is not only letters.';
          break;
        }
        default: {
          message = '{name} is not a valid value.';
          break;
        }
      }
    }

    if (config.title) {
      message = message.replace('{name}', config.title);
    }

    return message;
  }

  /**
   * Validates a field name with the values given in the instance.
   *
   * @param {string} name
   * @param {object} rules
   * @param {string} title
   * @param {string} overrideMessage
   * @returns {*}
   */
  validateField(name, rules, title, overrideMessage) {
    const response = { valid: true, errors: [] };

    // no validation rules provided (possibly later), keep field as valid for now
    if (!rules) {
      return response;
    }

    const value = this.getFieldValue(name);

    // no required rule given and no value, so skip all other validation
    if (value === '' && rules.required) {
      const config = Object.assign(
        { title: (title !== '') ? title : name },
        rules.required
      );
      const message = overrideMessage || this.getMessage('required', config);
      return {
        valid: false,
        errors: [message],
      };
    }

    Object.entries(rules).forEach((ruleEntry) => {
      const [rule, initConfig] = ruleEntry;
      const config = Object.assign({ title: name }, { title }, initConfig);

      // have done required above as priority
      if (rule === 'required') {
        return;
      }

      const valid = this.validateValue(value, rule, config);

      if (!valid) {
        const message = this.getMessage(rule, config);

        response.valid = false;
        response.errors.push(message);
      }
    });

    if (overrideMessage && !response.valid) {
      response.errors = [overrideMessage];
    }

    return response;
  }
}

export default Validator;
