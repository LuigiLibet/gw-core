/* global wp, GWCBlocks, GW_CUSTOM_BLOCKS */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined' || !GWCBlocks.deps) {
    return;
  }

  if (!GWCBlocks.controls) {
    GWCBlocks.controls = {};
  }

  const { useState, useEffect, useMemo } = GWCBlocks.deps;
  const { SelectControl, TextControl } = GWCBlocks.deps;
  const { __ } = GWCBlocks.deps;

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

  // Export control
  GWCBlocks.controls.IconPicker = IconPickerControl;
})();

