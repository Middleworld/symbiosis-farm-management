<?php

declare(strict_types=1);

namespace Drupal\farm_quick_test\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FailTest constraint.
 */
class FailTestConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value->value === TRUE) {
      // PHPStan level 2+ throws the following error on the next line:
      // Access to an undefined property
      // Symfony\Component\Validator\Constraint::$message.
      // We ignore this because we are following Drupal core's pattern.
      // @phpstan-ignore property.notFound
      $this->context->addViolation($constraint->message);
    }
  }

}
