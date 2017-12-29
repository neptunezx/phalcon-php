<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

/**
 * Phalcon\Validation\Validator\PresenceOf
 *
 * Validates that a value is not null or empty string
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\PresenceOf;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "name",
 *     new PresenceOf(
 *         [
 *             "message" => "The name is required",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "name",
 *         "email",
 *     ],
 *     new PresenceOf(
 *         [
 *             "message" => [
 *                 "name"  => "The name is required",
 *                 "email" => "The email is required",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class PresenceOf extends Validator
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
        if ($value === null || $value === "") {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "PresenceOf");
            $code = $this->prepareCode($field);

            $replacePairs = array(':field' => $label);

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "PresenceOf",
                    $code
                )
            );

            return false;
        }

        return true;
    }
}
