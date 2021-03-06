<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display\Block.
 * Definition of Views\block\Plugin\views\display\Block.
 */

namespace Views\block\Plugin\views\display;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @Plugin(
 *   id = "block",
 *   module = "block",
 *   title = @Translation("Block"),
 *   help = @Translation("Display the view as a block."),
 *   theme = "views_view",
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block")
 * )
 */
class Block extends DisplayPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['block_description'] = array('default' => '', 'translatable' => TRUE);
    $options['block_caching'] = array('default' => DRUPAL_NO_CACHE);

    return $options;
  }

  /**
   * The default block handler doesn't support configurable items,
   * but extended block handlers might be able to do interesting
   * stuff with it.
   */
  public function executeHookBlockList($delta = 0, $edit = array()) {
    $delta = $this->view->storage->name . '-' . $this->display['id'];
    $desc = $this->getOption('block_description');

    if (empty($desc)) {
      if ($this->display['display_title'] == $this->definition['title']) {
        $desc = t('View: !view', array('!view' => $this->view->storage->getHumanName()));
      }
      else {
        $desc = t('View: !view: !display', array('!view' => $this->view->storage->getHumanName(), '!display' => $this->display['display_title']));
      }
    }
    return array(
      $delta => array(
        'info' => $desc,
        'cache' => $this->getCacheType()
      ),
    );
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  public function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $info['content'] = $this->view->render();
    $info['subject'] = filter_xss_admin($this->view->getTitle());
    if (!empty($this->view->result) || $this->getOption('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $info;
    }
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::optionsSummary($categories, $options);

    $categories['block'] = array(
      'title' => t('Block settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $block_description = strip_tags($this->getOption('block_description'));
    if (empty($block_description)) {
      $block_description = t('None');
    }

    $options['block_description'] = array(
      'category' => 'block',
      'title' => t('Block name'),
      'value' => views_ui_truncate($block_description, 24),
    );

    $types = $this->blockCachingModes();
    $options['block_caching'] = array(
      'category' => 'other',
      'title' => t('Block caching'),
      'value' => $types[$this->getCacheType()],
    );
  }

  /**
   * Provide a list of core's block caching modes.
   */
  protected function blockCachingModes() {
    return array(
      DRUPAL_NO_CACHE => t('Do not cache'),
      DRUPAL_CACHE_GLOBAL => t('Cache once for everything (global)'),
      DRUPAL_CACHE_PER_PAGE => t('Per page'),
      DRUPAL_CACHE_PER_ROLE => t('Per role'),
      DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE => t('Per role per page'),
      DRUPAL_CACHE_PER_USER => t('Per user'),
      DRUPAL_CACHE_PER_USER | DRUPAL_CACHE_PER_PAGE => t('Per user per page'),
    );
  }

  /**
   * Provide a single method to figure caching type, keeping a sensible default
   * for when it's unset.
   */
  protected function getCacheType() {
    $cache_type = $this->getOption('block_caching');
    if (empty($cache_type)) {
      $cache_type = DRUPAL_NO_CACHE;
    }
    return $cache_type;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'block_description':
        $form['#title'] .= t('Block admin description');
        $form['block_description'] = array(
          '#type' => 'textfield',
          '#description' => t('This will appear as the name of this block in administer >> structure >> blocks.'),
          '#default_value' => $this->getOption('block_description'),
        );
        break;
      case 'block_caching':
        $form['#title'] .= t('Block caching type');

        $form['block_caching'] = array(
          '#type' => 'radios',
          '#description' => t("This sets the default status for Drupal's built-in block caching method; this requires that caching be turned on in block administration, and be careful because you have little control over when this cache is flushed."),
          '#options' => $this->blockCachingModes(),
          '#default_value' => $this->getCacheType(),
        );
        break;
      case 'exposed_form_options':
        $this->view->initHandlers();
        if (!$this->usesExposed() && parent::usesExposed()) {
          $form['exposed_form_options']['warning'] = array(
            '#weight' => -10,
            '#markup' => '<div class="messages warning">' . t('Exposed filters in block displays require "Use AJAX" to be set to work correctly.') . '</div>',
          );
        }
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state['section']) {
      case 'display_id':
        $this->updateBlockBid($form_state['view']->storage->name, $this->display['id'], $this->display['new_id']);
        break;
      case 'block_description':
        $this->setOption('block_description', $form_state['values']['block_description']);
        break;
      case 'block_caching':
        $this->setOption('block_caching', $form_state['values']['block_caching']);
        $this->saveBlockCache($form_state['view']->storage->name . '-'. $form_state['display_id'], $form_state['values']['block_caching']);
        break;
    }
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
      if ($this->isAJAXEnabled()) {
        return parent::usesExposed();
      }
      return FALSE;
    }

  /**
   * Update the block delta when you change the machine readable name of the display.
   */
  protected function updateBlockBid($name, $old_delta, $delta) {
    $old_hashes = $hashes = variable_get('views_block_hashes', array());

    $old_delta = $name . '-' . $old_delta;
    $delta = $name . '-' . $delta;
    if (strlen($old_delta) >= 32) {
      $old_delta = md5($old_delta);
      unset($hashes[$old_delta]);
    }
    if (strlen($delta) >= 32) {
      $md5_delta = md5($delta);
      $hashes[$md5_delta] = $delta;
      $delta = $md5_delta;
    }

    // Maybe people don't have block module installed, so let's skip this.
    if (db_table_exists('block')) {
      db_update('block')
        ->fields(array('delta' => $delta))
        ->condition('delta', $old_delta)
        ->execute();
    }

    // Update the hashes if needed.
    if ($hashes != $old_hashes) {
      variable_set('views_block_hashes', $hashes);
    }
  }

  /**
   * Save the block cache setting in the blocks table if this block allready
   * exists in the blocks table. Dirty fix untill http://drupal.org/node/235673 gets in.
   */
  protected function saveBlockCache($delta, $cache_setting) {
    if (strlen($delta) >= 32) {
      $delta = md5($delta);
    }
    if (db_table_exists('block') && $bid = db_query("SELECT bid FROM {block} WHERE module = 'views' AND delta = :delta", array(
        ':delta' => $delta))->fetchField()) {
      db_update('block')
        ->fields(array(
        'cache' => $cache_setting,
        ))
        ->condition('module', 'views')
        ->condition('delta', $delta)
        ->execute();
    }
  }

}
