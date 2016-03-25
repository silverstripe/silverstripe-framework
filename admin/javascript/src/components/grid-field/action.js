import React from 'react';
import SilverStripeComponent from 'silverstripe-component';

class GridFieldActionComponent extends SilverStripeComponent {
    constructor(props) {
        super(props);
        
        this.handleClick = this.handleClick.bind(this);
    }

    render() {
        return (
            <button 
                className={`grid-field-action-component font-icon-${this.props.icon}`} 
                onClick={this.handleClick} />
        );
    }

    handleClick(event) {
        this.props.handleClick(event);
    }
}

GridFieldActionComponent.PropTypes = {
    handleClick: React.PropTypes.func.isRequired
}

export default GridFieldActionComponent;
