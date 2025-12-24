/* global wp, GWCBlocks, GW_CUSTOM_BLOCKS */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps || !GWCBlocks.ui || !GWCBlocks.controls) {
    return;
  }

  // Wait for GW_CUSTOM_BLOCKS to be available
  if (typeof GW_CUSTOM_BLOCKS === 'undefined' || !GW_CUSTOM_BLOCKS || !Array.isArray(GW_CUSTOM_BLOCKS.blocks)) {
    // Wait a bit and try again
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max wait
    const checkInterval = setInterval(() => {
      attempts++;
      if (typeof GW_CUSTOM_BLOCKS !== 'undefined' && GW_CUSTOM_BLOCKS && Array.isArray(GW_CUSTOM_BLOCKS.blocks)) {
        clearInterval(checkInterval);
        // Re-run the initialization
        initializeRegistry();
      } else if (attempts >= maxAttempts) {
        clearInterval(checkInterval);
        console.error('[GW Custom Blocks] GW_CUSTOM_BLOCKS not available after waiting.');
      }
    }, 100);
    return;
  }

  initializeRegistry();

  function initializeRegistry() {
    if (!GW_CUSTOM_BLOCKS || !Array.isArray(GW_CUSTOM_BLOCKS.blocks)) {
      return;
    }

    const { registerBlockType } = GWCBlocks.deps;
    const { useBlockProps, InnerBlocks, ServerSideRender } = GWCBlocks.deps;
    const { __ } = GWCBlocks.deps;
    const { dataSelect } = GWCBlocks.deps;
    const { renderToolbar, renderInspector } = GWCBlocks.ui;

    /**
     * Register all custom blocks
     */
    const registerBlocks = () => {
      GW_CUSTOM_BLOCKS.blocks.forEach((b) => {
        try {
          registerBlockType(b.name, {
            apiVersion: 2,
            title: b.title || b.name,
            icon: b.icon || 'block-default',
            category: b.category || 'widgets',
            supports: b.supports || {},
            attributes: b.attributes || {},
            edit: (props) => {
              const { attributes, setAttributes } = props;
              const blockProps = useBlockProps();
              const postType = dataSelect ? (dataSelect('core/editor') && dataSelect('core/editor').getCurrentPostType()) : null;

              const inspector = renderInspector(b, attributes, setAttributes, postType);
              const toolbar = renderToolbar(b, attributes, setAttributes);

              // Special handling for link-wrapper: use InnerBlocks for editing
              if (b.name === 'gw/link-wrapper') {
                const wrapperProps = useBlockProps({
                  className: 'link_wrapper' + (attributes.className ? ' ' + attributes.className : ''),
                  id: attributes.anchor || undefined,
                });

                return wp.element.createElement(wp.element.Fragment, {},
                  toolbar,
                  inspector,
                  wp.element.createElement('div', wrapperProps,
                    wp.element.createElement(InnerBlocks)
                  )
                );
              }

              const preview = wp.element.createElement('div', blockProps,
                ServerSideRender ? wp.element.createElement(ServerSideRender, {
                  block: b.name,
                  attributes,
                }) : wp.element.createElement('div', {}, __('Preview not available'))
              );

              return wp.element.createElement(wp.element.Fragment, {}, toolbar, inspector, preview);
            },
            save: () => {
              if (b.supports && (b.supports.innerBlocks || b.supports.__experimentalInnerBlocks)) {
                return wp.element.createElement(InnerBlocks.Content);
              }
              return null;
            },
          });
        } catch (e) {
          // eslint-disable-next-line no-console
          console.error('[GW Custom Blocks] Failed to register block', b && b.name, e);
        }
      });
    };

    // Export function
    GWCBlocks.registry = {
      registerBlocks
    };
  }
})();

