/* global wp */
(function () {
  'use strict';
  
  if (typeof GWCBlocks === 'undefined') {
    window.GWCBlocks = {};
  }

  // Extract WordPress dependencies
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

  // Export dependencies
  GWCBlocks.deps = {
    registerBlockType,
    __,
    useMemo,
    useState,
    useEffect,
    InspectorControls,
    BlockControls,
    useBlockProps,
    MediaUpload,
    MediaUploadCheck,
    InnerBlocks,
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
    ServerSideRender,
    useEntityProp,
    dataSelect,
    element: wp.element
  };
})();

