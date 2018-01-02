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
class Timestampable extends Behavior
{

    /**
     * Listens for notifications from the models manager
     *
     * @param string $type
     * @param  ModelInterface $model
     * @throws Exception
     * @return null
     */
    public function notify($type, ModelInterface $model)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }
        /**
         * Check if the developer decided to take action here
         */
        if ($this->mustTakeAction($type) !== true) {
            return null;
        }

        $options = $this->getOptions($type);
        if (is_array($options)) {

            /**
             * The field name is required in this behavior
             */
            if (isset($options['field'])) {
                $field = $options['field'];
            } else {
                throw new Exception("The option 'field' is required");
            }

            $timestamp = null;

            if (isset($options['format'])) {
                $format = $options['format'];
                /**
                 * Format is a format for date()
                 */
                $timestamp = date($format);
            } else {
                if (isset($options['generator'])) {
                    $generator = $options['generator'];

                    /**
                     * A generator is a closure that produce the correct timestamp value
                     */
                    if (is_object($generator)) {
                        if ($generator instanceof \Closure) {
                            $timestamp = call_user_func($generator);
                        }
                    }
                }
            }

            /**
             * Last resort call time()
             */
            if ($timestamp === null) {
                $timestamp = time();
            }

            /**
             * Assign the value to the field, use writeattribute if the property is protected
             */
            if (is_array($field)) {
                foreach ($field as $singleField) {
                    $model->writeAttribute($singleField, $timestamp);
                }
            } else {
                $model->writeAttribute($field, $timestamp);
            }
        }
    }
}
