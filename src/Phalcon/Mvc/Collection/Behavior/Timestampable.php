<?php

namespace Phalcon\Mvc\Collection\Behavior;

use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Collection\Behavior;
use Phalcon\Mvc\Collection\Exception;

/**
 * Phalcon\Mvc\Collection\Behavior\Timestampable
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
     * @param CollectionInterface $model
     * @throws Exception
	 */
	public function notify($type, CollectionInterface $model)
	{
	    if(!is_string($type)) {
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
			if(isset($options["field"])) {
                throw new Exception("The option 'field' is required");
            }
            $field = $options["field"];

			$timestamp = null;

			if(isset($options["format"])) {
			    $format = $options["format"];
                $timestamp = date($format);
            }
            else {
			    if(isset($options["generator"])) {
			        $generator = $options["generator"];
			        if(is_object($generator)) {
			            if($generator instanceof \Closure) {
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
