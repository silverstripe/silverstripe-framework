<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use ReflectionClass;
use SilverStripe\Core\Injector\Injectable;

/**
 * A helper class for managing {@link Form} and {@link FormField} HTML template
 * output.
 *
 * This primarily exists to maintain backwards compatibility between Form and
 * FormField template changes since developers may rely on specific HTML output
 * in their applications. Any core changes to templates (such as changing ID's)
 * may have the potential to silently prevent websites from working.
 *
 * To provide a form with a custom FormTemplateHelper use the following snippet:
 *
 * <code>
 * $form->setTemplateHelper('ClassName');
 * </code>
 *
 * Globally, the FormTemplateHelper can be set via the {@link Injector} API.
 */
class FormTemplateHelper
{
    use Injectable;

    /**
     * @param Form $form
     *
     * @return string
     */
    public function generateFormID($form)
    {
        if ($id = $form->getHTMLID()) {
            return Convert::raw2htmlid($id);
        }

        $reflection = new ReflectionClass($form);
        $shortName = str_replace(array('.', '/'), '', $form->getName());
        return Convert::raw2htmlid($reflection->getShortName() . '_' . $shortName);
    }

    /**
     * @param FormField $field
     *
     * @return string
     */
    public function generateFieldHolderID($field)
    {
        return $this->generateFieldID($field) . '_Holder';
    }

    /**
     * Generate the field ID value
     *
     * @param FormField $field
     * @return string
     */
    public function generateFieldID($field)
    {
        if ($form = $field->getForm()) {
            return sprintf(
                "%s_%s",
                $this->generateFormID($form),
                Convert::raw2htmlid($field->getName())
            );
        }

        return Convert::raw2htmlid($field->getName());
    }
}
