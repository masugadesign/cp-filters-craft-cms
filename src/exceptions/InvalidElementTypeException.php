<?php

namespace Masuga\CpFilters\exceptions;

use yii\base\Exception;

class InvalidElementTypeException extends Exception
{

	/**
	 * The invalid element class name.
	 * @var string
	 */
	protected $elementType = null;

	public function __construct($elementType, $message=null, $code=0)
	{
		$this->elementType = $elementType;
		if ( ! $message ) {
			$message = "Invalid element type `$elementType`";
		}
		parent::__construct($message, $code);
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return "Invalid element type `{$this->elementType}`";
	}
}
