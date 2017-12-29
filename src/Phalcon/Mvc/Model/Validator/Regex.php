<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

/**
 * Phalcon\Mvc\Model\Validator\Regex
 *
 * Allows validate if the value of a field matches a regular expression
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Model\Validator\Regex as RegexValidator;
 *
 * class Subscriptors extends \Phalcon\Mvc\Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new RegexValidator(
 *                 [
 *                     "field"   => "created_at",
 *                     "pattern" => "/^[0-9]{4}[-\/](0[1-9]|1[12])[-\/](0[1-9]|[12][0-9]|3[01])/",
 *                 ]
 *             )
 *         );
 *
 *         if ($this->validationHasFailed() == true) {
 *             return false;
 *         }
 *     }
 * }
 *</code>
 *
 * @deprecated 3.1.0
 * @see Phalcon\Validation\Validator\Regex
 */
class Regex extends Validator
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
        if (is_object($record) === false &&
            $record instanceof EntityInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $fieldName = $this->getOption('field');
        if (is_string($fieldName) === false) {
            throw new Exception('Field name must be a string');
        }

        /**
         * The 'pattern' option must be a valid regular expression
         */
        if ($this->isSetOption('pattern') === false) {
            throw new Exception('Validator requires a perl-compatible regex pattern');
        }
        $pattern = $this->getOption('pattern');

        $value = $this->readAttribute($fieldName);
        if ($this->isSetOption("allowEmpty") && empty($value)) {
            return true;
        }
        $failed  = false;
        $matches = null;

        //Check if the value matches using preg_match
        if (preg_match($pattern, $value, $matches) == true) {
            $failed = ($matches[0] !== $value ? true : false);
        } else {
            $failed = true;
        }

        if ($failed === true) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption('message');
            if (isset($message) === false) {
                $message = "Value of field '" . $fieldName . "' doesn't match regular expression";
            }

            $this->appendMessage(strtr($message, ':field', $field), $fieldName, 'Regex');
            return false;
        }

        return true;
    }

}
