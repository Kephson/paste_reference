/**
 * JavaScript to add helper functions
 * @exports @ehaerer/paste-reference/helper.js
 */
class Helper {
    /**
     * @returns {String}
     */
    decodeHtmlspecialChars(text) {
        const map = {
            '&amp;': '&',
            '&#038;': "&",
            '&lt;': '<',
            '&gt;': '>',
            '&quot;': '"',
            '&#039;': "'",
            '&#8217;': "’",
            '&#8216;': "‘",
            '&#8211;': "–",
            '&#8212;': "—",
            '&#8230;': "…",
            '&#8221;': '”'
        };

        return text.replace(/\&[\w\d\#]{2,5}\;/g, function (m) {
            return map[m];
        });
    }
}

export default new Helper();
