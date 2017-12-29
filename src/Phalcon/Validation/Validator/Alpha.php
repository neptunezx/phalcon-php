<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation\Exception;
use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Phalcon\Validation\Validator\Alpha
 *
 * Check for alphabetic character(s)
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Alpha as AlphaValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "username",
 *     new AlphaValidator(
 *         [
 *             "message" => ":field must contain only letters",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "username",
 *         "name",
 *     ],
 *     new AlphaValidator(
 *         [
 *             "message" => [
 *                 "username" => "username must contain only letters",
 *                 "name"     => "name must contain only letters",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class Alpha extends Validator
{
    /**
     * Executes the validation
     * @param \Phalcon\Validation $validation
     * @param string $field
     * @return boolean
     * @throws Exception
     */
    public function validate($validation = null, $field = null)
    {
        if (is_object($validation) === false ||
            $validation instanceof Validation === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($field) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $value = $validation->getValue($field);
        if (preg_match("/[^[:alpha:]]/imu", $value)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Alpha");
            $code = $this->prepareCode($field);
            $replacePairs[':field'] = $label;
            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Alpha",
                    $code
                )
            );

            return false;
        }

        return true;
    }


}
