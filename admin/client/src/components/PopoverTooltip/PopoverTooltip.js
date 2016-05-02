import $ from 'jQuery';
import 'tetherWrapper';
import 'bootstrap-popover';
import 'bootstrap-tooltip';
import React from 'react';
import ReactDOM from 'react-dom';

class PopoverTooltip extends React.Component {
  componentDidMount() {
    const el = ReactDOM.findDOMNode(this);

    // Enable BS Popovers and tooltips
    $(el).find('[data-toggle="tooltip"]').tooltip();
    $(el).find('[data-toggle="popover"]').popover({ html: true });

    // Accessibility support for popover focus (requires anchor element)
    $(el).find('.popover-dismiss').popover({ trigger: 'focus' });
  }

  // TODO allow for custom popover/tooltip trigger
  // eg. (classes, contents, data-toggle, placement, icon and text)
  // Add the possibility to use FormAction component as a trigger
  render() {
    return (
      <a
        tabIndex="0"
        role="button"
        data-container="body"
        className="btn btn-link btn--options"
        title=""
        data-toggle="popover"
        data-trigger="focus"
        data-placement="top"
        data-content={this.props.content}
      >
        <i className="font-icon-dot-3"></i>
      </a>
    );
  }
}

PopoverTooltip.propTypes = {
  content: React.PropTypes.string.isRequired,
};

export default PopoverTooltip;
