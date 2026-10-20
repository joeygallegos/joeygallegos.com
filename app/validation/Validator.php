<?php

namespace App\Validation;

use Slim\Psr7\Request;
use Delight\Cookie\Session;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator
{

	/**
	 * Array of errors from validation
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Validate request parameters against rule array
	 * @return Validator instance
	 */
	public function validate(Request $request, array $rules)
	{
		// The validator is shared by the container, so every invocation must start with an independent result.
		$this->errors = [];
		$parsedBody = $request->getParsedBody();
		$parsedBody = is_array($parsedBody) ? $parsedBody : [];

		foreach ($rules as $field => $config) {
			// check request param against rule for matching param name
			try {
				$config['validator']->setName($config['clean_name'])->assert($parsedBody[$field] ?? null);
			} catch (NestedValidationException $e) {
				$this->errors[$field] = $e->getMessages();
			}
		}

		Session::set('form_errors', $this->errors);
		return $this;
	}

	/**
	 * Check if the validation has errors
	 * @return boolean
	 */
	public function failed()
	{
		return !empty($this->errors);
	}

	/**
	 * Grab the array of errors
	 * @return array
	 */
	public function errors()
	{
		return $this->errors;
	}
}
