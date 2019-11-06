/**
 * The parsed markdown comes in with <p>[CHILDREN]</p>, which is invalid HTML
 * once we interpolate a DIV in there.
 * 
 * This also removes whitespace between tags, which chokes the parser.
 * 
 * @param html 
 * @return string
 */
const cleanChildrenTags = (html: string): string => (
    html.replace(
        /(?:<p>\s*)?(\[CHILDREN.*\]*)\s*<\/p>/g,
        (_, childrenTag) => childrenTag.replace(/<\/?em>/g, '_')
    )
);

export default cleanChildrenTags;