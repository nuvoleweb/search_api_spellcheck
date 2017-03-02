<?php

/**
 * @file
 * Views hook implementations for the Search API spellcheck module.
 */

/**
 * Implements hook_views_data().
 */
function search_api_spellcheck_views_data() {
  // @todo: Only enable for search api views.
  $data['views']['search_api_spellcheck'] = [
    'title' => t('Search api spellcheck'),
    'help' => t('Displays spell check suggestions.'),
    'area' => [
      'id' => 'search_api_spellcheck',
    ],
  ];

  return $data;
}
