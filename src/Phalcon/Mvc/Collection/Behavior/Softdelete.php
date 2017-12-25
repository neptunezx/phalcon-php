<?php
/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2017 Phalcon Team (https://phalconphp.com)          |
 +------------------------------------------------------------------------+
 | This source file is subject to the New BSD License that is bundled     |
 | with this package in the file LICENSE.txt.                             |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
 | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
 |          Eduar Carvajal <eduar@phalconphp.com>                         |
 +------------------------------------------------------------------------+
 */

namespace Phalcon\Mvc\Collection\Behavior;

use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Collection\Behavior;
use Phalcon\Mvc\Collection\Exception;

/**
 * Phalcon\Mvc\Collection\Behavior\SoftDelete
 *
 * Instead of permanently delete a record it marks the record as
 * deleted changing the value of a flag column
 */
class SoftDelete extends Behavior
{

	/**
	 * Listens for notifications from the models manager
     *
     * @param string $type
     * @param CollectionInterface $model
     * @throws Exception
	 */
	public function notify($type, CollectionInterface $model)
	{
        if(!is_string($type)) {
            throw new Exception('Invalid parameter type.');
        }
		if ($type == "beforeDelete") {

			$options = $this->getOptions();

			/**
			 * 'value' is the value to be updated instead of delete the record
			 */
			if(!isset($options["value"])) {
                throw new Exception("The option 'value' is required");
            }
            $value = $options["value"];

			/**
			 * 'field' is the attribute to be updated instead of delete the record
			 */
			if(isset($options["field"])) {
                throw new Exception("The option 'field' is required");
            }
            $field = $options["field"];

			/**
			 * Skip the current operation
			 */
			$model->skipOperation(true);

			/**
			 * If the record is already flagged as 'deleted' we don't delete it again
			 */
			if ($model->readAttribute($field) != $value) {

				/**
				 * Clone the current model to make a clean new operation
				 */
				$updateModel = clone $model;

                $updateModel->writeAttribute($field, $value);

				/**
				 * Update the cloned model
				 */
				if (!$updateModel->save()) {

					/**
					 * Transfer the messages from the cloned model to the original model
					 */
					foreach ($updateModel->getMessages() as $message) {
                        $model->appendMessage($message);
                    }
//					for message in $updateModel->getMessages() {
//						model->appendMessage(message);
//					}
					return false;
				}

				/**
				 * Update the original model too
				 */
				$model->writeAttribute($field, $value);
			}
		}
	}
}
