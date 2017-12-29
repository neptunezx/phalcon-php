<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;

/**
 * Phalcon\Validation\Validator\CreditCard
 *
 * Checks if a value has a valid credit card number
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\CreditCard as CreditCardValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "creditCard",
 *     new CreditCardValidator(
 *         [
 *             "message" => "The credit card number is not valid",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "creditCard",
 *         "secondCreditCard",
 *     ],
 *     new CreditCardValidator(
 *         [
 *             "message" => [
 *                 "creditCard"       => "The credit card number is not valid",
 *                 "secondCreditCard" => "The second credit card number is not valid",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 */
class CreditCard extends Validator
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

        $valid = $this->verifyByLuhnAlgorithm($value);

        if (!$valid) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "CreditCard");
            $code = $this->prepareCode($field);

            $replacePairs[':field'] = $label;

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "CreditCard",
                    $code
                )
            );

            return false;
        }

        return true;
    }

    /**
     * is a simple checksum formula used to validate a variety of identification numbers
     * @param  string number
     * @return boolean
     * @throws Exception
     */
    private function verifyByLuhnAlgorithm($number)
    {
        if (!is_string($number)) {
            throw new Exception('Invalid parameter type.');
        }
        $digits = (array)str_split($number);

        $hash = "";

        foreach (array_reverse($digits) as $position => $digit) {
            $hash .= ($position % 2 ? $digit * 2 : $digit);
        }

        $result = array_sum(str_split($hash));

        return ($result % 10 == 0);
    }


}
