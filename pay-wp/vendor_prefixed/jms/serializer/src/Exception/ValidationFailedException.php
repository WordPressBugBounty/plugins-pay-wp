<?php

declare (strict_types=1);
namespace WPPayVendor\JMS\Serializer\Exception;

use WPPayVendor\Symfony\Component\Validator\ConstraintViolationListInterface;
class ValidationFailedException extends RuntimeException
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $list;
    public function __construct(ConstraintViolationListInterface $list)
    {
        parent::__construct(sprintf('Validation failed with %d error(s).', \count($list)));
        $this->list = $list;
    }
    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->list;
    }
}
