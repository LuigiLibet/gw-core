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
  const { ComboboxControl, Spinner, Button } = GWCBlocks.deps;
  const { __ } = GWCBlocks.deps;

  // Term Select Control Component
  const TermSelectControl = ({ label, value, onChange, field }) => {
    const [options, setOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [selectedTerms, setSelectedTerms] = useState([]);
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    const taxonomy = field.taxonomy || 'category';
    const isMultiple = field.multiple !== false && (field.multiple === true || field.single === false);
    const queryArgs = field.args || {};

    // Parse value string to array of term IDs
    const parseValue = (val) => {
      if (!val || typeof val !== 'string') return [];
      return val.split(',').map(id => id.trim()).filter(id => id && /^\d+$/.test(id));
    };

    // Convert array of term IDs to comma-separated string
    const stringifyValue = (ids) => {
      return ids.filter(id => id).join(',');
    };

    // Initialize selected terms from value
    useEffect(() => {
      if (isMultiple) {
        const ids = parseValue(value);
        if (ids.length > 0) {
          fetchTermDetails(ids);
        } else {
          setSelectedTerms([]);
        }
      }
    }, [value, isMultiple]);

    // Fetch term details for selected IDs
    const fetchTermDetails = async (ids) => {
      if (!ids || ids.length === 0) {
        setSelectedTerms([]);
        return;
      }

      try {
        const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
        const queryParams = new URLSearchParams({
          taxonomy: taxonomy,
          include: ids.join(','),
          per_page: 100,
        });

        const response = await apiFetch({ path: `/gw/v1/terms?${queryParams.toString()}` });
        
        if (response && Array.isArray(response)) {
          // Preserve order from ids array
          const termsMap = {};
          response.forEach(term => {
            termsMap[term.value] = term;
          });
          
          const orderedTerms = ids
            .map(id => termsMap[id])
            .filter(term => term !== undefined);
          
          setSelectedTerms(orderedTerms);
        }
      } catch (e) {
        console.error('[GW Custom Blocks] Error fetching term details:', e);
      }
    };

    // Fetch terms for dropdown
    useEffect(() => {
      let isActive = true;
      const fetchTerms = async () => {
        setIsLoading(true);
        try {
          const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
          const queryParams = new URLSearchParams({
            taxonomy: taxonomy,
            per_page: search ? 20 : 10,
            search: search,
            hide_empty: queryArgs.hide_empty !== undefined ? queryArgs.hide_empty : false,
            orderby: queryArgs.orderby || 'name',
            order: queryArgs.order || 'ASC',
          });

          // Add other query args
          if (queryArgs.include && Array.isArray(queryArgs.include)) {
            queryParams.append('include', queryArgs.include.join(','));
          }
          if (queryArgs.exclude && Array.isArray(queryArgs.exclude)) {
            queryParams.append('exclude', queryArgs.exclude.join(','));
          }
          if (queryArgs.parent !== undefined) {
            queryParams.append('parent', queryArgs.parent);
          }

          const response = await apiFetch({ path: `/gw/v1/terms?${queryParams.toString()}` });

          if (isActive) {
            // Group terms by parent for hierarchical display
            const groupedOptions = [];
            const parentMap = {};
            const rootTerms = [];

            response.forEach(term => {
              const termOption = {
                value: String(term.value),
                label: term.label,
                parent: term.parent || 0,
              };

              if (term.parent === 0) {
                rootTerms.push(termOption);
              } else {
                if (!parentMap[term.parent]) {
                  parentMap[term.parent] = [];
                }
                parentMap[term.parent].push(termOption);
              }
            });

            // Build hierarchical structure
            const buildHierarchical = (parentId) => {
              const children = parentMap[parentId] || [];
              return children.map(child => {
                const grandChildren = buildHierarchical(parseInt(child.value));
                return {
                  ...child,
                  children: grandChildren,
                };
              });
            };

            // Add root terms and their children
            rootTerms.forEach(root => {
              const children = buildHierarchical(parseInt(root.value));
              groupedOptions.push({
                ...root,
                children: children,
              });
            });

            // Flatten for ComboboxControl (it doesn't support groups natively, so we'll use indentation)
            const flatOptions = [];
            const addWithIndent = (terms, level = 0) => {
              terms.forEach(term => {
                const indent = '  '.repeat(level);
                flatOptions.push({
                  value: term.value,
                  label: indent + term.label,
                });
                if (term.children && term.children.length > 0) {
                  addWithIndent(term.children, level + 1);
                }
              });
            };
            addWithIndent(groupedOptions);

            setOptions(flatOptions.length > 0 ? flatOptions : response.map(term => ({
              value: String(term.value),
              label: term.label
            })));
          }
        } catch (e) {
          console.error('[GW Custom Blocks] Error fetching terms:', e);
        } finally {
          if (isActive) setIsLoading(false);
        }
      };

      const timeoutId = setTimeout(() => {
        fetchTerms();
      }, 300); // Debounce

      return () => {
        isActive = false;
        clearTimeout(timeoutId);
      };
    }, [search, taxonomy, JSON.stringify(queryArgs)]);

    // Handle term selection
    const handleTermSelect = (selectedValue) => {
      if (!selectedValue) {
        if (!isMultiple) {
          onChange('');
        }
        return;
      }

      if (isMultiple) {
        const currentIds = parseValue(value);
        if (!currentIds.includes(selectedValue)) {
          const newIds = [...currentIds, selectedValue];
          onChange(stringifyValue(newIds));
        }
      } else {
        onChange(selectedValue);
      }
    };

    // Handle term removal (multiple mode)
    const handleTermRemove = (termId) => {
      const currentIds = parseValue(value);
      const newIds = currentIds.filter(id => id !== termId);
      onChange(stringifyValue(newIds));
    };

    // Drag and drop handlers (using native HTML5 API like repeater and gallery)
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

      const currentIds = parseValue(value);
      const newIds = [...currentIds];
      const draggedId = newIds[draggedIndex];
      newIds.splice(draggedIndex, 1);
      newIds.splice(dropIndex, 0, draggedId);
      onChange(stringifyValue(newIds));

      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    const handleDragEnd = () => {
      setDraggedIndex(null);
      setDragOverIndex(null);
    };

    // Render single mode
    if (!isMultiple) {
      return wp.element.createElement('div', { className: 'gw-custom-blocks-term-select-control' },
        wp.element.createElement(ComboboxControl, {
          label: label,
          value: value ? String(value) : '',
          onChange: handleTermSelect,
          onFilterValueChange: (inputValue) => {
            setSearch(inputValue);
          },
          options: options,
          isLoading: isLoading,
          allowReset: true,
        }),
        isLoading && wp.element.createElement(Spinner, {})
      );
    }

    // Render multiple mode with drag & drop (using native HTML5 API)
    const currentIds = parseValue(value);

    return wp.element.createElement('div', { className: 'gw-custom-blocks-term-select-control gw-custom-blocks-term-select-control-multiple' },
      wp.element.createElement(ComboboxControl, {
        label: label,
        value: '',
        onChange: handleTermSelect,
        onFilterValueChange: (inputValue) => {
          setSearch(inputValue);
        },
        options: options.filter(opt => !currentIds.includes(opt.value)),
        isLoading: isLoading,
        allowReset: false,
      }),
      isLoading && wp.element.createElement(Spinner, {}),
      selectedTerms.length > 0 && wp.element.createElement('div', { className: 'gw-custom-blocks-term-list' },
        selectedTerms.map((term, index) => {
          const isDragging = draggedIndex === index;
          const isDragOver = dragOverIndex === index;
          return wp.element.createElement('div', {
            key: term.value,
            className: `gw-custom-blocks-term-item ${isDragging ? 'is-dragging' : ''} ${isDragOver ? 'is-drag-over' : ''}`,
            draggable: true,
            onDragStart: (e) => handleDragStart(e, index),
            onDragOver: (e) => handleDragOver(e, index),
            onDragLeave: handleDragLeave,
            onDrop: (e) => handleDrop(e, index),
            onDragEnd: handleDragEnd,
          },
            wp.element.createElement('div', {
              className: 'gw-custom-blocks-term-item-drag-handle',
              'aria-label': __('Drag to reorder', 'gwblueprint') || 'Drag to reorder',
            }, '⋮⋮'),
            wp.element.createElement('span', { className: 'gw-custom-blocks-term-item-label' }, term.label),
            wp.element.createElement(Button, {
              isDestructive: true,
              isSmall: true,
              onClick: () => handleTermRemove(term.value),
              className: 'gw-custom-blocks-term-item-remove',
            }, '×')
          );
        })
      )
    );
  };

  // Export control
  GWCBlocks.controls.TermSelect = TermSelectControl;
})();

