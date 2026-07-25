<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\RegulationList\Dto;

use WPPayVendor\JMS\Serializer\Annotation\Type;
use WPPayVendor\BlueMedia\Common\Dto\AbstractDto;
use WPPayVendor\BlueMedia\Serializer\SerializableInterface;
use WPPayVendor\BlueMedia\RegulationList\ValueObject\RegulationList;
final class RegulationListDto extends AbstractDto
{
    /**
     * @var RegulationList
     * @Type("WPPayVendor\BlueMedia\RegulationList\ValueObject\RegulationList")
     */
    private $regulationList;
    /**
     * @return RegulationList
     */
    public function getRegulationList(): RegulationList
    {
        return $this->regulationList;
    }
    public function getRequestData(): SerializableInterface
    {
        return $this->getRegulationList();
    }
}
