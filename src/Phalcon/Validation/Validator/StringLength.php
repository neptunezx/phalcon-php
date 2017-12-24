<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

/**
 * Phalcon\Validation\Validator\StringLength
 *
 * Validates that a string has the specified maximum and minimum constraints
 * The test is passed if for a string's length L, min<=L<=max, i.e. L must
 * be at least min, and at most max.
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\StringLength as StringLength;
 *
 * $validator = new Validation();
 *
 * $validation->add(
 *     "name_last",
 *     new StringLength(
 *         [
 *             "max"            => 50,
 *             "min"            => 2,
 *             "messageMaximum" => "We don't like really long names",
 *             "messageMinimum" => "We want more than just their initials",
 *         ]
 *     )
 * );
 *
 * $validation->add(
 *     [
 *         "name_last",
 *         "name_first",
 *     ],
 *     new StringLength(
 *         [
 *             "max" => [
 *                 "name_last"  => 50,
 *                 "name_first" => 40,
 *             ],
 *             "min" => [
 *                 "name_last"  => 2,
 *                 "name_first" => 4,
 *             ],
 *             "messageMaximum" => [
 *                 "name_last"  => "We don't like really long last names",
 *                 "name_first" => "We don't like really long first names",
 *             ],
 *             "messageMinimum" => [
 *                 "name_last"  => "We don't like too short last names",
 *                 "name_first" => "We don't like too short first names",
 *             ]
 *         ]
 *     )
 * );
 * </code>
 */
class StringLength extends Validator
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
        // At least one of 'min' or 'max' must be set
        $isSetMin = $this->hasOption("min");
        $isSetMax = $this->hasOption("max");

        if (!$isSetMin && !$isSetMax) {
            throw new Exception("A minimum or maximum must be set");
        }

        $value = $validation->getValue($field);
        $label = $this->prepareLabel($validation, $field);
        $code = $this->prepareCode($field);

        // Check if mbstring is available to calculate the correct length
        if (function_exists("mb_strlen")) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        /**
         * Maximum length
         */
        if ($isSetMax) {

            $maximum = $this->getOption("max");
            if (is_array($maximum)) {
                $maximum = $maximum[$field];
            }
            if ($length > $maximum) {
                $message = $this->prepareMessage($validation, $field, "TooLong", "messageMaximum");
                $replacePairs = array(":field" => $label, ":max" => $maximum);

                $validation->appendMessage(
                    new Message(
                        strtr($message, $replacePairs),
                        $field,
                        "TooLong",
                        $code
                    )
                );

                return false;
            }
        }

        /**
         * Minimum length
         */
        if ($isSetMin) {
            $minimum = $this->getOption("min");
            if (is_array($minimum)) {
                $minimum = $minimum[$field];
            }
            if ($length < $minimum) {
                $message = $this->prepareMessage($validation, $field, "TooShort", "messageMinimum");
                $replacePairs = array(":field" => $label, ":min" => $minimum);

                $validation->appendMessage(
                    new Message(
                        strtr($message, $replacePairs),
                        $field,
                        "TooShort",
                        $code
                    )
                );
                return false;
            }
        }
        return true;
    }
}
