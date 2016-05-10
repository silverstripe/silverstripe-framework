import React from 'react';
import { connect } from 'react-redux';
import SilverStripeComponent from 'lib/SilverStripeComponent';

export class Breadcrumb extends SilverStripeComponent {

  render() {
    return (
      <ol className="breadcrumb">
        {this.getBreadcrumbs()}
      </ol>
    );
  }

  getBreadcrumbs() {
    if (typeof this.props.crumbs === 'undefined') {
      return null;
    }

    return [].concat(
     this.props.crumbs.slice(0, -1).map((crumb, index) => [
       <li className="breadcrumb__item">
         <a key={index} className="breadcrumb__item-title" href={crumb.href}>{crumb.text}</a>
        </li>,
     ]),
       this.props.crumbs.slice(-1).map((crumb, index) => [
         <li className="breadcrumb__item breadcrumb__item--last">
           <h2 className="breadcrumb__item-title breadcrumb__item-title--last" key={index}>
             {crumb.text}
           </h2>
         </li>,
       ])
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

export default connect(mapStateToProps)(Breadcrumb);
