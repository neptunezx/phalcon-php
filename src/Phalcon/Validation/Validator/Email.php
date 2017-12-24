<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\Message;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation;

/**
 * Phalcon\Validation\Validator\Email
 *
 * Checks if a value has a correct e-mail format
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Email as EmailValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "email",
 *     new EmailValidator(
 *         [
 *             "message" => "The e-mail is not valid",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "email",
 *         "anotherEmail",
 *     ],
 *     new EmailValidator(
 *         [
 *             "message" => [
 *                 "email"        => "The e-mail is not valid",
 *                 "anotherEmail" => "The another e-mail is not valid",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class Email extends Validator
{

    /**
     * Executes the validation
     *
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

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Email");
            $code = $this->prepareCode($field);
            $replacePairs[':field'] = $label;
            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Email",
                    $code
                )
            );

            return false;
        }

        return true;
    }

}
