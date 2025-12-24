/* global wp, GWCBlocks */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps || !GWCBlocks.controls) {
    return;
  }

  const { BlockControls, InspectorControls, PanelBody, TabPanel } = GWCBlocks.deps;
  const { __ } = GWCBlocks.deps;
  const { ToolbarGroup, ToolbarButton } = GWCBlocks.deps;
  const { ControlForField } = GWCBlocks.controls;

  /**
   * Render toolbar for block
   * @param {Object} b - Block configuration
   * @param {Object} attributes - Block attributes
   * @param {Function} setAttributes - Set attributes function
   * @returns {Object|null} React element or null
   */
  const renderToolbar = (b, attributes, setAttributes) => {
    const ui = b.ui || {};
    const groups = Array.isArray(ui.toolbar) ? ui.toolbar : [];
    if (!groups.length || !BlockControls) return null;

    const groupEls = groups.map((g, idx) => {
      const ctrls = Array.isArray(g.controls) ? g.controls : [];
      const children = ctrls.map((c, i) => {
        const type = (c.type || 'button').toLowerCase();
        const label = c.label || '';
        const icon = c.icon || null;
        const attr = c.attribute || c.attr;
        const value = c.value;
        const isActive = (() => {
          if (typeof c.isActive === 'function') return !!c.isActive(attributes);
          if (type === 'toggle') return !!attributes[attr];
          if (type === 'value') return attributes[attr] === value;
          return !!c.active;
        })();
        const onClick = () => {
          if (typeof c.onClick === 'function') { c.onClick({ attributes, setAttributes }); return; }
          if (type === 'toggle') {
            setAttributes({ [attr]: !attributes[attr] });
          } else if (type === 'value') {
            setAttributes({ [attr]: value });
          } else {
            // default just toggles a boolean attr
            if (attr) setAttributes({ [attr]: !attributes[attr] });
          }
        };
        return wp.element.createElement(ToolbarButton, { key: i, icon, label, isPressed: isActive, onClick });
      });
      return wp.element.createElement(ToolbarGroup, { key: idx, label: g.title || g.label || undefined }, children);
    });

    return wp.element.createElement(BlockControls, {}, groupEls);
  };

  /**
   * Render inspector controls for block
   * @param {Object} b - Block configuration
   * @param {Object} attributes - Block attributes
   * @param {Function} setAttributes - Set attributes function
   * @param {string} postType - Post type
   * @returns {Object} React element
   */
  const renderInspector = (b, attributes, setAttributes, postType) => {
    const fieldKeys = Object.keys(b.fields || {});
    const hasTabs = b.ui && Array.isArray(b.ui.tabs) && b.ui.tabs.length > 0;
    
    if (hasTabs) {
      const tabs = b.ui.tabs.map((t) => ({ name: t.name, title: t.title || t.name }));
      return wp.element.createElement(InspectorControls, {},
        wp.element.createElement(TabPanel, {
          className: 'gw-custom-blocks-tabpanel',
          activeClass: 'is-active',
          tabs,
        }, (tab) => {
          const spec = (b.ui.tabs || []).find((t) => t.name === tab.name) || { fields: [] };
          const keys = Array.isArray(spec.fields) ? spec.fields : [];
          return wp.element.createElement(PanelBody, { title: tab.title },
            keys.map((k) => b.fields && b.fields[k] ? wp.element.createElement(ControlForField, { b, fieldKey: k, field: b.fields[k], attributes, setAttributes, postType }) : null)
          );
        })
      );
    } else {
      return wp.element.createElement(InspectorControls, {},
        wp.element.createElement(PanelBody, { title: __('Settings') },
          fieldKeys.map((key) => wp.element.createElement(ControlForField, { b, fieldKey: key, field: b.fields[key], attributes, setAttributes, postType }))
        )
      );
    }
  };

  // Export functions
  GWCBlocks.ui = {
    renderToolbar,
    renderInspector
  };
})();

