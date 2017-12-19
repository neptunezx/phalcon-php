<?php

namespace Phalcon\Mvc\Model\Validator;

use \Phalcon\Mvc\Model\Validator;
use \Phalcon\Mvc\Model\ValidatorInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Validator\ExclusionIn
 *
 * Check if a value is not included into a list of values
 *
 * <code>
 *  use Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionInValidator;
 *
 *  class Subscriptors extends Phalcon\Mvc\Model
 *  {
 *
 *      public function validation()
 *      {
 *          $this->validate(new ExclusionInValidator(array(
 *              'field' => 'status',
 *              'domain' => array('A', 'I')
 *          )));
 *          if ($this->validationHasFailed() == true) {
 *              return false;
 *          }
 *      }
 *
 *  }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/exclusionin.c
 */
class ExclusionIn extends Validator implements ValidatorInterface
{

    /**
     * Executes the validator
     *
     * @param \Phalcon\Mvc\ModelInterface $record
     * @return boolean
     * @throws Exception
     */
    public function validate($record)
    {
        if (is_object($record) === false ||
            $record instanceof ModelInterface === false) {
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

        //We check if the value is contained in the array using "in_array"
        if (in_array($value, $domain) === true) {
            //Check if the developer has defined a custom message
            $message = $this->getOption('message');
            if (isset($message) === false) {
                $message = "Value of field '" . $fieldName . "' must not be part of list: " . implode(', ', $domain);
            }

            $this->appendMessage($message, $fieldName, 'Exclusion');
            return false;
        }

        return true;
    }

}
