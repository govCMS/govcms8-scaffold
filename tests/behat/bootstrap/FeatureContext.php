<?php

/**
 * @file
 * VAHI/SCV Drupal context for Behat testing.
 */

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\DrupalContext;
use IntegratedExperts\BehatSteps\D8\ContentTrait;
use IntegratedExperts\BehatSteps\FieldTrait;
use IntegratedExperts\BehatSteps\LinkTrait;
use IntegratedExperts\BehatSteps\PathTrait;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\Language\Language;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use FieldTrait;
  use HoneypotTrait;
  use LinkTrait;
  use PathTrait;

  /**
   * {@inheritdoc}
   */
  public function assertAuthenticatedByRole($role) {
    // Override parent assertion to allow using 'anonymous user' role without
    // actually creating a user with role. By default,
    // assertAuthenticatedByRole() will create a user with 'authenticated role'
    // even if 'anonymous user' role is provided.
    if ($role == 'anonymous user') {
      if (!empty($this->loggedInUser)) {
        $this->logout();
      }
    }
    else {
      parent::assertAuthenticatedByRole($role);
    }
  }

  /**
   * @Given managed file:
   */
  public function fileCreateManaged(TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      if (empty($node->path)) {
        throw new \RuntimeException('"path" property is required');
      }
      $path = ltrim($node->path, '/');

      // Get fixture file path.
      if ($this->getMinkParameter('files_path')) {
        $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
        if (is_file($full_path)) {
          $path = $full_path;
        }
      }

      if (!is_readable($path)) {
        throw new \RuntimeException('Unable to find file ' . $path);
      }

      $destination = 'public://' . basename($path);
      $file = file_save_data(file_get_contents($path), $destination, FILE_EXISTS_REPLACE);

      if (!$file) {
        throw new \RuntimeException('Unable to save managed file ' . $path);
      }
    }
  }

  /**
   * @Given /^I select "([^"]*)" from "([^"]*)" chosen select$/
   */
  public function iSelectFromChosenSelect($option, $select) {
    $select = $this->fixStepArgument($select);
    $option = $this->fixStepArgument($option);

    $page = $this->getSession()->getPage();
    $field = $page->findField($select, TRUE);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value', $select);
    }

    $id = $field->getAttribute('id');
    $opt = $field->find('named', ['option', $option]);
    $val = $opt->getValue();

    $javascript = "jQuery('#$id').val('$val');
                  jQuery('#$id').trigger('chosen:updated');
                  jQuery('#$id').trigger('change');";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ").
   */
  protected function fixStepArgument($argument) {
    return str_replace('\\"', '"', $argument);
  }

  /**
   * Checks if an option is present in the dropdown
   *
   * @Then /^I should see "([^"]*)" in the dropdown "([^"]*)"$/
   *
   * @param $value
   *   string The option string to be searched for
   * @param $field
   *   string The dropdown field selector
   * @param $fieldLabel
   *   string The label of the field in case $field is not a label
   */
  public function iShouldSeeInTheDropdown($value, $field, $fieldLabel = "") {
    if ($fieldLabel == "") {
      $fieldLabel = $field;
    }
    // Get the object of the dropdown field
    $dropDown = $this->getSession()->getPage()->findField($field);
    if (empty($dropDown)) {
      throw new \Exception('The page does not have the dropdown with label "' . $fieldLabel . '"');
    }
    // Get all the texts under the dropdown field
    $options = $dropDown->getText();
    if (strpos(trim($options), trim($value)) === FALSE) {
      throw new \Exception("The text " . $fieldLabel . " does not have the option " . $value . " " . $this->getSession()
          ->getCurrentUrl());
    }
  }

  /**
   * Checks if an option is not present in the dropdown
   *
   * @Then /^I should not see "([^"]*)" in the dropdown "([^"]*)"$/
   *
   * @param string $value
   *  The option string to be searched for
   * @param string $field
   *   The dropdown field label
   */
  public function iShouldNotSeeInTheDropdown($value, $field) {
    // get the object of the dropdown field
    $dropDown = $this->getSession()->getPage()->findField($field);
    if (empty($dropDown)) {
      throw new \Exception('The page does not have the dropdown with label "' . $field . '"');
    }
    // get all the texts under the dropdown field
    $options = $dropDown->getText();
    if (strpos(trim($options), trim($value)) !== FALSE) {
      throw new \Exception("The dropdown " . $field . " has the option " . $value . " but it should not be");
    }
  }

  /**
   * Checks, that form field with specified id|name|label|value has the <values>
   *
   * @param $field
   *    string The dropdown field selector
   * @param $table
   *    array The list of values to verify
   *
   * @Then /^I should see the following <values> in the dropdown "([^"]*)"$/
   */
  public function iShouldSeeTheFollowingValuesInTheDropdown($field, TableNode $table) {
    if (empty($table)) {
      throw new Exception("No values were provided");
    }
    foreach ($table->getHash() as $value) {
      $this->iShouldSeeInTheDropdown($value['values'], $field);
    }
  }

  /**
   * Checks, that form field with specified id|name|label|value don't have the
   * <values>
   *
   * @param $field
   *    string The dropdown field selector
   * @param $table
   *    array The list of values to verify
   *
   * @Then /^I should not see the following <values> in the dropdown "([^"]*)"$/
   */
  public function iShouldNotSeeTheFollowingValuesInTheDropdown($field, TableNode $table) {
    if (empty($table)) {
      throw new Exception("No values were provided");
    }
    foreach ($table->getHash() as $value) {
      $this->iShouldNotSeeInTheDropdown($value['values'], $field);
    }
  }

  /**
   * Selects the multiple dropdown(single select/multiple select) values
   *
   * @param $table
   *    array The list of values to verify
   * @When /^I select the following <fields> with <values>$/
   */
  public function iSelectTheFollowingFieldsWithValues(TableNode $table) {
    $multiple = TRUE;
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $select = $this->getSession()
        ->getPage()
        ->findField($table[$key]['fields']);
      if (empty($select)) {
        throw new \Exception("The page does not have the " . $table[$key]['fields'] . " field");
      }
      // The default true value for 'multiple' throws an error 'value cannot be an array' for single select fields
      $multiple = $select->getAttribute('multiple') ? TRUE : FALSE;
      $this->getSession()
        ->getPage()
        ->selectFieldOption($table[$key]['fields'], $table[$key]['values'], $multiple);
    }
  }

  /**
   * Checks if the given value is default selected in the given dropdown
   *
   * @param $option
   *   string The value to be looked for
   * @param $field
   *   string The dropdown field that has the value
   *
   * @Given /^I should see the option "([^"]*)" selected in "([^"]*)" dropdown$/
   */
  public function iShouldSeeTheOptionSelectedInDropdown($option, $field) {
    $selector = $field;
    // Some fields do not have label, so set the selector here
    if (strtolower($field) == "default notification") {
      $selector = "edit-projects-default";
    }
    $chk = $this->getSession()->getPage()->findField($field);
    // Make sure that the dropdown $field and the value $option exists in the dropdown
    $optionObj = $chk->findAll('xpath', '//option[@selected="selected"]');
    // Check if at least one value is selected
    if (empty($optionObj)) {
      throw new \Exception("The field '" . $field . "' does not have any options selected");
    }
    $found = FALSE;
    foreach ($optionObj as $opt) {
      if ($opt->getText() == $option) {
        $found = TRUE;
        break;
      }
    }
    if (!$found) {
      throw new \Exception("The field '" . $field . "' does not have the option '" . $option . "' selected");
    }
  }

  /**
   * @Then I log out
   */
  public function iLogOut() {
    parent::assertAnonymousUser();
  }

  /**
   * @Given media entities
   */
  public function mediaEntities(TableNode $table) {
    foreach ($table->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      // Create file first, so we can attach it to the media entity.
      if (empty($node->name)) {
        throw new \RuntimeException('"name" property is required');
      }
      if (empty($node->path)) {
        throw new \RuntimeException('"path" property is required');
      }
      if (empty($node->type)) {
        throw new \RuntimeException('"type" property is required - e.g. "image"');
      }
      $file_path = $path = ltrim($node->path, '/');

      $query = \Drupal::entityQuery('file');
      $query->condition('uri', $file_path, 'CONTAINS');
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        $file = File::load(reset($entity_ids));
      }

      if (empty($file)) {
        // Get fixture file path.
        if ($this->getMinkParameter('files_path')) {
          $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
          if (is_file($full_path)) {
            $path = $full_path;
          }
        }

        if (!is_readable($path)) {
          throw new \RuntimeException('Unable to find file ' . $path);
        }

        $destination = 'public://' . basename($path);
        $file = file_save_data(file_get_contents($path), $destination, FILE_EXISTS_REPLACE);

      }

      if (!$file) {
        throw new \RuntimeException('Unable to save managed file ' . $path);
      }

      // Pre-set field names
      switch ($node->type) {
        case 'image':
          $field_name = 'field_media_image';
          break;

        default:
          throw new \RuntimeException('Media entity type is not supported yet.');
          break;
      }

      // Create media.
      $media = Media::create([
        'bundle' => $node->type,
        'name' => $node->name,
        'langcode' => Language::LANGCODE_DEFAULT,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $node->alt,
          'title' => $node->title,
        ],
        'image' => $file_path,
      ]);
      $media->save();
    }
  }

  /**
   * @Given no media entities
   */
  public function noMediaEntities(TableNode $table) {
    foreach ($table->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      // Create file first, so we can attach it to the media entity.
      if (empty($node->name)) {
        throw new \RuntimeException('"name" property is required');
      }

      // Remove media.
      $media = Media::load([
        'bundle' => $node->type,
        'name' => $node->name,
      ]);
      $media->delete();
    }
  }

}
