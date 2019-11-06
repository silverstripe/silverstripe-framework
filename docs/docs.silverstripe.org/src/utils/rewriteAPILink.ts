import useCurrentVersion from '../hooks/useCurrentVersion';

const rewriteAPILink = (link: string): string => {
    const version = useCurrentVersion().replace(/x$/, '');
    const match = link.match(/api\:(.*)/);
    if (!match) {
        console.error(`Unable to resolve api link ${link}!`);
        return link;
    }

    return `https://api.silverstripe.org/search/lookup?q=${match[1]}&version=${version}`;
};

export default rewriteAPILink;