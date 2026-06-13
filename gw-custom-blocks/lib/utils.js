/* global wp */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined') {
    window.GWCBlocks = {};
  }

  /**
   * Get editor colors from WordPress settings
   * @returns {Array} Array of color objects
   */
  const getEditorColors = () => {
    try {
      const settings = (wp.data && wp.data.select('core/block-editor').getSettings()) || {};
      return settings.colors || [];
    } catch (e) {
      return [];
    }
  };

  /**
   * Repeater value (de)serialization.
   *
   * Repeater data is stored as a JSON string (see phpSerialize below); the PHP
   * side reads it with gw_get_repeater_items(). These helpers keep the JSON name
   * `phpUnserialize`/`phpSerialize` for backward compatibility with importers.
   *
   * @param {string} serialized - JSON string
   * @returns {Array} Parsed array (empty on invalid/empty input)
   */
  const phpUnserialize = (serialized) => {
    if (!serialized || typeof serialized !== 'string') {
      return [];
    }
    const trimmed = serialized.trim();
    if (trimmed === '' || (trimmed[0] !== '[' && trimmed[0] !== '{')) {
      return [];
    }
    try {
      const parsed = JSON.parse(trimmed);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      console.error('[GW Custom Blocks] Error parsing repeater data:', e);
      return [];
    }
  };

  /**
   * Serialize an array of repeater items to a JSON string.
   * @param {Array} array - Array to serialize
   * @returns {string} JSON string ('' when empty/invalid)
   */
  const phpSerialize = (array) => {
    if (!Array.isArray(array) || array.length === 0) {
      return '';
    }
    try {
      return JSON.stringify(array);
    } catch (e) {
      console.error('[GW Custom Blocks] Error serializing repeater data:', e);
      return '';
    }
  };

  // Export utils
  GWCBlocks.utils = {
    getEditorColors,
    phpUnserialize,
    phpSerialize
  };
})();

