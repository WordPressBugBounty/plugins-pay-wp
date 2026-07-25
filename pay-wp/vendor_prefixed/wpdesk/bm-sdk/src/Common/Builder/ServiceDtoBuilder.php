<?php

declare (strict_types=1);
namespace WPPayVendor\BlueMedia\Common\Builder;

use WPPayVendor\BlueMedia\Configuration;
use WPPayVendor\BlueMedia\Hash\HashGenerator;
use WPPayVendor\BlueMedia\Serializer\Serializer;
use WPPayVendor\BlueMedia\Common\Dto\AbstractDto;
final class ServiceDtoBuilder
{
    public static function build(array $data, string $type, Configuration $configuration): AbstractDto
    {
        $serializer = new Serializer();
        $dto = $serializer->serializeDataToDto($data, $type);
        $dto->getRequestData()->setServiceId($configuration->getServiceId());
        $hash = HashGenerator::generateHash($dto->getRequestData()->toArray(), $configuration);
        $dto->getRequestData()->setHash($hash);
        return $dto;
    }
}
