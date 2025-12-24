/* global wp, GWCBlocks, GW_CUSTOM_BLOCKS */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps || !GWCBlocks.utils) {
    return;
  }

  if (!GWCBlocks.controls) {
    GWCBlocks.controls = {};
  }

  const { useState, useEffect } = GWCBlocks.deps;
  const { Button } = GWCBlocks.deps;
  const { __ } = GWCBlocks.deps;
  const { phpUnserialize, phpSerialize } = GWCBlocks.utils;

  // Repeater Control Component with Accordion and Drag and Drop
  const RepeaterControl = ({ label, value, onChange, field, renderControlFn }) => {
    const subFields = field.subFields || field.fields || {};
    const [items, setItems] = useState([]);
    const [openItems, setOpenItems] = useState(new Set());
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    // Parse serialized value to array
    useEffect(() => {
      const parsed = phpUnserialize(value || '');
      setItems(Array.isArray(parsed) ? parsed : []);
      // Open first item by default if none are open
      if (parsed.length > 0 && openItems.size === 0) {
        setOpenItems(new Set([0]));
      }
    }, [value]);

    // Update serialized value when items change
    const updateValue = (newItems) => {
      const serialized = phpSerialize(newItems);
      onChange(serialized);
    };

    // Create default item with default values from subFields
    const createDefaultItem = () => {
      const item = {};
      Object.keys(subFields).forEach(key => {
        const subField = subFields[key];
        if (subField.default !== undefined) {
          item[key] = subField.default;
        } else {
          // Set empty defaults based on type
          if (subField.type === 'boolean') {
            item[key] = false;
          } else if (subField.type === 'number' || subField.type === 'integer') {
            item[key] = 0;
          } else {
            item[key] = '';
          }
        }
      });
      return item;
    };

    const addItem = () => {
      const newItem = createDefaultItem();
      const newItems = [...items, newItem];
      setItems(newItems);
      updateValue(newItems);
      // Open the new item
      setOpenItems(new Set([...openItems, newItems.length - 1]));
    };

    const removeItem = (index) => {
      const confirmMessage = __('Are you sure you want to delete this item?', 'gwblueprint') || 'Are you sure you want to delete this item?';
      if (window.confirm(confirmMessage)) {
        const newItems = items.filter((_, i) => i !== index);
        setItems(newItems);
        updateValue(newItems);
        // Update open items set
        const newOpenItems = new Set();
        openItems.forEach(idx => {
          if (idx < index) {
            newOpenItems.add(idx);
          } else if (idx > index) {
            newOpenItems.add(idx - 1);
          }
        });
        setOpenItems(newOpenItems);
      }
    };

    const toggleItem = (index) => {
      const newOpenItems = new Set(openItems);
      if (newOpenItems.has(index)) {
        newOpenItems.delete(index);
      } else {
        newOpenItems.add(index);
      }
      setOpenItems(newOpenItems);
    };

    const updateItemField = (itemIndex, fieldKey, fieldValue) => {
      const newItems = [...items];
      if (!newItems[itemIndex]) {
        newItems[itemIndex] = {};
      }
      newItems[itemIndex][fieldKey] = fieldValue;
      setItems(newItems);
      updateValue(newItems);
    };

    // Drag and drop handlers
    const handleDragStart = (e, index) => {
      setDraggedIndex(index);
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/html', e.target);
    };

    const handleDragOver = (e, index) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      setDragOverIndex(index);
    };

    const handleDragLeave = () => {
      setDragOverIndex(null);
    };

    const handleDrop = (e, dropIndex) => {
      e.preventDefault();
      if (draggedIndex === null || draggedIndex === dropIndex) {
        setDraggedIndex(null);
        setDragOverIndex(null);
        return;
      }

      const newItems = [...items];
      const draggedItem = newItems[draggedIndex];
      newItems.splice(draggedIndex, 1);
      newItems.splice(dropIndex, 0, draggedItem);
      setItems(newItems);
      updateValue(newItems);

      // Update open items indices
      const newOpenItems = new Set();
      const wasDraggedOpen = openItems.has(draggedIndex);
      openItems.forEach(idx => {
        if (idx === draggedIndex) {
          // Skip, will add dropIndex if was open
        } else if (idx < draggedIndex && idx < dropIndex) {
          newOpenItems.add(idx);
        } else if (idx > draggedIndex && idx > dropIndex) {
          newOpenItems.add(idx);
        } else if (idx < draggedIndex && idx >= dropIndex) {
          newOpenItems.add(idx + 1);
        } else if (idx > draggedIndex && idx <= dropIndex) {
          newOpenItems.add(idx - 1);
        }
      });
      if (wasDraggedOpen) {
        newOpenItems.add(dropIndex);
      }
      setOpenItems(newOpenItems);

      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    const handleDragEnd = () => {
      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    return wp.element.createElement('div', { className: 'gw-custom-blocks-repeater-control' },
      wp.element.createElement('div', { style: { marginBottom: 12, fontWeight: 600 } }, label),
      items.length > 0 && wp.element.createElement('div', { className: 'gw-custom-blocks-repeater-items' },
        items.map((item, index) => {
          const isOpen = openItems.has(index);
          const isDragging = draggedIndex === index;
          const isDragOver = dragOverIndex === index;
          return wp.element.createElement('div', {
            key: index,
            className: `gw-custom-blocks-repeater-item ${isOpen ? 'is-open' : ''} ${isDragging ? 'is-dragging' : ''} ${isDragOver ? 'is-drag-over' : ''}`,
            draggable: true,
            onDragStart: (e) => handleDragStart(e, index),
            onDragOver: (e) => handleDragOver(e, index),
            onDragLeave: handleDragLeave,
            onDrop: (e) => handleDrop(e, index),
            onDragEnd: handleDragEnd,
          },
            // Header
            wp.element.createElement('div', {
              className: 'gw-custom-blocks-repeater-item-header',
            },
              // Drag handle icon
              wp.element.createElement('span', {
                className: 'gw-custom-blocks-repeater-drag-handle',
                style: {
                  display: 'inline-flex',
                  alignItems: 'center',
                  marginRight: '8px',
                  cursor: 'move',
                  color: '#757575',
                },
                'aria-label': __('Drag to reorder', 'gwblueprint') || 'Drag to reorder'
              }, wp.element.createElement('span', {
                className: 'dashicons dashicons-menu-alt',
                style: { fontSize: '16px', width: '16px', height: '16px' }
              })),
              // Item title
              wp.element.createElement('span', {
                style: { flex: 1, fontWeight: 500 }
              }, __('Item', 'gwblueprint') || 'Item', ' ', index + 1),
              // Delete button
              wp.element.createElement('button', {
                className: 'gw-custom-blocks-repeater-item-delete',
                onClick: (e) => {
                  e.stopPropagation();
                  removeItem(index);
                },
                style: {
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '4px',
                  marginRight: '8px',
                  color: '#dc3232',
                  display: 'inline-flex',
                  alignItems: 'center',
                },
                'aria-label': __('Delete item', 'gwblueprint') || 'Delete item'
              }, wp.element.createElement('span', {
                className: 'dashicons dashicons-dismiss',
                style: { fontSize: '16px', width: '16px', height: '16px' }
              })),
              // Toggle button
              wp.element.createElement('button', {
                className: 'gw-custom-blocks-repeater-item-toggle',
                onClick: () => toggleItem(index),
                style: {
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '4px',
                  color: '#757575',
                  display: 'inline-flex',
                  alignItems: 'center',
                },
                'aria-label': isOpen ? (__('Collapse item', 'gwblueprint') || 'Collapse item') : (__('Expand item', 'gwblueprint') || 'Expand item')
              }, wp.element.createElement('span', {
                className: isOpen ? 'dashicons dashicons-arrow-up-alt2' : 'dashicons dashicons-arrow-down-alt2',
                style: { fontSize: '16px', width: '16px', height: '16px' }
              }))
            ),
            // Content (collapsible)
            isOpen && wp.element.createElement('div', {
              className: 'gw-custom-blocks-repeater-item-content',
            },
              Object.keys(subFields).map(subKey => {
                const subField = subFields[subKey];
                const subValue = item && item[subKey] !== undefined ? item[subKey] : (subField.default !== undefined ? subField.default : '');
                return wp.element.createElement('div', {
                  key: subKey,
                  style: { marginBottom: 16 }
                },
                  renderControlFn(subKey, subField, subValue, (newValue) => {
                    updateItemField(index, subKey, newValue);
                  })
                );
              })
            )
          );
        })
      ),
      // Add item button
      wp.element.createElement(Button, {
        onClick: addItem,
        isSecondary: true,
        style: { marginTop: 12 },
        icon: 'plus-alt2'
      }, __('Add Item', 'gwblueprint') || 'Add Item')
    );
  };

  // Export control
  GWCBlocks.controls.Repeater = RepeaterControl;
})();

