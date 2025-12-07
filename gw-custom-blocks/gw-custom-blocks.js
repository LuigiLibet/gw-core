/* global GW_CUSTOM_BLOCKS, wp */
(function () {
  if (typeof wp === 'undefined' || !wp.blocks || !GW_CUSTOM_BLOCKS || !Array.isArray(GW_CUSTOM_BLOCKS.blocks)) {
    return;
  }

  const { registerBlockType } = wp.blocks;
  const { __ } = wp.i18n;
  const { useMemo, useState, useEffect } = wp.element;
  const { InspectorControls, BlockControls, useBlockProps, MediaUpload, MediaUploadCheck, InnerBlocks } = wp.blockEditor || wp.editor;
  const {
    PanelBody,
    TextControl,
    TextareaControl,
    RangeControl,
    ToggleControl,
    SelectControl,
    ColorPalette,
    TabPanel,
    ToolbarGroup,
    ToolbarButton,
    Button,
    ComboboxControl,
    Spinner,
  } = wp.components;
  const ServerSideRender = (wp.serverSideRender && wp.serverSideRender.default) || wp.serverSideRender;
  const { useEntityProp } = wp.coreData || {};
  const dataSelect = (wp.data && wp.data.select) ? wp.data.select : null;

  const getEditorColors = () => {
    try {
      const settings = (wp.data && wp.data.select('core/block-editor').getSettings()) || {};
      return settings.colors || [];
    } catch (e) {
      return [];
    }
  };

  // PHP Serialization helpers for Repeater
  // Simple PHP unserialize for arrays of objects with string values
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

  // Simple PHP serialize for arrays of objects
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

  // Gallery Control Component with Drag and Drop
  const GalleryControl = ({ label, value, onChange, field }) => {
    const [images, setImages] = useState([]);
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    // Parse value string (comma-separated IDs) to array
    useEffect(() => {
      const ids = value ? value.split(',').map(id => id.trim()).filter(id => id) : [];
      if (ids.length === 0) {
        setImages([]);
        return;
      }

      // Fetch image data from WordPress REST API
      const fetchImages = async () => {
        try {
          const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
          if (!apiFetch) {
            console.error('[GW Custom Blocks] wp.apiFetch not available');
            setImages([]);
            return;
          }
          const imagePromises = ids.map(id => {
            return apiFetch({ path: `/wp/v2/media/${id}` }).catch(() => null);
          });
          const imageData = await Promise.all(imagePromises);
          const validImages = imageData
            .filter(img => img !== null)
            .map(img => ({
              id: img.id,
              url: img.source_url || img.media_details?.sizes?.thumbnail?.source_url || img.url,
              alt: img.alt_text || '',
              title: img.title?.rendered || '',
            }));
          setImages(validImages);
        } catch (e) {
          console.error('[GW Custom Blocks] Error fetching gallery images:', e);
          setImages([]);
        }
      };

      fetchImages();
    }, [value]);

    // Update value when images change
    const updateValue = (newImages) => {
      const ids = newImages.map(img => img.id).join(',');
      onChange(ids);
    };

    const onSelectImages = (selectedImages) => {
      const newImages = selectedImages.map(img => ({
        id: img.id,
        url: img.url || img.sizes?.thumbnail?.url || img.source_url,
        alt: img.alt || img.alt_text || '',
        title: img.title || img.title?.rendered || '',
      }));
      const combinedImages = [...images, ...newImages];
      setImages(combinedImages);
      updateValue(combinedImages);
    };

    const removeImage = (index) => {
      const newImages = images.filter((_, i) => i !== index);
      setImages(newImages);
      updateValue(newImages);
    };

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

      const newImages = [...images];
      const draggedImage = newImages[draggedIndex];
      newImages.splice(draggedIndex, 1);
      newImages.splice(dropIndex, 0, draggedImage);
      setImages(newImages);
      updateValue(newImages);
      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    const handleDragEnd = () => {
      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    return wp.element.createElement('div', { className: 'gw-custom-blocks-gallery-control' },
      wp.element.createElement('div', { style: { marginBottom: 8, fontWeight: 600 } }, label),
      wp.element.createElement(MediaUploadCheck, {},
        wp.element.createElement(MediaUpload, {
          onSelect: onSelectImages,
          allowedTypes: ['image'],
          multiple: true,
          value: images.map(img => img.id),
          gallery: true,
          render: ({ open }) => {
            return wp.element.createElement(Button, {
              onClick: open,
              isPrimary: true,
              style: { marginBottom: 12 },
            }, __('Add Images'));
          }
        })
      ),
      images.length > 0 && wp.element.createElement('div', {
        className: 'gw-custom-blocks-gallery-grid',
        style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: '8px', marginTop: '12px' }
      },
        images.map((img, index) => {
          const isDragging = draggedIndex === index;
          const isDragOver = dragOverIndex === index;
          return wp.element.createElement('div', {
            key: img.id,
            className: `gw-custom-blocks-gallery-item ${isDragging ? 'is-dragging' : ''} ${isDragOver ? 'is-drag-over' : ''}`,
            draggable: true,
            onDragStart: (e) => handleDragStart(e, index),
            onDragOver: (e) => handleDragOver(e, index),
            onDragLeave: handleDragLeave,
            onDrop: (e) => handleDrop(e, index),
            onDragEnd: handleDragEnd,
            style: {
              position: 'relative',
              cursor: 'move',
              opacity: isDragging ? 0.5 : 1,
              border: isDragOver ? '2px dashed #0073aa' : '2px solid transparent',
            }
          },
            wp.element.createElement('img', {
              src: img.url,
              alt: img.alt,
              style: { width: '100%', height: 'auto', display: 'block' }
            }),
            wp.element.createElement('button', {
              className: 'gw-custom-blocks-gallery-remove',
              onClick: () => removeImage(index),
              style: {
                position: 'absolute',
                top: '4px',
                right: '4px',
                background: 'rgba(0, 0, 0, 0.7)',
                color: '#fff',
                border: 'none',
                borderRadius: '50%',
                width: '24px',
                height: '24px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '16px',
                lineHeight: 1,
              },
              'aria-label': __('Remove image')
            }, '×')
          );
        })
      )
    );
  };

  // Image Control Component (single image)
  const ImageControl = ({ label, value, onChange, field }) => {
    const [image, setImage] = useState(null);
    const saveAs = (field.saveAs || 'url').toLowerCase();

    // Parse value: can be ID (numeric) or URL (string)
    useEffect(() => {
      if (!value || value === '') {
        setImage(null);
        return;
      }

      // If saveAs is 'id', value should be numeric ID
      if (saveAs === 'id') {
        const imageId = parseInt(value, 10);
        if (isNaN(imageId) || imageId <= 0) {
          setImage(null);
          return;
        }

        // Fetch image data from WordPress REST API
        const fetchImage = async () => {
          try {
            const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
            if (!apiFetch) {
              console.error('[GW Custom Blocks] wp.apiFetch not available');
              setImage(null);
              return;
            }
            const imageData = await apiFetch({ path: `/wp/v2/media/${imageId}` }).catch(() => null);
            if (imageData) {
              setImage({
                id: imageData.id,
                url: imageData.source_url || imageData.media_details?.sizes?.thumbnail?.source_url || imageData.url,
                alt: imageData.alt_text || '',
                title: imageData.title?.rendered || '',
              });
            } else {
              setImage(null);
            }
          } catch (e) {
            console.error('[GW Custom Blocks] Error fetching image:', e);
            setImage(null);
          }
        };

        fetchImage();
      } else {
        // saveAs is 'url', value is already a URL
        setImage({
          id: null,
          url: value,
          alt: '',
          title: '',
        });
      }
    }, [value, saveAs]);

    // Update value when image changes
    const updateValue = (newImage) => {
      if (!newImage) {
        onChange('');
        return;
      }

      if (saveAs === 'id') {
        onChange(newImage.id ? String(newImage.id) : '');
      } else {
        onChange(newImage.url || '');
      }
    };

    const onSelectImage = (selectedImage) => {
      const newImage = {
        id: selectedImage.id,
        url: selectedImage.url || selectedImage.sizes?.thumbnail?.url || selectedImage.source_url,
        alt: selectedImage.alt || selectedImage.alt_text || '',
        title: selectedImage.title || selectedImage.title?.rendered || '',
      };
      setImage(newImage);
      updateValue(newImage);
    };

    const removeImage = () => {
      setImage(null);
      updateValue(null);
    };

    return wp.element.createElement('div', { className: 'gw-custom-blocks-image-control' },
      wp.element.createElement('div', { style: { marginBottom: 8, fontWeight: 600 } }, label),
      wp.element.createElement(MediaUploadCheck, {},
        wp.element.createElement(MediaUpload, {
          onSelect: onSelectImage,
          allowedTypes: ['image'],
          multiple: false,
          value: image && image.id ? image.id : undefined,
          render: ({ open }) => {
            if (image && image.url) {
              return wp.element.createElement('div', {
                style: {
                  position: 'relative',
                  width: '150px',
                  height: '150px',
                  borderRadius: '3px',
                  overflow: 'hidden',
                  border: '1px solid #ddd',
                  cursor: 'pointer',
                  background: '#f0f0f1',
                },
                onClick: open
              },
                wp.element.createElement('img', {
                  src: image.url,
                  alt: image.alt,
                  style: {
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    display: 'block'
                  }
                }),
                wp.element.createElement('button', {
                  className: 'gw-custom-blocks-image-remove',
                  onClick: (e) => {
                    e.stopPropagation();
                    removeImage();
                  },
                  style: {
                    position: 'absolute',
                    top: '4px',
                    right: '4px',
                    background: 'rgba(0, 0, 0, 0.7)',
                    color: '#fff',
                    border: 'none',
                    borderRadius: '3px',
                    width: '24px',
                    height: '24px',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: 0,
                    transition: 'background 0.2s ease',
                  },
                  onMouseEnter: (e) => {
                    e.target.style.background = 'rgba(220, 50, 47, 0.9)';
                  },
                  onMouseLeave: (e) => {
                    e.target.style.background = 'rgba(0, 0, 0, 0.7)';
                  },
                  'aria-label': __('Remove image', 'gwblueprint') || 'Remove image'
                }, wp.element.createElement('span', {
                  className: 'dashicons dashicons-dismiss',
                  style: { fontSize: '16px', width: '16px', height: '16px', color: '#fff' }
                }))
              );
            }
            return wp.element.createElement(Button, {
              onClick: open,
              isPrimary: true,
              style: { marginBottom: 12 },
            }, __('Select Image', 'gwblueprint') || 'Select Image');
          }
        })
      )
    );
  };

  // Icon Picker Control Component
  const IconPickerControl = ({ label, value, onChange, field }) => {
    const iconLibraries = (GW_CUSTOM_BLOCKS && GW_CUSTOM_BLOCKS.iconLibraries) || [];
    const [selectedLibrary, setSelectedLibrary] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedIcon, setSelectedIcon] = useState('');

    // Determine available libraries from field config or all libraries
    const availableLibraries = useMemo(() => {
      if (field.libraries && Array.isArray(field.libraries)) {
        if (field.libraries.length === 1 && field.libraries[0] === 'all') {
          return iconLibraries;
        }
        return iconLibraries.filter(lib => field.libraries.includes(lib.id));
      }
      return iconLibraries;
    }, [iconLibraries, field.libraries]);

    // Parse current value
    useEffect(() => {
      if (!value || value === '') {
        setSelectedLibrary('');
        setSelectedIcon('');
        return;
      }

      const parts = value.split(':');
      if (parts.length === 2) {
        // Format: "library:icon"
        setSelectedLibrary(parts[0]);
        setSelectedIcon(parts[1]);
      } else {
        // No library prefix - try to detect
        let foundLibrary = '';
        // Try to find library that contains this icon
        for (const lib of availableLibraries) {
          const found = lib.icons.find(icon => icon.value === parts[0]);
          if (found) {
            foundLibrary = lib.id;
            break;
          }
        }
        // If only one library, use it
        if (!foundLibrary && availableLibraries.length === 1) {
          foundLibrary = availableLibraries[0].id;
        }
        setSelectedLibrary(foundLibrary);
        setSelectedIcon(parts[0]);
      }
    }, [value, availableLibraries]);

    // Auto-select library if only one available
    useEffect(() => {
      if (availableLibraries.length === 1 && !selectedLibrary) {
        setSelectedLibrary(availableLibraries[0].id);
      }
    }, [availableLibraries, selectedLibrary]);

    // Get current library icons
    const currentLibrary = availableLibraries.find(lib => lib.id === selectedLibrary);
    const icons = currentLibrary ? currentLibrary.icons : [];

    // Filter icons by search term
    const filteredIcons = useMemo(() => {
      if (!searchTerm) return icons;
      const term = searchTerm.toLowerCase();
      return icons.filter(icon => 
        icon.value.toLowerCase().includes(term) || 
        icon.label.toLowerCase().includes(term)
      );
    }, [icons, searchTerm]);

    // Handle icon selection
    const handleIconSelect = (iconValue) => {
      setSelectedIcon(iconValue);
      // Format value: "library:icon" or just "icon" if single library
      const libraryToUse = selectedLibrary || (availableLibraries.length === 1 ? availableLibraries[0].id : '');
      if (availableLibraries.length === 1 && !libraryToUse) {
        // Single library, no prefix needed
        onChange(iconValue);
      } else if (libraryToUse) {
        onChange(`${libraryToUse}:${iconValue}`);
      } else {
        onChange(iconValue);
      }
    };

    // Handle library change
    const handleLibraryChange = (libraryId) => {
      setSelectedLibrary(libraryId);
      setSelectedIcon('');
      onChange('');
    };

    // Render icon preview
    const renderIconPreview = (iconValue, iconLabel, isSelected) => {
      if (!currentLibrary) return null;
      // Parse template to create element safely
      const template = currentLibrary.template.replace('{{icon}}', iconValue);
      // Use dangerouslySetInnerHTML to render the icon HTML directly
      // This ensures CSS classes are applied correctly
      return wp.element.createElement('div', {
        dangerouslySetInnerHTML: { __html: template },
        style: {
          fontSize: '20px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          width: '100%',
          height: '100%',
          lineHeight: 1,
        }
      });
    };

    return wp.element.createElement('div', { className: 'gw-custom-blocks-icon-picker-control' },
      wp.element.createElement('div', { style: { marginBottom: 8, fontWeight: 600 } }, label),
      
      // Library selector (if multiple libraries)
      availableLibraries.length > 1 && wp.element.createElement(SelectControl, {
        label: __('Icon Library', 'gwblueprint') || 'Icon Library',
        value: selectedLibrary,
        options: [
          { label: __('-- Select Library --', 'gwblueprint') || '-- Select Library --', value: '' },
          ...availableLibraries.map(lib => ({ label: lib.name, value: lib.id }))
        ],
        onChange: handleLibraryChange,
        style: { marginBottom: 12 }
      }),

      // Search field
      currentLibrary && wp.element.createElement(TextControl, {
        label: __('Search Icons', 'gwblueprint') || 'Search Icons',
        value: searchTerm,
        onChange: setSearchTerm,
        placeholder: __('Type to search...', 'gwblueprint') || 'Type to search...',
        style: { marginBottom: 12 }
      }),

      // Icon grid
      currentLibrary && filteredIcons.length > 0 && wp.element.createElement('div', {
        className: 'gw-custom-blocks-icon-picker-grid',
        style: {
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(60px, 1fr))',
          gap: '8px',
          maxHeight: '300px',
          overflowY: 'auto',
          padding: '8px',
          border: '1px solid #ddd',
          borderRadius: '4px',
          background: '#fff',
        }
      },
        filteredIcons.map((icon) => {
          const isSelected = selectedIcon === icon.value;
          return wp.element.createElement('button', {
            key: icon.value,
            type: 'button',
            className: `gw-custom-blocks-icon-picker-item ${isSelected ? 'is-selected' : ''}`,
            onClick: () => handleIconSelect(icon.value),
            title: icon.label,
            style: {
              position: 'relative',
              width: '100%',
              aspectRatio: '1',
              border: isSelected ? '2px solid #0073aa' : '1px solid #ddd',
              borderRadius: '4px',
              background: isSelected ? '#e5f5fa' : '#fff',
              cursor: 'pointer',
              padding: '8px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.2s ease',
            },
            onMouseEnter: (e) => {
              if (!isSelected) {
                e.target.style.borderColor = '#0073aa';
                e.target.style.background = '#f0f0f1';
              }
            },
            onMouseLeave: (e) => {
              if (!isSelected) {
                e.target.style.borderColor = '#ddd';
                e.target.style.background = '#fff';
              }
            }
          },
            renderIconPreview(icon.value, icon.label, isSelected),
            isSelected && wp.element.createElement('span', {
              className: 'dashicons dashicons-yes',
              style: {
                position: 'absolute',
                top: '2px',
                right: '2px',
                fontSize: '16px',
                color: '#0073aa',
                background: '#fff',
                borderRadius: '50%',
                width: '18px',
                height: '18px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }
            })
          );
        })
      ),

      // No icons message
      currentLibrary && filteredIcons.length === 0 && searchTerm && wp.element.createElement('div', {
        style: {
          padding: '20px',
          textAlign: 'center',
          color: '#757575',
        }
      }, __('No icons found', 'gwblueprint') || 'No icons found'),

      // No library selected message
      !currentLibrary && availableLibraries.length > 0 && wp.element.createElement('div', {
        style: {
          padding: '20px',
          textAlign: 'center',
          color: '#757575',
        }
      }, __('Please select an icon library', 'gwblueprint') || 'Please select an icon library'),

      // No libraries available
      availableLibraries.length === 0 && wp.element.createElement('div', {
        style: {
          padding: '20px',
          textAlign: 'center',
          color: '#757575',
        }
      }, __('No icon libraries available', 'gwblueprint') || 'No icon libraries available')
    );
  };

  // Post Select Control Component
  const PostSelectControl = ({ label, value, onChange, field }) => {
    const [options, setOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [selectedLabel, setSelectedLabel] = useState('');

    const postType = field.post_type || 'page';

    // Fetch initial posts or search results
    useEffect(() => {
      let isActive = true;
      const fetchPosts = async () => {
        setIsLoading(true);
        try {
          const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
          const queryParams = new URLSearchParams({
            type: postType,
            per_page: search ? 20 : 10, // Load more when searching
            search: search,
          });

          const response = await apiFetch({ path: `/gw/v1/posts?${queryParams.toString()}` });

          if (isActive) {
            setOptions(response.map(post => ({
              value: String(post.value), // Ensure value is string
              label: post.label
            })));
          }
        } catch (e) {
          console.error('[GW Custom Blocks] Error fetching posts:', e);
        } finally {
          if (isActive) setIsLoading(false);
        }
      };

      const timeoutId = setTimeout(() => {
        fetchPosts();
      }, 300); // Debounce

      return () => {
        isActive = false;
        clearTimeout(timeoutId);
      };
    }, [search, postType]);

    return wp.element.createElement('div', { className: 'gw-custom-blocks-post-select-control' },
      wp.element.createElement(ComboboxControl, {
        label: label,
        value: value ? String(value) : '', // Ensure value is string
        onChange: (newValue) => {
          // Ensure we pass a string or empty string
          onChange(newValue ? String(newValue) : '');
        },
        onFilterValueChange: (inputValue) => {
          setSearch(inputValue);
        },
        options: options,
        isLoading: isLoading,
        allowReset: true,
      }),
      isLoading && wp.element.createElement(Spinner, {})
    );
  };

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

          let inspector;
          const fieldKeys = Object.keys(b.fields || {});
          const hasTabs = b.ui && Array.isArray(b.ui.tabs) && b.ui.tabs.length > 0;
          if (hasTabs) {
            const tabs = b.ui.tabs.map((t) => ({ name: t.name, title: t.title || t.name }));
            inspector = wp.element.createElement(InspectorControls, {},
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
            inspector = wp.element.createElement(InspectorControls, {},
              wp.element.createElement(PanelBody, { title: __('Settings') },
                fieldKeys.map((key) => wp.element.createElement(ControlForField, { b, fieldKey: key, field: b.fields[key], attributes, setAttributes, postType }))
              )
            );
          }

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
})();


