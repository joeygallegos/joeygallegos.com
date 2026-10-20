<?php

declare(strict_types=1);

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validator as RespectValidator;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ValidatorTest extends TestCase
{
	protected function setUp(): void
	{
		$_SESSION = [];
	}

	public function testPersistsValidationErrorsToSessionWithExpectedKey(): void
	{
		$request = (new ServerRequestFactory())
			->createServerRequest('POST', '/contact/abc')
			->withParsedBody([
				'name' => '',
			]);

		$rules = [
			'name' => [
				'clean_name' => 'Name',
				'validator' => RespectValidator::notEmpty(),
			],
		];

		$validator = new Validator();
		$validator->validate($request, $rules);

		self::assertTrue($validator->failed());
		self::assertArrayHasKey('form_errors', $_SESSION);
		self::assertArrayHasKey('name', $_SESSION['form_errors']);
	}

	public function testMissingOrNonArrayInputIsValidatedAsNull(): void
	{
		$rules = [
			'name' => [
				'clean_name' => 'Name',
				'validator' => RespectValidator::notEmpty(),
			],
		];
		$validator = new Validator();

		$validator->validate(
			(new ServerRequestFactory())->createServerRequest('POST', '/contact/abc')->withParsedBody(null),
			$rules
		);

		self::assertTrue($validator->failed());
		self::assertArrayHasKey('name', $validator->errors());

		$validator->validate(
			(new ServerRequestFactory())->createServerRequest('POST', '/contact/abc')->withParsedBody(new stdClass()),
			$rules
		);

		self::assertTrue($validator->failed());
		self::assertArrayHasKey('name', $validator->errors());
	}

	public function testEachValidationCallStartsWithFreshErrors(): void
	{
		$rules = [
			'name' => [
				'clean_name' => 'Name',
				'validator' => RespectValidator::notEmpty(),
			],
		];
		$validator = new Validator();
		$requestFactory = new ServerRequestFactory();

		$validator->validate(
			$requestFactory->createServerRequest('POST', '/contact/abc')->withParsedBody([]),
			$rules
		);
		self::assertTrue($validator->failed());

		$validator->validate(
			$requestFactory->createServerRequest('POST', '/contact/abc')->withParsedBody(['name' => 'Taylor']),
			$rules
		);

		self::assertFalse($validator->failed());
		self::assertSame([], $validator->errors());
		self::assertSame([], $_SESSION['form_errors']);
	}
}
