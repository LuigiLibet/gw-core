# GW Custom Blocks - Documentation

Simplified system for registering dynamic WordPress blocks with PHP rendering and automatically generated editor controls.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Basic Usage](#basic-usage)
- [Block Registration](#block-registration)
- [Field Types](#field-types)
- [Rendering](#rendering)
- [User Interface](#user-interface)
- [Auto-discovery](#auto-discovery)
- [Meta Fields](#meta-fields)
- [Complete Examples](#complete-examples)

## Overview

GW Custom Blocks is a system that simplifies the creation of dynamic WordPress blocks (server-rendered blocks). It provides:

- **Simplified registration**: A single PHP function to register complete blocks
- **Automatic controls**: Automatically generates editor controls based on configuration
- **Flexible rendering**: Supports PHP templates or inline HTML with placeholders
- **Auto-discovery**: Can automatically discover and register blocks
- **Advanced support**: Tabs, custom toolbars, and meta fields

## Architecture

The system consists of two main components:

### 1. PHP (`gw-custom-blocks.php`)

- **Block registration**: `gw_register_block()` function that registers blocks in WordPress
- **Global registry**: Maintains a global registry (`$GLOBALS['gw_custom_blocks_registry']`) with the configuration of all blocks
- **Localization**: Passes configuration to JavaScript script via `wp_localize_script()`
- **Rendering**: Handles rendering of PHP templates or inline HTML

### 2. JavaScript (`gw-custom-blocks.js`)

- **Editor registration**: Reads localized configuration and registers blocks in the editor
- **Dynamic controls**: Generates inspector controls based on field configuration
- **Preview**: Uses `ServerSideRender` to display a preview of the block
- **Custom UI**: Supports tabs and custom toolbars
- **Styling**: Includes `gw-custom-blocks.css` for custom control styles (gallery, repeater, etc.)

## Basic Usage

### Step 1: Include the PHP file

Make sure to include the `gw-custom-blocks.php` file in your theme:

```php
require_once get_template_directory() . '/editor/gw-custom-blocks/gw-custom-blocks.php';
```

### Step 2: Register a block

```php
gw_register_block('my-block', array(
    'name' => 'My Block',
    'category' => 'custom',
    'render' => 'my-block/view.php',
    'fields' => array(
        'title' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Title',
            'default' => 'Hello World'
        ),
    ),
));
```

## Block Registration

### Function: `gw_register_block($slug, $args)`

Registers a custom block in WordPress.

#### Parameters

- **`$slug`** (string): Block slug (without namespace). The final name will be `$namespace/$slug`
- **`$args`** (array): Block configuration

#### Available arguments

| Argument | Type | Default | Description |
|----------|------|---------|-------------|
| `name` | string | null | Block title in the UI. If null, it's generated from the slug |
| `namespace` | string | 'gw' | Block namespace |
| `category` | string | 'custom' | Block category in the inserter |
| `supports` | array | [] | Supported block features (color, spacing, etc.) |
| `render` | string | null | Path to PHP template or inline HTML |
| `dir` | string | '' | Base directory for relative paths. Default: `blocks/` |
| `icon` | string\|array | 'block-default' | Block icon |
| `keywords` | array | [] | Keywords for the inserter |
| `editor_styles` | string | '' | Optional path to CSS file to load in editor |
| `style` | string | '' | Optional path to CSS file to load on frontend when block is present |
| `script` | string | '' | Optional path to JS file to load on frontend when block is present |
| `fields` | array | [] | Field/attribute definition |
| `ui` | array | [] | UI configuration (tabs, toolbar) |

### Complete example

```php
gw_register_block('badge', array(
    'name' => 'Badge',
    'category' => 'custom',
    'icon' => 'admin-appearance',
    'keywords' => array('badge', 'label', 'tag'),
    'supports' => array(
        'color' => array(
            'text' => true,
            'background' => true,
        ),
        'spacing' => array(
            'padding' => true,
        ),
    ),
    'render' => 'badge/view.php',
    'editor_styles' => 'assets/css/editor.css',
    'style' => 'assets/css/frontend.css',
    'script' => 'assets/js/frontend.js',
    'fields' => array(
        'label' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Label',
            'default' => 'New',
        ),
        'size' => array(
            'type' => 'number',
            'control' => 'range',
            'label' => 'Size',
            'min' => 8,
            'max' => 64,
            'step' => 1,
            'default' => 16,
        ),
        'color' => array(
            'type' => 'string',
            'control' => 'color',
            'label' => 'Color',
        ),
    ),
));
```

## Field Types

Fields define block attributes and automatically generate editor controls.

### Field structure

```php
'field_name' => array(
    'type' => 'string',        // Data type: string, number, integer, boolean
    'control' => 'text',        // Control type: text, textarea, color, range, number, toggle, select
    'label' => 'Label',        // Control label
    'default' => '',           // Default value
    // ... control-specific options
)
```

### Available controls

**Note:** Control types: `text`, `textarea`, `color`, `range`, `number`, `toggle`, `select`, `gallery`, `image`, `repeater`, `post_select`, `term_select`, `icon_picker`

#### 1. Text (`control: 'text'`)

Simple text field.

```php
'title' => array(
    'type' => 'string',
    'control' => 'text',
    'label' => 'Title',
    'default' => '',
)
```

#### 2. Textarea (`control: 'textarea'`)

Multiline text field.

```php
'description' => array(
    'type' => 'string',
    'control' => 'textarea',
    'label' => 'Description',
    'default' => '',
)
```

#### 3. Number (`control: 'number'`)

Numeric field.

```php
'quantity' => array(
    'type' => 'number',
    'control' => 'number',
    'label' => 'Quantity',
    'default' => 0,
)
```

#### 4. Range (`control: 'range'`)

Slider control for numeric values.

```php
'size' => array(
    'type' => 'number',
    'control' => 'range',
    'label' => 'Size',
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'default' => 50,
)
```

#### 5. Toggle (`control: 'toggle'`)

Boolean switch.

```php
'active' => array(
    'type' => 'boolean',
    'control' => 'toggle',
    'label' => 'Active',
    'default' => false,
)
```

#### 6. Select (`control: 'select'`)

Dropdown list.

```php
'status' => array(
    'type' => 'string',
    'control' => 'select',
    'label' => 'Status',
    'options' => array(
        array('label' => 'Published', 'value' => 'published'),
        array('label' => 'Draft', 'value' => 'draft'),
        'archived', // Also accepts simple strings
    ),
    'default' => 'published',
)
```

#### 7. Color (`control: 'color'`)

Color picker using the editor palette.

```php
'background_color' => array(
    'type' => 'string',
    'control' => 'color',
    'label' => 'Background Color',
    'default' => '',
)
```

#### 8. Gallery (`control: 'gallery'`)

Image gallery with drag and drop reordering. Stores image IDs as comma-separated string.

```php
'images' => array(
    'type' => 'string',
    'control' => 'gallery',
    'label' => 'Gallery',
    'default' => '',
)
```

**Features:**
- Select multiple images from WordPress media library
- Drag and drop to reorder images
- Remove images with delete button
- Stores image IDs as comma-separated string (e.g., "123,456,789")

**Helper function:** Use `gw_get_gallery_urls($ids_string, $size = 'full')` in PHP templates to get array of image URLs.

#### 9. Image (`control: 'image'`)

Single image upload with thumbnail preview. Stores image ID or URL based on configuration.

```php
'image' => array(
    'type' => 'string',
    'control' => 'image',
    'label' => 'Image',
    'default' => '',
    'saveAs' => 'url', // 'id' or 'url' (default: 'url')
)
```

**Features:**
- Select a single image from WordPress media library
- Square thumbnail preview with rounded corners (3px border-radius)
- Remove button with dismiss icon to clear the image
- Configurable storage: save as image ID or image URL
- Default storage format is URL

**Configuration:**
- `saveAs`: Determines what value is stored
  - `'url'` (default): Stores the full image URL as a string
  - `'id'`: Stores the WordPress attachment ID as a string

**Helper function:** Use `gw_get_image_url($value, $size = 'full')` in PHP templates to get image URL regardless of storage format.

**Example usage in template (`blocks/my-block/view.php`):**
```php
<?php
$image_value = isset($attributes['image']) ? $attributes['image'] : '';
if (!empty($image_value)) {
    $image_url = gw_get_image_url($image_value, 'large');
    ?>
    <img src="<?php echo esc_url($image_url); ?>" alt="" />
    <?php
}
?>
```

**Example with saveAs 'id':**
```php
gw_register_block('hero', array(
    'fields' => array(
        'background_image' => array(
            'type' => 'string',
            'control' => 'image',
            'label' => 'Background Image',
            'saveAs' => 'id', // Store as ID instead of URL
            'default' => '',
        ),
    ),
));
```

#### 10. Repeater (`control: 'repeater'`)

Repeater field with accordion items, each containing nested fields. Supports drag and drop reordering and item deletion with confirmation.

```php
'items' => array(
    'type' => 'string',
    'control' => 'repeater',
    'label' => 'Items',
    'default' => '',
    'subFields' => array(
        'title' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Title',
            'default' => '',
        ),
        'description' => array(
            'type' => 'string',
            'control' => 'textarea',
            'label' => 'Description',
            'default' => '',
        ),
        'image' => array(
            'type' => 'string',
            'control' => 'gallery',
            'label' => 'Image',
            'default' => '',
        ),
        'active' => array(
            'type' => 'boolean',
            'control' => 'toggle',
            'label' => 'Active',
            'default' => false,
        ),
    ),
)
```

**Features:**
- Add multiple items with nested fields
- Accordion interface: expand/collapse each item
- Drag and drop to reorder items
- Delete items with confirmation dialog
- Supports all field types within items (text, textarea, number, toggle, select, color, gallery, etc.)
- Stores data as JSON string (compatible with PHP serialized format)

**Configuration:**
- `subFields` or `fields`: Array of field definitions for nested fields within each item
- Each nested field follows the same structure as regular fields
- Default values are automatically applied when creating new items

**Helper function:** Use `gw_get_repeater_items($serialized_string)` in PHP templates to get array of items.

**Example usage in template (`blocks/my-block/view.php`):**
```php
<?php
$items_data = isset($attributes['items']) ? $attributes['items'] : '';
$items = gw_get_repeater_items($items_data);
?>
<div class="items-list">
    <?php foreach ($items as $item): ?>
        <div class="item">
            <h3><?php echo esc_html($item['title'] ?? ''); ?></h3>
            <p><?php echo esc_html($item['description'] ?? ''); ?></p>
            <?php if (!empty($item['image'])): ?>
                <?php
                $image_urls = gw_get_gallery_urls($item['image'], 'large');
                foreach ($image_urls as $url):
                ?>
                    <img src="<?php echo esc_url($url); ?>" alt="" />
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
```

#### 11. Post Select (`control: 'post_select'`)

Dropdown to select a post (page, post, product, etc.) with AJAX search.

```php
'selected_page' => array(
    'type' => 'string',
    'control' => 'post_select',
    'label' => 'Select a Page',
    'post_type' => 'page', // Optional, defaults to 'page'
)
```

**Features:**
- Searchable dropdown using WordPress REST API
- Loads first 10 items by default
- Fetches more items on search (up to 20)
- Stores the Post ID as a string

**Configuration:**
- `post_type`: The post type to search for (e.g., 'page', 'post', 'product'). Default: 'page'.

**Usage in template:**
```php
<?php
$page_id = isset($attributes['selected_page']) ? $attributes['selected_page'] : '';
if (!empty($page_id)) {
    $permalink = get_permalink((int)$page_id);
    // ...
}
?>
```

#### 12. Term Select (`control: 'term_select'`)

Dropdown to select terms from a taxonomy with AJAX search, support for single or multiple selection, drag & drop ordering (multiple mode), and hierarchical grouping.

```php
'categories' => array(
    'type' => 'string',
    'control' => 'term_select',
    'label' => 'Select Categories',
    'taxonomy' => 'category', // Required: taxonomy name
    'multiple' => true, // Optional: true for multiple selection, false for single (default: false)
    'args' => array(
        // Optional: WP_Term_Query arguments
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'include' => array(1, 2, 3), // Optional: limit to specific term IDs
        'exclude' => array(4, 5), // Optional: exclude specific term IDs
        'parent' => 0, // Optional: limit to top-level terms (for hierarchical taxonomies)
    ),
    'default' => '', // Comma-separated string for multiple, single ID for single
)
```

**Features:**
- Searchable dropdown using WordPress REST API
- Single or multiple selection modes
- Drag & drop reordering for multiple selection (using @dnd-kit)
- Hierarchical grouping: shows parent/child relationships with indentation
- Loads first 10 items by default, fetches more on search (up to 20)
- Stores term IDs as comma-separated string (multiple) or single ID string (single)

**Configuration:**
- `taxonomy` (required): The taxonomy name (e.g., 'category', 'post_tag', 'product_cat')
- `multiple` (optional): `true` for multiple selection with drag & drop, `false` for single selection. Default: `false`
- `args` (optional): Array of `WP_Term_Query` arguments to filter terms:
  - `hide_empty`: Whether to hide terms not assigned to any posts (default: `false`)
  - `orderby`: Field to order by ('name', 'count', 'term_id', etc.)
  - `order`: 'ASC' or 'DESC'
  - `include`: Array of term IDs to include
  - `exclude`: Array of term IDs to exclude
  - `parent`: Parent term ID (for hierarchical taxonomies)

**Helper function:** Use `gw_get_term_names($ids_string, $taxonomy)` in PHP templates to get array of term objects.

**Example usage in template (`blocks/my-block/view.php`):**
```php
<?php
$term_ids = isset($attributes['categories']) ? $attributes['categories'] : '';
if (!empty($term_ids)) {
    $terms = gw_get_term_names($term_ids, 'category');
    foreach ($terms as $term) {
        ?>
        <div class="category">
            <a href="<?php echo esc_url(get_term_link($term['id'])); ?>">
                <?php echo esc_html($term['name']); ?>
            </a>
        </div>
        <?php
    }
}
?>
```

**Example with single selection:**
```php
gw_register_block('product-category', array(
    'fields' => array(
        'category' => array(
            'type' => 'string',
            'control' => 'term_select',
            'label' => 'Product Category',
            'taxonomy' => 'product_cat',
            'multiple' => false, // Single selection
            'args' => array(
                'hide_empty' => true,
                'orderby' => 'name',
            ),
        ),
    ),
));
```

**Example with multiple selection and drag & drop:**
```php
gw_register_block('product-categories', array(
    'fields' => array(
        'categories' => array(
            'type' => 'string',
            'control' => 'term_select',
            'label' => 'Product Categories',
            'taxonomy' => 'product_cat',
            'multiple' => true, // Multiple selection with drag & drop
            'args' => array(
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ),
            'default' => '',
        ),
    ),
));
```

#### 13. Icon Picker (`control: 'icon_picker'`)

Icon picker with support for multiple icon libraries (Font Awesome, Flaticon UIcons, etc.).

```php
'icon' => array(
    'type' => 'string',
    'control' => 'icon_picker',
    'label' => 'Icon',
    'libraries' => array('uicons-rr'), // Optional: specify allowed libraries or 'all'
    'default' => '',
)
```

**Features:**
- Visual icon picker with grid preview
- Search/filter icons by name
- Support for multiple icon libraries
- Library selector (when multiple libraries available)
- Stores value as `"library:icon"` (e.g., `"uicons-rr:fi-rr-home"`) or just `"icon"` if single library

**Configuration:**
- `libraries`: Array of library IDs to allow, or `'all'` to allow all registered libraries. If omitted, all libraries are available.

**Helper function:** Use `gw_icon($value, $args)` in PHP templates to render the icon.

**Example usage in template (`blocks/my-block/view.php`):**
```php
<?php
$icon_value = isset($attributes['icon']) ? $attributes['icon'] : '';
if (!empty($icon_value)) {
    echo gw_icon($icon_value);
    // Or with custom template:
    // echo gw_icon($icon_value, array('template' => '<span class="{{icon}}"></span>'));
    // Or with additional classes:
    // echo gw_icon($icon_value, array('class' => 'my-icon-class'));
}
?>
```

**Registering Icon Libraries:**

Before using icon picker, you need to register icon libraries:

```php
// Register a library with manual icon list
gw_register_icon_library('my-library', array(
    'name' => 'My Icon Library',
    'css' => 'https://example.com/icons.css',
    'template' => '<i class="{{icon}}"></i>',
    'icons' => array(
        'icon-home' => 'Home',
        'icon-user' => 'User',
        'icon-envelope' => 'Envelope',
    ),
));

// Or parse icons from CSS automatically
$icons = gw_parse_icon_library_css(
    'https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css',
    'fi-rr-' // Class prefix
);

gw_register_icon_library('uicons-rr', array(
    'name' => 'Flaticon UIcons Regular Rounded',
    'css' => 'https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css',
    'template' => '<i class="{{icon}}"></i>',
    'icons' => $icons,
    'class_prefix' => 'fi-rr-',
));
```

**Helper Function: `gw_icon($value, $args)`**

Renders an icon from stored value.

**Parameters:**
- `$value` (string): Stored value (format: `"library:icon"` or just `"icon"` if single library)
- `$args` (array): Optional arguments:
  - `template` (string): Custom template override
  - `library` (string): Force specific library ID
  - `class` (string): Additional CSS classes

**Returns:** HTML string with rendered icon, or empty string if invalid.

**Examples:**
```php
// Basic usage
echo gw_icon('uicons-rr:fi-rr-home');

// Without library prefix (auto-detects)
echo gw_icon('fi-rr-home');

// Custom template
echo gw_icon('fi-rr-home', array('template' => '<span class="{{icon}}"></span>'));

// With additional classes
echo gw_icon('fi-rr-home', array('class' => 'my-icon large'));
```

## Rendering

The system supports two rendering methods:

### 1. PHP Template

Renders using a PHP file. Variables available in the template:

- **`$attributes`**: Array with all block attributes
- **`$content`**: Inner content of the block (if applicable)
- **`$block`**: Complete block object
- **`$post_id`**: Current post ID

**Example (`blocks/badge/view.php`):**

```php
<?php
$label = isset($attributes['label']) ? $attributes['label'] : 'New';
$size = isset($attributes['size']) ? $attributes['size'] : 16;
?>
<span class="badge" style="font-size: <?php echo esc_attr($size); ?>px;">
    <?php echo esc_html($label); ?>
</span>
```

**Example with Gallery (`blocks/gallery/view.php`):**

```php
<?php
$gallery_ids = isset($attributes['images']) ? $attributes['images'] : '';
$image_urls = gw_get_gallery_urls($gallery_ids, 'large');
?>
<div class="gallery">
    <?php foreach ($image_urls as $url): ?>
        <img src="<?php echo esc_url($url); ?>" alt="" />
    <?php endforeach; ?>
</div>
```

**Registration:**

```php
gw_register_block('badge', array(
    'render' => 'badge/view.php', // Relative to blocks/
    // ...
));
```

### 2. Inline HTML

For simple blocks, you can use inline HTML with placeholders.

**Available placeholders:**

- `{{content}}` or `$content`: Inner content of the block
- `{{attributeName}}` or `$attributeName`: Attribute value

**Example:**

```php
gw_register_block('badge', array(
    'render' => '<span class="badge">{{label}}</span>',
    'fields' => array(
        'label' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Label',
        ),
    ),
));
```

## User Interface

### Inspector Tabs

You can organize fields into tabs within the inspector.

```php
gw_register_block('my-block', array(
    'fields' => array(
        'title' => array(/* ... */),
        'description' => array(/* ... */),
        'color' => array(/* ... */),
        'size' => array(/* ... */),
    ),
    'ui' => array(
        'tabs' => array(
            array(
                'name' => 'content',
                'title' => 'Content',
                'fields' => array('title', 'description'),
            ),
            array(
                'name' => 'style',
                'title' => 'Style',
                'fields' => array('color', 'size'),
            ),
        ),
    ),
));
```

### Custom Toolbar

You can add buttons to the block toolbar.

```php
gw_register_block('my-block', array(
    'ui' => array(
        'toolbar' => array(
            array(
                'title' => 'Options',
                'controls' => array(
                    array(
                        'type' => 'toggle',
                        'label' => 'Active',
                        'icon' => 'yes',
                        'attribute' => 'active',
                    ),
                    array(
                        'type' => 'value',
                        'label' => 'Small',
                        'icon' => 'minus',
                        'attribute' => 'size',
                        'value' => 'small',
                    ),
                ),
            ),
        ),
    ),
));
```

**Toolbar control types:**

- **`toggle`**: Toggles a boolean attribute
- **`value`**: Sets an attribute to a specific value
- **`button`**: Generic button (requires custom `onClick`)

**Control properties:**

- `type`: Control type (`toggle`, `value`, `button`)
- `label`: Button label
- `icon`: WordPress Dashicons icon
- `attribute` or `attr`: Name of the attribute to modify
- `value`: Value to set (for `value` type)
- `isActive`: Function or boolean value to determine if active
- `onClick`: Custom function `function({ attributes, setAttributes }) {}`

## Auto-discovery

You can automatically register multiple blocks if they follow a specific structure.

### Function: `gw_register_blocks_autodiscover($dir)`

Discovers and registers blocks automatically by searching for `view.php` files in subdirectories.

**Directory structure:**

```
blocks/
  ├── my-block-1/
  │   └── view.php
  ├── my-block-2/
  │   └── view.php
  └── another-block/
      └── view.php
```

**Usage:**

```php
// Discover blocks in blocks/ (default)
gw_register_blocks_autodiscover();

// Or specify a custom directory
gw_register_blocks_autodiscover(get_template_directory() . '/custom-blocks');
```

Each directory becomes the block slug, and the title is automatically generated from the slug.

## Meta Fields

Blocks can work with post meta fields instead of block attributes.

### Configuration

Add `'source' => 'meta'` to the field and specify the meta key:

```php
gw_register_block('my-block', array(
    'fields' => array(
        'subtitle' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Subtitle',
            'source' => 'meta',
            'metaKey' => '_my_subtitle', // Optional, uses field name if not specified
        ),
    ),
));
```

**Note:** Meta fields require that the post type supports meta fields and that `useEntityProp` is available in the editor.

## Complete Examples

### Example 1: Simple Badge

```php
gw_register_block('badge', array(
    'name' => 'Badge',
    'category' => 'custom',
    'icon' => 'admin-appearance',
    'render' => '<span class="badge">{{label}}</span>',
    'fields' => array(
        'label' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Text',
            'default' => 'New',
        ),
    ),
));
```

### Example 2: Card with Tabs

```php
gw_register_block('card', array(
    'name' => 'Card',
    'category' => 'custom',
    'render' => 'card/view.php',
    'fields' => array(
        'title' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Title',
        ),
        'description' => array(
            'type' => 'string',
            'control' => 'textarea',
            'label' => 'Description',
        ),
        'image' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Image URL',
        ),
        'background_color' => array(
            'type' => 'string',
            'control' => 'color',
            'label' => 'Background Color',
        ),
    ),
    'ui' => array(
        'tabs' => array(
            array(
                'name' => 'content',
                'title' => 'Content',
                'fields' => array('title', 'description', 'image'),
            ),
            array(
                'name' => 'style',
                'title' => 'Style',
                'fields' => array('background_color'),
            ),
        ),
    ),
));
```

### Example 3: Gallery Block

```php
gw_register_block('image-gallery', array(
    'name' => 'Image Gallery',
    'category' => 'custom',
    'render' => 'image-gallery/view.php',
    'fields' => array(
        'images' => array(
            'type' => 'string',
            'control' => 'gallery',
            'label' => 'Images',
            'default' => '',
        ),
        'columns' => array(
            'type' => 'number',
            'control' => 'select',
            'label' => 'Columns',
            'options' => array(
                array('label' => '1 Column', 'value' => 1),
                array('label' => '2 Columns', 'value' => 2),
                array('label' => '3 Columns', 'value' => 3),
                array('label' => '4 Columns', 'value' => 4),
            ),
            'default' => 3,
        ),
    ),
));
```

**Template (`blocks/image-gallery/view.php`):**

```php
<?php
$gallery_ids = isset($attributes['images']) ? $attributes['images'] : '';
$columns = isset($attributes['columns']) ? $attributes['columns'] : 3;
$image_urls = gw_get_gallery_urls($gallery_ids, 'large');
?>
<div class="image-gallery" style="columns: <?php echo esc_attr($columns); ?>;">
    <?php foreach ($image_urls as $url): ?>
        <div class="gallery-item">
            <img src="<?php echo esc_url($url); ?>" alt="" />
        </div>
    <?php endforeach; ?>
</div>
```

### Example 4: Block with Toolbar

```php
gw_register_block('alert', array(
    'name' => 'Alert',
    'category' => 'custom',
    'render' => 'alert/view.php',
    'fields' => array(
        'message' => array(
            'type' => 'string',
            'control' => 'textarea',
            'label' => 'Message',
        ),
        'type' => array(
            'type' => 'string',
            'control' => 'select',
            'label' => 'Type',
            'options' => array(
                array('label' => 'Info', 'value' => 'info'),
                array('label' => 'Warning', 'value' => 'warning'),
                array('label' => 'Error', 'value' => 'error'),
                array('label' => 'Success', 'value' => 'success'),
            ),
            'default' => 'info',
        ),
        'dismissible' => array(
            'type' => 'boolean',
            'control' => 'toggle',
            'label' => 'Dismissible',
            'default' => false,
        ),
    ),
    'ui' => array(
        'toolbar' => array(
            array(
                'title' => 'Type',
                'controls' => array(
                    array(
                        'type' => 'value',
                        'label' => 'Info',
                        'icon' => 'info',
                        'attribute' => 'type',
                        'value' => 'info',
                    ),
                    array(
                        'type' => 'value',
                        'label' => 'Warning',
                        'icon' => 'warning',
                        'attribute' => 'type',
                        'value' => 'warning',
                    ),
                    array(
                        'type' => 'value',
                        'label' => 'Error',
                        'icon' => 'dismiss',
                        'attribute' => 'type',
                        'value' => 'error',
                    ),
                    array(
                        'type' => 'value',
                        'label' => 'Success',
                        'icon' => 'yes',
                        'attribute' => 'type',
                        'value' => 'success',
                    ),
                ),
            ),
        ),
    ),
));
```

### Example 5: Block with Icon Picker

```php
gw_register_block('icon-button', array(
    'name' => 'Icon Button',
    'category' => 'custom',
    'render' => 'icon-button/view.php',
    'fields' => array(
        'text' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'Button Text',
            'default' => 'Click Me',
        ),
        'icon' => array(
            'type' => 'string',
            'control' => 'icon_picker',
            'label' => 'Icon',
            'libraries' => array('uicons-rr'), // Optional: restrict to specific libraries
            'default' => '',
        ),
        'url' => array(
            'type' => 'string',
            'control' => 'text',
            'label' => 'URL',
            'default' => '#',
        ),
    ),
));
```

**Template (`blocks/icon-button/view.php`):**
```php
<?php
$text = isset($attributes['text']) ? $attributes['text'] : 'Click Me';
$icon_value = isset($attributes['icon']) ? $attributes['icon'] : '';
$url = isset($attributes['url']) ? $attributes['url'] : '#';
?>
<a href="<?php echo esc_url($url); ?>" class="icon-button">
    <?php if (!empty($icon_value)): ?>
        <?php echo gw_icon($icon_value, array('class' => 'icon-button-icon')); ?>
    <?php endif; ?>
    <span class="icon-button-text"><?php echo esc_html($text); ?></span>
</a>
```

### Example 6: Block with Term Select (Multiple Categories)

```php
gw_register_block('category-list', array(
    'name' => 'Category List',
    'category' => 'custom',
    'render' => 'category-list/view.php',
    'fields' => array(
        'categories' => array(
            'type' => 'string',
            'control' => 'term_select',
            'label' => 'Categories',
            'taxonomy' => 'category',
            'multiple' => true,
            'args' => array(
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ),
            'default' => '',
        ),
        'show_count' => array(
            'type' => 'boolean',
            'control' => 'toggle',
            'label' => 'Show Post Count',
            'default' => false,
        ),
    ),
));
```

**Template (`blocks/category-list/view.php`):**
```php
<?php
$term_ids = isset($attributes['categories']) ? $attributes['categories'] : '';
$show_count = isset($attributes['show_count']) ? $attributes['show_count'] : false;
$terms = gw_get_term_names($term_ids, 'category');
?>
<?php if (!empty($terms)): ?>
    <ul class="category-list">
        <?php foreach ($terms as $term): ?>
            <li>
                <a href="<?php echo esc_url(get_term_link($term['id'])); ?>">
                    <?php echo esc_html($term['name']); ?>
                </a>
                <?php if ($show_count): ?>
                    <span class="count">(<?php echo get_term($term['id'])->count; ?>)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
```

## Technical Notes

### JavaScript Dependencies

The editor script requires the following WordPress dependencies:

- `wp-blocks`
- `wp-element`
- `wp-i18n`
- `wp-components`
- `wp-block-editor`
- `wp-server-side-render`
- `wp-data`
- `wp-core-data`

### WordPress Support

The system uses WordPress Block API version 2 (`api_version: 2`).

### Core Attributes

If you enable support for core features like `anchor` or `customClassName`, the corresponding attributes are automatically added.

### Editor Styles

You can load CSS styles specific to the editor using `editor_styles`. The path can be:

- Relative to theme: `'assets/css/editor.css'`
- Absolute within theme: `/full/path/to/file.css`
- Full URL: `'https://example.com/styles.css'`

### Frontend Styles and Scripts

You can load CSS and JavaScript files on the frontend that will be automatically enqueued only when the block is present on the page using `style` and `script` parameters. WordPress automatically detects block usage and enqueues these assets.

**Features:**
- Assets are automatically enqueued only when the block is present
- No need for custom detection functions
- Uses WordPress native block asset management
- Supports the same path formats as `editor_styles`

**Example:**

```php
gw_register_block('my-block', array(
    'name' => 'My Block',
    'render' => 'my-block/view.php',
    'editor_styles' => 'assets/css/editor.css',  // Editor only
    'style' => 'assets/css/frontend.css',        // Frontend CSS (auto-enqueued)
    'script' => 'assets/js/frontend.js',         // Frontend JS (auto-enqueued)
    'fields' => array(/* ... */),
));
```

The paths can be:
- Relative to theme: `'assets/css/frontend.css'`
- Absolute within theme: `/full/path/to/file.css`
- Full URL: `'https://example.com/styles.css'`

## Troubleshooting

### Block doesn't appear in the editor

1. Verify that the PHP file is included correctly
2. Make sure `gw_register_block()` is called in the correct hook (`init` or `after_setup_theme`)
3. Check the browser console for JavaScript errors
4. Verify that `GW_CUSTOM_BLOCKS` is available in JavaScript

### Controls don't work

1. Verify that field names match the attributes
2. Make sure the control type is valid
3. Check that default values are of the correct type

### Preview doesn't show

1. Verify that `ServerSideRender` is available
2. Make sure the PHP template exists and is accessible
3. Check PHP logs for rendering errors

## References

- [WordPress Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Block API Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/)

