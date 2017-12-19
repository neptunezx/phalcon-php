<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\Email
 *
 * Checks if a value has a correct e-mail format
 *
 * <code>
 * use Phalcon\Validation\Validator\Email as EmailValidator;
 *
 * $validator->add('email', new EmailValidator(array(
 *   'message' => 'The e-mail is not valid'
 * )));
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/email.c
 */
class Email extends Validator implements ValidatorInterface
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
        if (filter_var($value, \FILTER_VALIDATE_EMAIL) === false) {
            $message = $this->getOption('message');
            if (empty($message) === true) {
                $message = "Value of field '" . $attribute . "' must have a valid e-mail format";
            }

            $validator->appendMessage(new Message($message, $attribute, 'Email'));

            return false;
        }

        return true;
    }

}
