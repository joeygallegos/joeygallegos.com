<?php
namespace App\Validation;

use Slim\Container;
use Slim\Http\Request;
use Delight\Cookie\Session;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator {

	/**
	 * Array of errors from validation
	 * @var array
	 */
	protected $errors = [];

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Validate request parameters against rule array
	 * @return Validator instance
	 */
	public function validate(Request $request, array $rules)
	{
		foreach ($rules as $field => $config)
		{
			// check request param against rule for matching param name
			try {
				$config['validator']->setName($config['clean_name'])->assert($request->getParam($field));
			} catch (NestedValidationException $e) {
				$this->errors[$field] = $e->getMessages();
			}
		}

		Session::set('errors', $this->errors);
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