// TODO Enable require(*.css) includes once https://github.com/webpack/extract-text-webpack-plugin/issues/179
// is resolved. Included in bundle.scss for now.

require('babel-polyfill');
require('json-js');

// jQuery plugins require that the jQuery object is exposed as a global
// webpack.ProvidePlugin is used to ensure that jQuery and $ are provided to all includes
require('script!../../../thirdparty/jquery/jquery.js');
require('expose?jQuery!jQuery');

// Expose the libraries as globals for other modules to access
// Note that these are order-dependent - earlier items should not depend on later ones
require('expose?DeepFreezeStrict!deep-freeze-strict');
require('expose?React!react');
require('expose?ReactDND!react-dnd');
require('expose?ReactDNDHtml5Backend!react-dnd-html5-backend');
require('expose?Tether!tether');
require('expose?ReactDom!react-dom');
require('expose?Redux!redux');
require('expose?ReactRedux!react-redux');
require('expose?ReduxForm!redux-form');
require('expose?ReduxThunk!redux-thunk');
require('expose?ReactRouter!react-router');
require('expose?ReactRouterRedux!react-router-redux');
require('expose?ReactBootstrap!react-bootstrap-ss');
require('expose?ReactAddonsCssTransitionGroup!react-addons-css-transition-group');
require('expose?ReactAddonsTestUtils!react-addons-test-utils');
require('expose?Page!page.js');
require('expose?BootstrapCollapse!bootstrap/dist/js/umd/collapse.js');
require('expose?validator!validator');
require('expose?ApolloClient!apollo-client');
require('expose?ReactApollo!react-apollo');
require('expose?GraphQLTag!graphql-tag');
require('expose?GraphQLFragments!graphql-fragments');
require('expose?qs!qs');

require('../../../thirdparty/jquery-ondemand/jquery.ondemand.js');
require('../../../thirdparty/jquery-ui/jquery-ui.js');
// require('../../../thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');
require('../../../thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
require('../../../thirdparty/jquery-cookie/jquery.cookie.js');
require('../../../thirdparty/jquery-query/jquery.query.js');
require('../../../thirdparty/jquery-form/jquery.form.js');
require('../../../thirdparty/jquery-notice/jquery.notice.js');
// require('../../../thirdparty/jquery-notice/jquery.notice.css');
require('jquery-sizes/lib/jquery.sizes.js');
require('../../../thirdparty/jstree/jquery.jstree.js');
// require('../../../thirdparty/stree/themes/apple/style.css');
require('../../../thirdparty/jquery-hoverIntent/jquery.hoverIntent.js');
require('../../../thirdparty/jquery-changetracker/lib/jquery.changetracker.js');

require('chosen-js');
