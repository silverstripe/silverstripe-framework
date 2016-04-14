import React from 'react';
import SilverStripeComponent from 'silverstripe-component';
import 'bootstrap-collapse';

class AccordionGroup extends SilverStripeComponent {
  render() {
    const headerID = `${this.props.groupid}_Header`;
    const listID = `${this.props.groupid}_Items`;
    const href = `#${listID}`;
    const groupProps = {
      id: listID,
      'aria-expanded': true,
      className: 'list-group list-group-flush collapse in',
      role: 'tabpanel',
      'aria-labelledby': headerID,
    };
    return (
      <div className="accordion-group">
        <h6 className="accordion-group__title" role="tab" id={headerID}>
          <a data-toggle="collapse" href={href} aria-expanded="true" aria-controls={listID}>
            {this.props.title}
          </a>
        </h6>
        <div {...groupProps}>
          {this.props.children}
        </div>
      </div>
    );
  }
}
export default AccordionGroup;
