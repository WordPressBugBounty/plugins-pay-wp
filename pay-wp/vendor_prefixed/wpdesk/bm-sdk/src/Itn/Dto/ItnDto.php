<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Itn\Dto;

use WPPayVendor\BlueMedia\Common\Dto\AbstractDto;
use WPPayVendor\BlueMedia\Itn\ValueObject\Itn;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\JMS\Serializer\Annotation\XmlList;
use WPPayVendor\JMS\Serializer\Annotation\Type;
final class ItnDto extends AbstractDto implements SerializableInterface
{
    /**
     * @var Itn
     * @Type("WPPayVendor\BlueMedia\Itn\ValueObject\Itn")
     * @XmlList(inline = true, entry = "transaction")
     */
    private $itn;
    /**
     * @return Itn
     */
    public function getItn(): Itn
    {
        return $this->itn;
    }
    public function setItn(Itn $itn): void
    {
        $this->itn = $itn;
    }
    public function getRequestData(): SerializableInterface
    {
        return $this->getItn();
    }
}
