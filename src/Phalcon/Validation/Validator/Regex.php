<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\ValidatorInterface;
use \Phalcon\Validation\Message;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\Regex
 *
 * Allows validate if the value of a field matches a regular expression
 *
 * <code>
 * use Phalcon\Validation\Validator\Regex as RegexValidator;
 *
 * $validator->add('created_at', new RegexValidator(array(
 *   'pattern' => '/^[0-9]{4}[-\/](0[1-9]|1[12])[-\/](0[1-9]|[12][0-9]|3[01])$/',
 *   'message' => 'The creation date is invalid'
 * )));
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/validator/regex.c
 */
class Regex extends Validator implements ValidatorInterface
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

        //the regular expression is set in the option 'pattern'
        $pattern = $this->getOption('pattern');

        //Check if the value matches using preg_match in the PHP userland
        $matchPattern = preg_match($pattern, $value, $matches);

        if ($matchPattern === true) {
            $failed = ($matches[0] !== $value);
        } else {
            $failed = true;
        }

        if ($failed === true) {
            //Check if the developer has defined a custom message
            $message = $this->getOption('message');

            if (empty($message) === true) {
                $message = "Value of field '" . $attribute . "' doesn't match regular expression";
            }

            $validator->appendMessage(new Message($message, $attribute, 'Regex'));

            return false;
        }

        return true;
    }

}
