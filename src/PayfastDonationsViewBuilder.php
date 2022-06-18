<?php

namespace Drupal\payfast_donation_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a payfast donations entity type.
 */
class PayfastDonationsViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The payfast donations has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
