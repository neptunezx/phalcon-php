<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Validator\Between
 *
 * Validates that a value is between an inclusive range of two values.
 * For a value x, the test is passed if minimum<=x<=maximum.
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Between;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "price",
 *     new Between(
 *         [
 *             "minimum" => 0,
 *             "maximum" => 100,
 *             "message" => "The price must be between 0 and 100",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "price",
 *         "amount",
 *     ],
 *     new Between(
 *         [
 *             "minimum" => [
 *                 "price"  => 0,
 *                 "amount" => 0,
 *             ],
 *             "maximum" => [
 *                 "price"  => 100,
 *                 "amount" => 50,
 *             ],
 *             "message" => [
 *                 "price"  => "The price must be between 0 and 100",
 *                 "amount" => "The amount must be between 0 and 50",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class Between extends Validator
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
        $minimum = $this->getOption('minimum');
        $maximum = $this->getOption('maximum');
        if (is_array($minimum)) {
            $minimum = $minimum[$field];
        }

        if (is_array($maximum)) {
            $maximum = $maximum[$field];
        }

        if ($value <= $minimum || $value >= $maximum) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Between");
            $code = $this->prepareCode($field);
            $replacePairs[':field'] = $label;
            $replacePairs[":min"] = $minimum;
            $replacePairs[":max"] = $maximum;
            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Between",
                    $code
                )
            );

            return false;
        }

        return true;
    }

}
