<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

/**
 * Phalcon\Validation\Validator\Url
 *
 * Checks if a value has a url format
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Url as UrlValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "url",
 *     new UrlValidator(
 *         [
 *             "message" => ":field must be a url",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "url",
 *         "homepage",
 *     ],
 *     new UrlValidator(
 *         [
 *             "message" => [
 *                 "url"      => "url must be a url",
 *                 "homepage" => "homepage must be a url",
 *             ]
 *         ]
 *     )
 * );
 * </code>
 */
class Url extends Validator
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

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $label = $this->prepareLabel($validation, $field);
            $message = $this->prepareMessage($validation, $field, "Url");
            $code = $this->prepareCode($field);

            $replacePairs = array(":field" => $label);

            $validation->appendMessage(
                new Message(
                    strtr($message, $replacePairs),
                    $field,
                    "Url",
                    $code
                )
            );
            return false;
        }
        return true;
    }
}
