import React from 'react';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import { connect } from 'react-redux';
import { Link } from 'react-router';

class Breadcrumb extends SilverStripeComponent {

  getLastCrumb() {
    return this.props.crumbs && this.props.crumbs[this.props.crumbs.length - 1];
  }

  renderBreadcrumbs() {
    if (!this.props.crumbs) {
      return null;
    }

    return this.props.crumbs.slice(0, -1).map((crumb, index) => (
       <li key={index} className="breadcrumb__item">
         <Link
           className="breadcrumb__item-title"
           to={crumb.href}
           onClick={crumb.onClick}
         >{crumb.text}</Link>
        </li>
    )).concat([
      <li
        key={this.props.crumbs.length - 1}
        className="breadcrumb__item"
      />,
    ]);
  }

  renderLastCrumb() {
    const crumb = this.getLastCrumb();
    if (!crumb) {
      return null;
    }

    const iconClassNames = ['breadcrumb__icon'];
    if (crumb.icon) {
      iconClassNames.push(crumb.icon.className);
    }

    return (
      <div className="breadcrumb__item breadcrumb__item--last">
        <h2 className="breadcrumb__item-title">
          {crumb.text}
          {crumb.icon &&
          <span className={iconClassNames.join(' ')} onClick={crumb.icon.action} />
          }
        </h2>
      </div>
    );
  }

  render() {
    return (
      <div className="breadcrumb__container fill-height flexbox-area-grow">
        <ol className="breadcrumb">
          {this.renderBreadcrumbs()}
        </ol>
        {this.renderLastCrumb()}
      </div>
    );
  }
}

Breadcrumb.propTypes = {
  crumbs: React.PropTypes.array,
};

function mapStateToProps(state) {
  return {
    crumbs: state.breadcrumbs,
  };
}

export { Breadcrumb };

export default connect(mapStateToProps)(Breadcrumb);
