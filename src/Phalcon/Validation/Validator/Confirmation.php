<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Validator\Confirmation
 *
 * Checks that two values have the same value
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Confirmation;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "password",
 *     new Confirmation(
 *         [
 *             "message" => "Password doesn't match confirmation",
 *             "with"    => "confirmPassword",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "password",
 *         "email",
 *     ],
 *     new Confirmation(
 *         [
 *             "message" => [
 *                 "password" => "Password doesn't match confirmation",
 *                 "email"    => "Email doesn't match confirmation",
 *             ],
 *             "with" => [
 *                 "password" => "confirmPassword",
 *                 "email"    => "confirmEmail",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class Confirmation extends Validator
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
        if (!is_string($field) && !is_null($field)) {
            throw new Exception('Invalid parameter type.');
        }

        $fieldWith = $this->getOption("with");

        if (is_array($fieldWith)) {
            $fieldWith = $fieldWith[$field];
        }

        $value = $validation->getValue($field);
        $valueWith = $validation->getValue($fieldWith);

        if (!$this->compare($value, $valueWith)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Confirmation");
            $code = $this->prepareCode($field);

            $labelWith = $this->getOption("labelWith");
            if (is_array($labelWith)) {
                $labelWith = $labelWith[$fieldWith];
            }
            if (empty ($labelWith)) {
                $labelWith = $validation->getLabel($fieldWith);
            }

            $replacePairs[':field'] = $label;
            $replacePairs[":with"] = $labelWith;

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Confirmation",
                    $code
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Compare strings
     * @param string $a
     * @param string $b
     * @return boolean
     * @throws Exception
     */
    protected final function compare($a, $b)
    {
        if(!is_string($a) || !is_string($b)){
            throw new Exception('Invalid parameter type.');
        }
        if ($this->getOption("ignoreCase", false)) {
            /**
             * mbstring is required here
             */
            if (!function_exists("mb_strtolower")) {
                throw new Exception("Extension 'mbstring' is required");
            }

            $a = mb_strtolower($a, "utf-8");
            $b = mb_strtolower($b, "utf-8");
        }
        return $a == $b;
    }

}
