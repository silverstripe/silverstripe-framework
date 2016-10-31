/**
 * Finds the field with matching id from the schema or state, this is mainly for dealing with
 * schema's deep nesting of fields.
 *
 * @param fields
 * @param name
 * @returns {object|undefined}
 */
export function findField(fields, name) {
  let result = null;
  if (!fields) {
    return result;
  }

  result = fields.find(field => field.name === name);

  for (const field of fields) {
    if (result) {
      break;
    }
    result = findField(field.children, name);
  }
  return result;
}

/**
 * Gets all field values based on the assigned form schema, from prop state.
 *
 * @returns {Object}
 */
export default function schemaFieldValues(schema, state) {
  // using state is more efficient and has the same fields, fallback to nested schema
  if (!state) {
    return {};
  }

  return state.fields
    .reduce((prev, curr) => {
      const match = findField(schema.fields, curr.name);

      if (!match) {
        return prev;
      }

      // Skip non-data fields
      if (match.type === 'Structural' || match.readOnly === true) {
        return prev;
      }

      return Object.assign({}, prev, {
        [match.name]: curr.value,
      });
    }, {});
}
