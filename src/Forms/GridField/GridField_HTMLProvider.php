<?php

namespace SilverStripe\Forms\GridField;

/**
 * A GridField manipulator that provides HTML for the header/footer rows, or f
 * or before/after the template.
 */
interface GridField_HTMLProvider extends GridFieldComponent
{

    /**
     * Returns a map where the keys are fragment names and the values are
     * pieces of HTML to add to these fragments.
     *
     * Here are 4 built-in fragments: 'header', 'footer', 'before', and
     * 'after', but components may also specify fragments of their own.
     *
     * To specify a new fragment, specify a new fragment by including the
     * text "$DefineFragment(fragmentname)" in the HTML that you return.
     *
     * Fragment names should only contain alphanumerics, -, and _.
     *
     * If you attempt to return HTML for a fragment that doesn't exist, an
     * exception will be thrown when the {@link GridField} is rendered.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField);
}
