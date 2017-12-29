<?php

namespace Phalcon\Mvc\Model\Behavior;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\Behavior\Timestampable
 *
 * Allows to automatically update a model’s attribute saving the
 * datetime when a record is created or updated
 */
class Timestampable extends Behavior implements BehaviorInterface
{

    /**
     * Listens for notifications from the models manager
     *
     * @param string $type
     * @param \Phalcon\Mvc\ModelInterface $model
     * @throws Exception
     */
    public function notify($type, ModelInterface $model)
    {
        if (is_string($type) === false ||
            is_object($model) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if the developer decided to take aciton here
        if ($this->mustTakeAction($type) !== true) {
            return;
        }

        $options = $this->getOptions($type);
        if (is_array($options) === true) {
            //The field name is required in this behavior
            if (isset($options['field']) === false) {
                throw new Exception("The option 'field' is required");
            }

            $timestamp = null;
            if (isset($options['format']) === true) {
                //Format is a format for date
                $timestamp = date($options['format']);
            } else {
                if (isset($options['generator']) === true) {
                    //A generator is a closure that produces the current timestamp value
                    if (is_object($options['generator']) === true &&
                        $options['generator'] instanceof Closure === true) {
                        $timestamp = call_user_func($options['generator']);
                    }
                }
            }

            //Last resort call time()
            if (is_null($timestamp) === true) {
                $timestamp = time();
            }

            $field = $options['field'];

            //Assign the value to the field, use writeAttribute if the property is protected
            if (is_array($field) === true) {
                foreach ($field as $singleField) {
                    $model->writeAttribute($singleField, $timestamp);
                }
            } else {
                $model->writeAttribute($field, $timestamp);
            }
        }
    }

}
