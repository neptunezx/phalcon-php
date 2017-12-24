<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

/**
 * Phalcon\Mvc\Model\Validator\Url
 *
 * Allows to validate if a field has a url format
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Model\Validator\Url as UrlValidator;
 *
 * class Posts extends \Phalcon\Mvc\Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new UrlValidator(
 *                 [
 *                     "field" => "source_url",
 *                 ]
 *             )
 *         );
 *
 *         if ($this->validationHasFailed() === true) {
 *             return false;
 *         }
 *     }
 * }
 *</code>
 *
 * @deprecated 3.1.0
 * @see Phalcon\Validation\Validator\Url
 */
class Url extends Validator
{

    /**
     * Executes the validator
     *
     * @param \Phalcon\Mvc\EntityInterface $record
     * @return boolean
     * @throws Exception
     */
    public function validate($record)
    {
        if (is_object($record) === false ||
            $record instanceof EntityInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $field = $this->getOption('field');
        if (is_string($field) === false) {
            throw new Exception('Field name must be a string');
        }

        $value = $record->readAttribute($field);

        /**
         * Filters the format using FILTER_VALIDATE_URL
         */
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption('message');

            if (isset($message) === false) {
                $message = ":field does not have a valid url format";
            }

            $this->appendMessage(strtr($message, ':field', $field), $field, 'Url');
            return false;
        }

        return true;
    }

}
