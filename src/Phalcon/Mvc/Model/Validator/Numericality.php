<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

/**
 * Phalcon\Mvc\Model\Validator\Numericality
 *
 * Allows to validate if a field has a valid numeric format
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Model\Validator\Numericality as NumericalityValidator;
 *
 * class Products extends \Phalcon\Mvc\Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new NumericalityValidator(
 *                 [
 *                     "field" => "price",
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
 * @see Phalcon\Validation\Validator\Numericality
 */
class Numericality extends Validator
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

        if (($this->isSetOption("allowEmpty")) && empty($value)) {
            return true;
        }

        /**
         * Check if the value is numeric using is_numeric in the PHP userland
         */

        if (is_null($value) === false) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption('message');
            if (isset($message) === false) {
                $message = "Value of field '" . $field . "' must be numeric";
            }

            $this->appendMessage(strtr($message, ":field", $field), $field, 'Numericality');

            return false;
        }

        return true;
    }

}
