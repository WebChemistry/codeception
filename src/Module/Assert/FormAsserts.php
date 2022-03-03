<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module\Assert;

use a;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use PHPUnit\Framework\Assert;
use WebChemistry\Codeception\Client\Internals;

final class FormAsserts
{

	public function __construct(
		private Form $form,
		private Internals $internals,
	)
	{
	}

	public function getInternals(): Internals
	{
		return $this->internals;
	}

	public function wasSuccess(): self
	{
		if ($this->form->isSuccess()) {
			return $this;
		}

		if ($this->internals->error) {
			Assert::fail(sprintf('Form was not submitted, because of %s.', lcfirst($this->internals->error)));

			return $this;
		}

		if (!$this->form->isSubmitted()) {
			Assert::fail('Form was not submitted.');

			return $this;
		}

		if ($this->form->hasErrors()) {
			Assert::fail("Form has these errors: \n" . $this->printFormErrors($this->form));

			return $this;
		}

		Assert::fail('Form was not success.');

		return $this;
	}

	public function wasFail(): self
	{
		if ($this->internals->error) {
			Assert::fail(sprintf('Form was not submitted, because of %s.', lcfirst($this->internals->error)));

			return $this;
		}

		if (!$this->form->isSubmitted()) {
			Assert::fail('Form was not submitted.');

			return $this;
		}

		if (!$this->form->isSuccess()) {
			return $this;
		}

		Assert::fail('Form was success.');

		return $this;
	}

	public function hadValue(string $key, mixed $value): self
	{
		$values = $this->form->getValues('array');
		if (!array_key_exists($key, $values)) {
			Assert::fail(sprintf('Form does not have value %s.', $key));
		}

		Assert::assertSame($value, $values[$key]);

		return $this;
	}

	/**
	 * @param mixed[] $values
	 */
	public function hadValues(array $values): self
	{
		Assert::assertSame($values, $this->form->getValues('array'));

		return $this;
	}

	private function printFormErrors(Form $form): string
	{
		$errors = [];

		foreach ($form->getControls() as $control) {
			if (!$control instanceof BaseControl) {
				continue;
			}

			if (!$control->hasErrors()) {
				continue;
			}

			foreach ($control->getErrors() as $error) {
				$errors[] = $control->getName()  . ': ' . $error;
			}
		}

		foreach ($form->getOwnErrors() as $error) {
			$errors[] = 'Form error: ' . $error;
		}

		return implode("\n", $errors);
	}

}
