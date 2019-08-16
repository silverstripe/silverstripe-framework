const buildBreadcrumbs = (slug) => {
    let breadcrumbs = [];
    let slugParts = slug.split('/');
    slugParts.pop();

    while (slugParts.length) {
      breadcrumbs.push(slugParts.join('/'));
      slugParts.pop();
    }
    breadcrumbs.reverse();
    breadcrumbs[0] = '/';

    return breadcrumbs;
}

module.exports = buildBreadcrumbs;