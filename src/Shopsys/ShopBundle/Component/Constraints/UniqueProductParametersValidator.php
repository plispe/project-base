<?php

namespace Shopsys\ShopBundle\Component\Constraints;

use Shopsys\ShopBundle\Component\Constraints\UniqueProductParameters;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueProductParametersValidator extends ConstraintValidator {

	/**
	 * @param array $values
	 * @param \Symfony\Component\Validator\Constraint $constraint
	 */
	public function validate($values, Constraint $constraint) {
		if (!$constraint instanceof UniqueProductParameters) {
			throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException($constraint, UniqueCollection::class);
		}

		// Dummy validator, because validator is implemented in JS and
		// \Shopsys\ShopBundle\Component\Transformers\ProductParameterValueToProductParameterValuesLocalizedTransformer
		// throw exception on duplicate parameters
	}

}
