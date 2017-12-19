<?php

namespace Phalcon\Mvc\Model\Validator;

use \Phalcon\Mvc\Model\Validator;
use \Phalcon\Mvc\Model\ValidatorInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Validator\Regex
 *
 * Allows validate if the value of a field matches a regular expression
 *
 * <code>
 * use Phalcon\Mvc\Model\Validator\Regex as RegexValidator;
 *
 * class Subscriptors extends Phalcon\Mvc\Model
 * {
 *
 *  public function validation()
 *  {
 *      $this->validate(new RegexValidator(array(
 *          'field' => 'created_at',
 *          'pattern' => '/^[0-9]{4}[-\/](0[1-9]|1[12])[-\/](0[1-9]|[12][0-9]|3[01])$/'
 *      )));
 *      if ($this->validationHasFailed() == true) {
 *          return false;
 *      }
 *  }
 *
 * }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/validator/regex.c
 */
class Regex extends Validator implements ValidatorInterface
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
        if (is_object($record) === false &&
            $record instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $fieldName = $this->getOption('field');
        if (is_string($fieldName) === false) {
            throw new Exception('Field name must be a string');
        }

        //The 'pattern' option must be a valid regular expression
        if ($this->isSetOption('pattern') === false) {
            throw new Exception('Validator requires a perl-compatible regex pattern');
        }
        $pattern = $this->getOption('pattern');

        $value = $this->readAttribute($fieldName);

        $failed  = false;
        $matches = null;

        //Check if the value matches using preg_match
        if (preg_match($pattern, $value, $matches) == true) {
            $failed = ($matches[0] !== $value ? true : false);
        } else {
            $failed = true;
        }

        if ($failed === true) {
            //Check if the develop has defined a custom message
            $message = $this->getOption('message');
            if (isset($message) === false) {
                $message = "Value of field '" . $fieldName . "' doesn't match regular expression";
            }

            $this->appendMessage($message, $fieldName, 'Regex');
            return false;
        }

        return true;
    }

}
