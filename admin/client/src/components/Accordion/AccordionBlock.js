// TODO move list-group to its own component

import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import 'bootstrap-collapse';

class AccordionBlock extends SilverStripeComponent {
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
      <div className="accordion__block">
        <a className="accordion__title"
          data-toggle="collapse"
          href={href}
          aria-expanded="true"
          aria-controls={listID}
          id={headerID}
          role="tab"
        >{this.props.title}
        </a>
        <div {...groupProps}>
          {this.props.children}
        </div>
      </div>
    );
  }
}
export default AccordionBlock;
