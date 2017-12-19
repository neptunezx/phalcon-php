<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation\Message;
use \Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\Between
 *
 * Validates that a value is between a range of two values
 *
 * <code>
 * use Phalcon\Validation\Validator\Between;
 *
 * $validator->add('name', new Between(array(
 *   'minimum' => 0,
 *   'maximum' => 100,
 *   'message' => 'The price must be between 0 and 100'
 * )));
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/between.c
 */
class Between extends Validator implements ValidatorInterface
{

    /**
     * Executes the validation
     *
     * @param \Phalcon\Validation $validator
     * @param string $attribute
     * @return boolean
     * @throws Exception
     */
    public function validate($validator, $attribute)
    {
        if (is_object($validator) === false ||
            $validator instanceof Validation === false) {
            throw new Exception('Invalid parameter type.');
        }

        $value   = $validator->getValue($attribute);
        $minimum = $this->getOption('minimum');
        $maximum = $this->getOption('maximum');

        if ($value <= $minimum || $value >= $maximum) {
            $messageStr = $this->getOption('message');
            if (empty($messageStr) === true) {
                $messageStr = $attribute . ' is not between a valid range';
            }

            $validator->appendMessage(new Message($messageStr, $attribute, 'Between'));

            return false;
        }

        return true;
    }

}
