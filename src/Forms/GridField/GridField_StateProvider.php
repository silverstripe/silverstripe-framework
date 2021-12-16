<?php

namespace SilverStripe\Forms\GridField;

/**
 * A GridField component that provides state, notably default state.
 *
 * Implementation of this interface is optional; without it, no default state is assumed.
 * The benefit of default state is that it won't be included in URLs, keeping URLs tidier.
 */
interface GridField_StateProvider extends GridFieldComponent
{
    /**
     * Initialise the default state in the given GridState_Data
     *
     * We recommend that you call $data->initDefaults() to do this.
     *
     * @param $data The top-level state object
     */
    public function initDefaultState(GridState_Data $data): void;
}
