import CompositeField from 'components/CompositeField/CompositeField';
import fieldHolder from 'components/FieldHolder/FieldHolder';

class FieldGroup extends CompositeField {
  getClassName() {
    return `field-group-component ${super.getClassName()}`;
  }
}

// Field group is essentially a composite field, but wrapped with a typical field holder
export { FieldGroup };

export default fieldHolder(FieldGroup);
