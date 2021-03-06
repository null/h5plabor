<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\argument_default\CurrentUser.
 */

namespace Views\user\Plugin\views\argument_default;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the global $user
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @Plugin(
 *   id = "current_user",
 *   module = "user",
 *   title = @Translation("User ID from logged in user")
 * )
 */
class CurrentUser extends ArgumentDefaultPluginBase {

  function get_argument() {
    global $user;
    return $user->uid;
  }

}
