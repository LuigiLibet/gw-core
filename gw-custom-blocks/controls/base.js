/* global wp, GWCBlocks */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps || !GWCBlocks.utils || !GWCBlocks.controls) {
    return;
  }

  const {
    TextControl,
    TextareaControl,
    RangeControl,
    ToggleControl,
    SelectControl,
    ColorPalette,
  } = GWCBlocks.deps;
  const { getEditorColors } = GWCBlocks.utils;
  const { useEntityProp } = GWCBlocks.deps;

  // Get all control components
  const PostSelectControl = GWCBlocks.controls.PostSelect;
  const TermSelectControl = GWCBlocks.controls.TermSelect;
  const GalleryControl = GWCBlocks.controls.Gallery;
  const ImageControl = GWCBlocks.controls.Image;
  const RepeaterControl = GWCBlocks.controls.Repeater;
  const IconPickerControl = GWCBlocks.controls.IconPicker;

  /**
   * Render control based on field type
   * @param {string} key - Field key
   * @param {Object} field - Field configuration
   * @param {*} value - Current value
   * @param {Function} onChange - Change handler
   * @returns {Object} React element
   */
  const renderControl = (key, field, value, onChange) => {
    const label = field.label || key;
    const control = (field.control || 'text').toLowerCase();

    switch (control) {
      case 'post_select':
        return wp.element.createElement(PostSelectControl, {
          label,
          value: value,
          onChange,
          field
        });
      case 'term_select':
        return wp.element.createElement(TermSelectControl, {
          label,
          value: value,
          onChange,
          field
        });
      case 'textarea':
        return wp.element.createElement(TextareaControl, { label, value: value || '', onChange });
      case 'range':
        return wp.element.createElement(RangeControl, {
          label,
          value: typeof value === 'number' ? value : (field.default || 0),
          onChange,
          min: field.min != null ? field.min : 0,
          max: field.max != null ? field.max : 100,
          step: field.step != null ? field.step : 1,
        });
      case 'number':
        // Use TextControl type number to keep deps small
        return wp.element.createElement(TextControl, {
          label,
          type: 'number',
          value: value != null ? value : (field.default != null ? field.default : ''),
          onChange: (v) => onChange(v === '' ? '' : Number(v)),
        });
      case 'toggle':
        return wp.element.createElement(ToggleControl, {
          label,
          checked: !!value,
          onChange: (v) => onChange(!!v),
        });
      case 'select': {
        const options = (field.options || []).map((opt) => {
          if (typeof opt === 'object' && opt) return { label: opt.label || String(opt.value), value: opt.value };
          return { label: String(opt), value: opt };
        });
        // Convert value to proper type for integer fields
        const isIntegerType = field.type === 'integer' || field.type === 'number';
        const normalizedValue = value != null ? value : (field.default != null ? field.default : (isIntegerType ? 0 : ''));
        return wp.element.createElement(SelectControl, {
          label,
          value: normalizedValue,
          options,
          onChange: (v) => {
            // Convert to integer if field type is integer or number
            const converted = isIntegerType ? parseInt(v, 10) : v;
            onChange(converted);
          },
        });
      }
      case 'color': {
        const colors = getEditorColors();
        return wp.element.createElement('div', { className: 'gw-custom-blocks-color-control' },
          wp.element.createElement('div', { style: { marginBottom: 8 } }, label),
          wp.element.createElement(ColorPalette, { colors, value: value || '', onChange })
        );
      }
      case 'gallery': {
        return wp.element.createElement(GalleryControl, { label, value: value || '', onChange, field });
      }
      case 'image': {
        return wp.element.createElement(ImageControl, { label, value: value || '', onChange, field });
      }
      case 'repeater': {
        return wp.element.createElement(RepeaterControl, {
          label,
          value: value || '',
          onChange,
          field,
          renderControlFn: renderControl
        });
      }
      case 'icon_picker': {
        return wp.element.createElement(IconPickerControl, { label, value: value || '', onChange, field });
      }
      case 'text':
      default:
        return wp.element.createElement(TextControl, { label, value: value != null ? value : (field.default || ''), onChange });
    }
  };

  /**
   * Control wrapper component for field
   * @param {Object} props - Component props
   * @param {Object} props.b - Block configuration
   * @param {string} props.fieldKey - Field key
   * @param {Object} props.field - Field configuration
   * @param {Object} props.attributes - Block attributes
   * @param {Function} props.setAttributes - Set attributes function
   * @param {string} props.postType - Post type
   * @returns {Object} React element
   */
  const ControlForField = (props) => {
    const { b, fieldKey, field, attributes, setAttributes, postType } = props;
    const isMeta = field && typeof field.source === 'string' && field.source.toLowerCase() === 'meta';
    let value, onChange;
    if (isMeta && useEntityProp && postType) {
      const metaKey = field.metaKey || fieldKey;
      const entity = useEntityProp('postType', postType, 'meta');
      if (Array.isArray(entity) && entity.length >= 2) {
        const meta = entity[0];
        const setMeta = entity[1];
        value = meta ? meta[metaKey] : undefined;
        onChange = (v) => {
          // Cast common types
          if ((field.control || '').toLowerCase() === 'number' || (field.type || '') === 'number' || (field.control || '').toLowerCase() === 'range') {
            v = (v === '' || v === null || typeof v === 'undefined') ? '' : Number(v);
          } else if ((field.control || '').toLowerCase() === 'toggle' || (field.type || '') === 'boolean') {
            v = !!v;
          }
          setMeta(Object.assign({}, meta, { [metaKey]: v }));
        };
      }
    }
    if (!onChange) {
      value = attributes[fieldKey];
      onChange = (v) => setAttributes({ [fieldKey]: v });
    }
    return wp.element.createElement('div', { key: fieldKey, style: { marginBottom: 12 } },
      renderControl(fieldKey, field, value, onChange)
    );
  };

  // Export functions
  GWCBlocks.controls.renderControl = renderControl;
  GWCBlocks.controls.ControlForField = ControlForField;
})();

