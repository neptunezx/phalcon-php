<?php

namespace Phalcon\Mvc\Model\Behavior;

use \Phalcon\Mvc\Model\Behavior;
use \Phalcon\Mvc\Model\BehaviorInterface;
use \Phalcon\Mvc\Model\Exception;
use \Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\Behavior\SoftDelete
 *
 * Instead of permanently delete a record it marks the record as
 * deleted changing the value of a flag column
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/behavior/softdelete.c
 */
class SoftDelete extends Behavior implements BehaviorInterface
{

    /**
     * Listens for notifications from the models manager
     *
     * @param string $type
     * @param \Phalcon\Mvc\ModelInterface $model
     * @throws Exception
     */
    public function notify($type, $model)
    {
        if (is_string($type) === false ||
            is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($type === 'beforeDelete') {
            $options = $this->getOptions();
            if (isset($options['value']) === false) {
                throw new Exception("The option 'value' is required");
            }

            if (isset($options['field']) === false) {
                throw new Exception("The options 'field' is required");
            }

            //Skip the current operation
            $model->skipOperation(true);

            //'value' is the value to be updated instead of delete the record
            $value = $options['field'];

            //'field' is the attribute to be updated instead of delete the record
            $field       = $options['field'];
            $actualValue = $model->readAttribute($field);

            //If the record is already flagged as 'deleted' we don't delete it again
            if ($actualValue !== $value) {
                //Clone the current model to make a clean new operation
                $updateModel = clone $model;

                //Update the cloned model
                $updateModel->writeAttribute($field, $value);
                if ($updateModel->save() !== true) {
                    //Transfer the message from the cloned model to the original model
                    $messages = $updateModel->getMessages();
                    foreach ($messages as $message) {
                        $model->appendMessage($message);
                    }

                    return false;
                }

                //Update the original model too
                $model->writeAttribute($field, $value);
            }
        }
    }

}
