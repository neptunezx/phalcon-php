<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

/**
 * Phalcon\Mvc\Model\Validator\InclusionIn
 *
 * Check if a value is included into a list of values
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Model\Validator\InclusionIn as InclusionInValidator;
 *
 * class Subscriptors extends \Phalcon\Mvc\Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new InclusionInValidator(
 *                 [
 *                     "field"  => "status",
 *                     "domain" => ["A", "I"],
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
 * @see Phalcon\Validation\Validator\InclusionIn
 */


class InclusionIn extends Validator
{

    /**
     * Executes validator
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

        //The 'domain' option must be a valid array of not allowed values
        if ($this->isSetOption('domain') === false) {
            throw new Exception("The option 'domain' is required for this validator");
        }

        $domain = $this->getOption('domain');
        if (is_array($domain) === false) {
            throw new Exception("Option 'domain' must be an array");
        }

        $value = $record->readAttribute($fieldName);

        if ($this->isSetOption("allowEmpty") && empty($value)) {
            return true;
        }
        $strict = false;
        if ($this->isSetOption("strict")) {
            if (is_bool($strict)) {
                throw new Exception("Option 'strict' must be a boolean");
            }
			$strict = $this->getOption("strict");
		}


        /**
         * Check if the value is contained in the array
         */
        if (in_array($value, $domain) === false) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption('message');
            if (isset($message) === false) {
                $message = "Value of field '" . $fieldName . "' must not be part of list: " . implode(', ', $domain);
            }

            $this->appendMessage(strtr($message, array(':field'=> $field, ":domain"=> join(", ", $domain))), $fieldName, 'Inclusion');
            return false;
        }

        return true;
    }

}
