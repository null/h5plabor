<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\access\Permission.
 */

namespace Views\user\Plugin\views\access;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Annotation\Translation;

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @Plugin(
 *   id = "perm",
 *   title = @Translation("Permission"),
 *   help = @Translation("Access will be granted to users with the specified permission string.")
 * )
 */
class Permission extends AccessPluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  public function access($account) {
    return views_check_perm($this->options['perm'], $account);
  }

  function get_access_callback() {
    return array('views_check_perm', array($this->options['perm']));
  }

  public function summaryTitle() {
    $permissions = module_invoke_all('permission');
    if (isset($permissions[$this->options['perm']])) {
      return $permissions[$this->options['perm']]['title'];
    }

    return t($this->options['perm']);
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['perm'] = array('default' => 'access content');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $perms = array();
    $module_info = system_get_info('module');

    // Get list of permissions
    foreach (module_implements('permission') as $module) {
      $permissions = module_invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }

    ksort($perms);

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => t('Only users with the selected permission flag will be able to access this display. Note that users with "access all views" can see any view, regardless of other permissions.'),
    );
  }

}
