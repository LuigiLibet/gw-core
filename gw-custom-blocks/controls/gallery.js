/* global wp, GWCBlocks */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps) {
    return;
  }

  if (!GWCBlocks.controls) {
    GWCBlocks.controls = {};
  }

  const { useState, useEffect } = GWCBlocks.deps;
  const { MediaUpload, MediaUploadCheck, Button } = GWCBlocks.deps;
  const { __ } = GWCBlocks.deps;

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

  // Export control
  GWCBlocks.controls.Gallery = GalleryControl;
})();

