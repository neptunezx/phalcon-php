<?php

namespace Phalcon\Validation;

/**
 * Phalcon\Validation\ValidatorInterface
 *
 * Interface for Phalcon\Validation\Validator
 */

interface ValidatorInterface
{
    /**
     * Checks if an option is defined
     * @param string $key
     * @return boolean
     */
    public function hasOption($key);

	/**
     * Returns an option in the validator's options
     * Returns null if the option hasn't set
     * @param string $key
     * @param mixed|null defaultValue
     * @return mixed
     */
	public function getOption($key, $defaultValue = null);

	/**
     * Executes the validation
     * @param \Phalcon\Validation $validation
     * @param string $attribute
     * @return boolean
     */
	public function validate($validation, $attribute);

}
