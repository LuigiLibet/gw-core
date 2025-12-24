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
   * PHP Serialization helpers for Repeater
   * Simple PHP unserialize for arrays of objects with string values
   * @param {string} serialized - Serialized string
   * @returns {Array} Parsed array
   */
  const phpUnserialize = (serialized) => {
    if (!serialized || typeof serialized !== 'string' || serialized.trim() === '') {
      return [];
    }
    try {
      // Try JSON first (more common and easier)
      if (serialized.trim().startsWith('[') || serialized.trim().startsWith('{')) {
        return JSON.parse(serialized);
      }
      // PHP serialized format: a:N:{...}
      if (!serialized.startsWith('a:')) {
        return [];
      }
      // Simple parser for basic PHP serialized arrays
      // Format: a:N:{i:0;a:M:{s:L:"key";s:L:"value";}...}
      const result = [];
      let pos = 2; // Skip "a:"
      const countMatch = serialized.match(/^a:(\d+):/);
      if (!countMatch) return [];
      const count = parseInt(countMatch[1], 10);
      pos = countMatch[0].length; // Position after "a:N:"

      for (let i = 0; i < count; i++) {
        // Skip index: "i:N;" or "s:L:"key";"
        if (serialized[pos] === 'i') {
          pos = serialized.indexOf(';', pos) + 1;
        } else if (serialized[pos] === 's') {
          pos = serialized.indexOf(';', pos) + 1;
        }

        // Parse array: "a:M:{"
        if (serialized[pos] !== 'a') break;
        const arrMatch = serialized.substring(pos).match(/^a:(\d+):\{/);
        if (!arrMatch) break;
        pos += arrMatch[0].length;

        const item = {};
        const itemCount = parseInt(arrMatch[1], 10);

        for (let j = 0; j < itemCount; j++) {
          // Parse key: "s:L:"key";"
          const keyMatch = serialized.substring(pos).match(/^s:(\d+):"([^"]+)";/);
          if (!keyMatch) break;
          const key = keyMatch[2];
          pos += keyMatch[0].length;

          // Parse value: "s:L:"value";" or "i:N;" or "b:1;"
          let value;
          if (serialized[pos] === 's') {
            const valMatch = serialized.substring(pos).match(/^s:(\d+):"([^"]*)";/);
            if (valMatch) {
              value = valMatch[2];
              pos += valMatch[0].length;
            } else {
              break;
            }
          } else if (serialized[pos] === 'i') {
            const valMatch = serialized.substring(pos).match(/^i:(-?\d+);/);
            if (valMatch) {
              value = parseInt(valMatch[1], 10);
              pos += valMatch[0].length;
            } else {
              break;
            }
          } else if (serialized[pos] === 'b') {
            const valMatch = serialized.substring(pos).match(/^b:([01]);/);
            if (valMatch) {
              value = valMatch[1] === '1';
              pos += valMatch[0].length;
            } else {
              break;
            }
          } else {
            break;
          }
          item[key] = value;
        }

        // Skip closing brace
        if (serialized[pos] === '}') pos++;
        result.push(item);
      }

      return result;
    } catch (e) {
      console.error('[GW Custom Blocks] Error unserializing PHP data:', e);
      return [];
    }
  };

  /**
   * Simple PHP serialize for arrays of objects
   * @param {Array} array - Array to serialize
   * @returns {string} Serialized string
   */
  const phpSerialize = (array) => {
    if (!Array.isArray(array) || array.length === 0) {
      return '';
    }
    try {
      // Use JSON for simplicity and reliability
      // PHP can unserialize JSON arrays easily, and it's more maintainable
      return JSON.stringify(array);
    } catch (e) {
      console.error('[GW Custom Blocks] Error serializing data:', e);
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

