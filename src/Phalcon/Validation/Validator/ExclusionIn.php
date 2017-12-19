<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\ExclusionIn
 *
 * Check if a value is not included into a list of values
 *
 * <code>
 * use Phalcon\Validation\Validator\ExclusionIn;
 *
 * $validator->add('status', new ExclusionIn(array(
 *   'message' => 'The status must not be A or B',
 *   'domain' => array('A', 'B')
 * )));
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/exclusionin.c
 */
class ExclusionIn extends Validator implements ValidatorInterface
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

        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $value = $validator->getValue($attribute);

        //A domain is an array with a list of valid values
        $domain = $this->getOption('domain');
        if (is_array($domain) === false) {
            throw new Exception("Option 'domain' must be an array");
        }

        //Check if the value is contained in the array
        if (in_array($value, $domain) === true) {
            $message = $this->getOption('message');
            if (empty($message) === true) {
                $message = "Value of field '" . $attribute . "' must not be part of list: " . implode(', ', $domain);
            }

            $validator->appendMessage(new Message($message, $attribute, 'ExclusionIn'));

            return false;
        }

        return true;
    }

}
