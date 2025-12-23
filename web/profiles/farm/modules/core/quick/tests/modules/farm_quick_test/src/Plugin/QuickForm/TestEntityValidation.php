<?php

declare(strict_types=1);

namespace Drupal\farm_quick_test\Plugin\QuickForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_quick\Plugin\QuickForm\QuickFormBase;
use Drupal\farm_quick\Traits\QuickAssetTrait;

/**
 * Test Entity Validation quick form.
 *
 * @QuickForm(
 *   id = "test_entity_validation",
 *   label = @Translation("Test entity validation quick form"),
 *   description = @Translation("Test entity validation quick form description."),
 *   helpText = @Translation("Test entity validation quick form help text."),
 *   permissions = {
 *     "create test log",
 *   }
 * )
 */
class TestEntityValidation extends QuickFormBase {

  use QuickAssetTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $id = NULL) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Try to create an entity that will fail validation.
    $this->createAsset([
      'type' => 'test',
      'name' => 'Test',
      'fail' => TRUE,
    ]);
  }

}
