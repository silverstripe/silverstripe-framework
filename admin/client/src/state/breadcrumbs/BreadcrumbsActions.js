import ACTION_TYPES from './BreadcrumbsActionTypes';

/**
 * Set selected changeset item
 *
 * @param {array} List of breadcrumbs to add, each of which is an object
 * with a 'text' and optional 'href' property.
 * @return {object}
 */
export function setBreadcrumbs(breadcrumbs) {
  return {
    type: ACTION_TYPES.SET_BREADCRUMBS,
    payload: { breadcrumbs },
  };
}
