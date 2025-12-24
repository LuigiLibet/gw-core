/* global GW_CUSTOM_BLOCKS, wp, GWCBlocks */
(function () {
  'use strict';
  
  // Initial validation
  if (typeof wp === 'undefined' || !wp.blocks || !GW_CUSTOM_BLOCKS || !Array.isArray(GW_CUSTOM_BLOCKS.blocks)) {
    return;
  }

  // Wait for all modules to be loaded
  // Check if all required modules are available
  const checkModules = () => {
    return (
      typeof GWCBlocks !== 'undefined' &&
      GWCBlocks.utils &&
      GWCBlocks.deps &&
      GWCBlocks.controls &&
      GWCBlocks.controls.renderControl &&
      GWCBlocks.controls.ControlForField &&
      GWCBlocks.ui &&
      GWCBlocks.ui.renderToolbar &&
      GWCBlocks.ui.renderInspector &&
      GWCBlocks.registry &&
      GWCBlocks.registry.registerBlocks
    );
  };

  // Try to initialize immediately
  if (checkModules()) {
    GWCBlocks.registry.registerBlocks();
    return;
  }

  // If modules aren't ready, wait a bit and try again
  // This handles cases where scripts load in unexpected order
  let attempts = 0;
  const maxAttempts = 50; // 5 seconds max wait
  const checkInterval = setInterval(() => {
    attempts++;
    if (checkModules()) {
      clearInterval(checkInterval);
      GWCBlocks.registry.registerBlocks();
    } else if (attempts >= maxAttempts) {
      clearInterval(checkInterval);
      console.error('[GW Custom Blocks] Failed to load all required modules. Make sure all script dependencies are properly enqueued.');
    }
  }, 100);
})();
