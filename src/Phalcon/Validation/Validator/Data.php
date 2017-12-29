<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Validator\Date
 *
 * Checks if a value is a valid date
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Date as DateValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "date",
 *     new DateValidator(
 *         [
 *             "format"  => "d-m-Y",
 *             "message" => "The date is invalid",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "date",
 *         "anotherDate",
 *     ],
 *     new DateValidator(
 *         [
 *             "format" => [
 *                 "date"        => "d-m-Y",
 *                 "anotherDate" => "Y-m-d",
 *             ],
 *             "message" => [
 *                 "date"        => "The date is invalid",
 *                 "anotherDate" => "The another date is invalid",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class Data extends Validator
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

        $value = $validation->getValue($field);
        $format = $this->getOption("format");

        if (is_array($format)) {
            $format = $format[$field];
        }

        if (empty($format)) {
            $format = "Y-m-d";
        }

        if (!$this->checkDate($value, $format)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Date");
            $this->prepareCode($field);

            $replacePairs[':field'] = $label;

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Date"
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param $value
     * @param $format
     * @return boolean
     */
    private function checkDate($value, $format)
    {
        if (!is_string($value)) {
            return false;
        }
        \DateTime::createFromFormat($format, $value);
        $errors = \DateTime::getLastErrors();
        if ($errors["warning_count"] > 0 || $errors["error_count"] > 0) {
            return false;
        }

        return true;
    }


}
