<?php

namespace Drupal\search_api_spellcheck\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\Plugin\views\filter\SearchApiFulltext;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Provides an area for messages.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("search_api_spellcheck")
 */
class SpellCheck extends AreaPluginBase {

  /**
   * The available filters for the current view.
   *
   * @var array
   */
  private $filters;

  /**
   * The current query parameters.
   *
   * @var array
   */
  private $currentQuery;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['search_api_spellcheck_title']['default'] = '';
    $options['search_api_spellcheck_hide_on_result']['default'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['search_api_spellcheck_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The title to announce the suggestions. Default: suggestions:'),
      '#default_value' => isset($this->options['search_api_spellcheck_title']) ? $this->options['search_api_spellcheck_title'] : '',
    );

    $form['search_api_spellcheck_hide_on_result'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide when the view has results.'),
      '#default_value' => isset($this->options['search_api_spellcheck_hide_on_result']) ? $this->options['search_api_spellcheck_hide_on_result'] : TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery() {
    /** @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $query */
    $query = $this->query;
    $query->setOption('search_api_spellcheck', TRUE);
    parent::preQuery();
  }

  /**
   * Render the area.
   *
   * @param bool $empty
   *   (optional) Indicator if view result is empty or not. Defaults to FALSE.
   *
   * @return array
   *   In any case we need a valid Drupal render array to return.
   */
  public function render($empty = FALSE) {
    if ($this->options['search_api_spellcheck_hide_on_result'] == FALSE || ($this->options['search_api_spellcheck_hide_on_result'] && $empty)) {
      $result = $this->query->getSearchApiResults();
      // Check if extraData is there.
      if ($extra_data = $result->getExtraData('search_api_solr_response')) {
        // Initialize our array.
        $suggestions = [];
        // Check that we have suggestions.
        $keys = $this->view->getExposedInput()['keys'];

        $new_data = [];
        if (!empty($extra_data['spellcheck']['suggestions'])) {
          foreach ($extra_data['spellcheck']['suggestions'] as $key => $value) {
            if (is_string($value)) {
              $new_data[$key] = [
                'error' => $value,
                'suggestion' => $extra_data['spellcheck']['suggestions'][$key + 1]['suggestion'][0],
              ];
            }
          }
        }

        foreach ($new_data as $datum) {
          $keys = str_replace($datum['error'], $datum['suggestion'], $keys);
        }

        $output = [
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('Did you mean: '),
          ],
          [
            '#type' => 'link',
            '#title' => str_replace('+', ' ', $keys),
            '#url' => Url::fromRoute('<current>', [], ['query' => ['keys' => str_replace(' ', '+', $keys)]]),
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $this->t('?'),
          ],
        ];
        return $output;

      }
    }
  }

  /**
   * Gets the current query parameters.
   *
   * @return array
   *   Key value of parameters.
   */
  protected function getCurrentQuery() {
    if (NULL === $this->currentQuery) {
      $this->currentQuery = \Drupal::request()->query->all();
    }
    return $this->currentQuery;
  }

  /**
   * Gets a list of filters.
   *
   * @return array
   *   The filters by key value.
   */
  protected function getFilters() {
    if (NULL === $this->filters) {
      $this->filters = [];
      $exposed_input = $this->view->getExposedInput();
      foreach ($this->view->filter as $key => $filter) {
        if ($filter instanceof SearchApiFulltext) {
          // The filter could be different then the key.
          if (!empty($filter->options['expose']['identifier'])) {
            $key = $filter->options['expose']['identifier'];
          }
          $this->filters[$key] = !empty($exposed_input[$key]) ? $exposed_input[$key] : FALSE;
        }
      }
    }
    return $this->filters;
  }

  /**
   * Gets the matching filter for the suggestion.
   *
   * @param array $suggestion
   *   The suggestion array.
   *
   * @return bool|string
   *   False or the matching filter.
   */
  private function getFilterMatch(array $suggestion) {
    if ($index = array_search($suggestion[0], $this->getFilters(), TRUE)) {
      // @todo: Better validation.
      if (!empty($suggestion[1]['suggestion'][0])) {
        return [$index => $suggestion[1]['suggestion'][0]];
      }
    }
  }

  /**
   * Gets the suggestion label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The suggestion label translated.
   */
  private function getSuggestionLabel() {
    return !empty($this->options['search_api_spellcheck_title']) ? $this->options['search_api_spellcheck_title'] : $this->t('Suggestions:');
  }

}
