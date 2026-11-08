/**
 * TYPO3 v13 has no language label modules: reads TYPO3.lang, which
 * ModifyPageLayoutContentListener fills, and replaces {placeholders}.
 *
 * @exports @ehaerer/paste-reference/labels
 */
export default {
  /**
   * @param {string} key
   * @param {Object} [placeholders]
   * @returns {string}
   */
  get(key, placeholders = {}) {
    return (TYPO3.lang[key] ?? key).replace(/\{(\w+)}/g, (match, name) => placeholders[name] ?? match);
  },
};
