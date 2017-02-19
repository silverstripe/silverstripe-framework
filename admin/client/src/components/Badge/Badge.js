import React, { PropTypes } from 'react';

const Badge = ({ status, message, className }) => {
  if (!status) {
    return null;
  }
  return (
    <span className={`${className || ''} label label-${status} label-pill`}>
      {message}
    </span>
  );
};

Badge.propTypes = {
  message: PropTypes.node,
  status: PropTypes.oneOf([
    'default',
    'info',
    'success',
    'warning',
    'danger',
    'primary',
    'secondary',
  ]),
  className: PropTypes.string,
};

export default Badge;
