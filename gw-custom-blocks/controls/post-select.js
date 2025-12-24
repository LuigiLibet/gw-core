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

  // Export control
  GWCBlocks.controls.PostSelect = PostSelectControl;
})();

