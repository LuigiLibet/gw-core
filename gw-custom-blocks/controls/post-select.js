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
  const { ComboboxControl, Spinner } = GWCBlocks.deps;

  // Post Select Control Component
  const PostSelectControl = ({ label, value, onChange, field }) => {
    const [options, setOptions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [search, setSearch] = useState('');
    // The already-saved option, fetched by ID so its label shows even when it is not
    // part of the current search results (which only return the first page).
    const [selectedOption, setSelectedOption] = useState(null);

    const postType = field.post_type || 'page';

    // Preload the saved value: if a value is set and it isn't already in the loaded
    // options, fetch just that post by ID once and keep it so the combobox can render
    // its label. Without this the saved page "disappears" when it sorts beyond the
    // first page of results.
    useEffect(() => {
      let isActive = true;

      if (!value) {
        setSelectedOption(null);
        return;
      }

      const valueStr = String(value);

      // Already covered by the current options or the cached selected option.
      if (options.some(opt => opt.value === valueStr)) {
        return;
      }
      if (selectedOption && selectedOption.value === valueStr) {
        return;
      }

      const fetchSelected = async () => {
        try {
          const apiFetch = wp.apiFetch || (window.wp && window.wp.apiFetch);
          const queryParams = new URLSearchParams({
            type: postType,
            include: valueStr,
          });

          const response = await apiFetch({ path: `/gw/v1/posts?${queryParams.toString()}` });

          if (isActive && Array.isArray(response) && response.length) {
            const post = response[0];
            setSelectedOption({ value: String(post.value), label: post.label });
          }
        } catch (e) {
          console.error('[GW Custom Blocks] Error fetching selected post:', e);
        }
      };

      fetchSelected();

      return () => {
        isActive = false;
      };
    }, [value, postType, options, selectedOption]);

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

    // Always keep the saved option in the list, even as the search replaces the rest,
    // so the combobox can resolve the current value to a label.
    const valueStr = value ? String(value) : '';
    let mergedOptions = options;
    if (selectedOption && valueStr && !options.some(opt => opt.value === valueStr)) {
      mergedOptions = [selectedOption, ...options];
    }

    // ComboboxControl fija el label mostrado AL MONTAR; nuestra opción guardada llega
    // async (la precargamos por ID), así que el control ya se pintó vacío y no refresca.
    // Forzamos un remount puntual cuando el value ya resuelve a una opción, vía key.
    const valueResolved = !valueStr || mergedOptions.some(opt => opt.value === valueStr);
    const comboKey = valueResolved ? ('gwps-' + valueStr) : 'gwps-pending';

    return wp.element.createElement('div', { className: 'gw-custom-blocks-post-select-control' },
      wp.element.createElement(ComboboxControl, {
        key: comboKey,
        label: label,
        value: valueStr, // Ensure value is string
        onChange: (newValue) => {
          // Ensure we pass a string or empty string
          onChange(newValue ? String(newValue) : '');
        },
        onFilterValueChange: (inputValue) => {
          setSearch(inputValue);
        },
        options: mergedOptions,
        isLoading: isLoading,
        allowReset: true,
      }),
      isLoading && wp.element.createElement(Spinner, {})
    );
  };

  // Export control
  GWCBlocks.controls.PostSelect = PostSelectControl;
})();

