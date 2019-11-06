import React, { StatelessComponent, ReactElement } from 'react';

export enum CalloutType {
    hint = "hint",
    notice = "notice",
    info = "info",
    warning = "warning",
    alert = "alert",
}

interface CalloutProps {
    type: CalloutType;
}

const lookup = {
    hint: 'success',
    notice: 'warning',
    info: 'info',
    warning: 'warning',
    alert: 'danger',
}
const Callout: StatelessComponent<CalloutProps> = ({ type, children }): ReactElement => {
    const alertClass = lookup[type] || 'info';

    return (
        <div className={`callout-block callout-block-${alertClass}`}>
            <div className="content">
                {children}
            </div>
        </div>
    );
};

export default Callout;
