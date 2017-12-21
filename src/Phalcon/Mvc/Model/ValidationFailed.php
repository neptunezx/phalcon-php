<?php

namespace Phalcon\Mvc\Model;

use \Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\ValidationFailed
 *
 * This exception is generated when a model fails to save a record
 * Phalcon\Mvc\Model must be set up to have this behavior
 */
class ValidationFailed extends \Phalcon\Mvc\Model\Exception
{

    /**
     * Model
     *
     * @var null|\Phalcon\Mvc\Model
     * @access protected
     */
    protected $_model;

    /**
     * Messages
     *
     * @var null|array
     * @access protected
     */
    protected $_messages;

    /**
     * \Phalcon\Mvc\Model\ValidationFailed constructor
     *
     * @param \Phalcon\Mvc\Model $model
     * @param \Phalcon\Mvc\Model\Message[] $validationMessages
     * @throws Exception
     */
    public function __construct(Model $model, array $validationMessages)
    {
        if (count($validationMessages) > 0) {
            /**
             * Get the first message in the array
             */
            $message = $validationMessages[0];

            /**
             * Get the message to use it in the exception
             */
            $messageStr = $message->getMessage();
        } else {
            $messageStr = "Validation failed";
        }

        $this->_model    = $model;
        $this->_messages = $validationMessages;

        parent::__construct($messageStr);
    }

    /**
     * Returns the model that generated the messages
     *
     * @return \Phalcon\Mvc\Model|null
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Returns the complete group of messages produced in the validation
     *
     * @return \Phalcon\Mvc\Model\Message[]|null
     */
    public function getMessages()
    {
        return $this->_messages;
    }

}
