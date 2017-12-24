<?php

namespace Phalcon\Validation\Validator;

use \Phalcon\Validation\Validator;
use \Phalcon\Validation\Message;
use \Phalcon\Validation\Exception;
use \Phalcon\Validation;


/**
 * Phalcon\Validation\Validator\InclusionIn
 *
 * Check if a value is included into a list of values
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\InclusionIn;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "status",
 *     new InclusionIn(
 *         [
 *             "message" => "The status must be A or B",
 *             "domain"  => ["A", "B"],
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "status",
 *         "type",
 *     ],
 *     new InclusionIn(
 *         [
 *             "message" => [
 *                 "status" => "The status must be A or B",
 *                 "type"   => "The status must be 1 or 2",
 *             ],
 *             "domain" => [
 *                 "status" => ["A", "B"],
 *                 "type"   => [1, 2],
 *             ]
 *         ]
 *     )
 * );
 * </code>
 */
class InclusionIn extends Validator
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

        /**
         * A domain is an array with a list of valid values
         */
        $domain = $this->getOption("domain");
        $fieldDomain = isset($domain[$field]) ? $domain[$field] : null;
        if (!is_null($fieldDomain)) {
            if (is_array($fieldDomain)) {
                $domain = $fieldDomain;
            }
        }
        if ($domain) {
            throw new Exception("Option 'domain' must be an array");
        }

        $strict = false;
        if ($this->hasOption("strict")) {
            $strict = $this->getOption("strict");

            if (is_array($strict)) {
                $strict = $strict[$field];
            }

            if (is_bool($strict)) {
                throw new Exception("Option 'strict' must be a boolean");
            }
        }

        /**
         * Check if the value is contained by the array
         */
        if (!in_array($value, $domain, $strict)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "InclusionIn");
            $code = $this->prepareCode($field);

            $replacePairs = array(':field' => $label, ":domain" => join(", ", $domain));

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "InclusionIn",
                    $code
                )
            );
            return false;
        }

        return true;
    }

}
