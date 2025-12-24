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

  // Export control
  GWCBlocks.controls.Image = ImageControl;
})();

