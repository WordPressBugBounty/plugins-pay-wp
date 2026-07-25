<?php

declare (strict_types=1);
namespace WPPayVendor;

use WPPayVendor\Rector\Config\RectorConfig;
return RectorConfig::configure()->withPaths([__DIR__ . '/src'])->withPhp74Sets();
