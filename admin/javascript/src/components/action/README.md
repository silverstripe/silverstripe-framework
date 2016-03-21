# Action

This component is used to display a button which is linked to an action.

## Props

### handleClick (function)

The handler for when a button is clicked, is passed the click event as the only argument.

### text (string)

The text to be shown in the button.

### type (string)

The type of button to be shown, adds a class to the button.

Accepted values are:
 * 'danger'
 * 'success'
 * 'primary'
 * 'link'
 * 'secondary'
 * 'complete'

### icon (string)

The icon to be used on the button, adds font-icon-{this.props.icon} class to the button. See available icons [here](../../../../fonts/incon-reference.html).

### loading (boolean)

If true, replaces the text/icon with a loading icon. 

### disabled (boolean)

If true, gives the button a visually disabled state and disables click events.
