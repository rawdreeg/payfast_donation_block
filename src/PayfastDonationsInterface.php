<?php

namespace Drupal\payfast_donation_block;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a payfast donations entity type.
 */
interface PayfastDonationsInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the payfast donations title.
   *
   * @return string
   *   Title of the payfast donations.
   */
  public function getTitle();

  /**
   * Sets the payfast donations title.
   *
   * @param string $title
   *   The payfast donations title.
   *
   * @return \Drupal\payfast_donation_block\PayfastDonationsInterface
   *   The called payfast donations entity.
   */
  public function setTitle($title);

  /**
   * Gets the payfast donations creation timestamp.
   *
   * @return int
   *   Creation timestamp of the payfast donations.
   */
  public function getCreatedTime();

  /**
   * Sets the payfast donations creation timestamp.
   *
   * @param int $timestamp
   *   The payfast donations creation timestamp.
   *
   * @return \Drupal\payfast_donation_block\PayfastDonationsInterface
   *   The called payfast donations entity.
   */
  public function setCreatedTime($timestamp);

}
