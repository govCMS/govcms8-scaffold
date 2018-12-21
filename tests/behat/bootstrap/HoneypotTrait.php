<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Trait HoneypotTrait.
 */
trait HoneypotTrait {

  /**
   * @BeforeScenario @nohoneypot
   */
  public function beforeEmailScenario(BeforeScenarioScope $scope) {
    $config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $original_time_limit = $config->get('time_limit');
    \Drupal::state()->set('honeypot_time_limit_original', $original_time_limit);
    $config->set('time_limit', 0)->save();
  }

  /**
   * @AfterScenario @nohoneypot
   */
  public function afterEmailScenario(AfterScenarioScope $scope) {
    $original_time_limit = \Drupal::state()->get('honeypot_time_limit_original');
    $config = \Drupal::configFactory()->getEditable('honeypot.settings');
    if ($original_time_limit) {
      $config->set('time_limit', $original_time_limit)->save();
    }

    \Drupal::state()->delete('honeypot_time_limit_original');
  }

}
