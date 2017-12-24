<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;


/**
 * Phalcon\Mvc\Model\Validator\PresenceOf
 *
 * Allows to validate if a filed have a value different of null and empty string ("")
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Model\Validator\PresenceOf;
 *
 * class Subscriptors extends \Phalcon\Mvc\Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new PresenceOf(
 *                 [
 *                     "field"   => "name",
 *                     "message" => "The name is required",
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
 * @see Phalcon\Validation\Validator\PresenceOf
 */
class PresenceOf extends Validator
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

        $fieldName = $this->getOption('field');
        if (is_string($fieldName) === false) {
            throw new Exception('Field name must be a string');
        }

        /**
         * A value is null when it is identical to null or an empty string
         */
        $value = $record->readAttribute($fieldName);

        if (is_null($value) || (is_string($value) && !strlen($value))) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption("message");
            if (empty($message)) {
                $message = "':field' is required";
            }
            $this->appendMessage(strtr($message, ':field', $field), $field, "PresenceOf");
            return false;
        }

        return true;
    }

}
