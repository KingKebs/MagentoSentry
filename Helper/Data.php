<?php

namespace Vodacom\MagentoSentry\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_EXTENSION_ENABLED = "Vodacom_Sentry/general/enable_sentry";
    const DSN_XML_PATH  = "Vodacom_Sentry/general/dsn_url";
    const TRACE_RATE_XML_PATH  = "Vodacom_Sentry/general/traces_sample_rate";
    const ENVIRONMENT_XML_PATH  = "Vodacom_Sentry/general/environment";

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EXTENSION_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return (string)$this->scopeConfig->getValue(self::DSN_XML_PATH, ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return string
     */
    public function getEnviroment()
    {
        return (string)$this->scopeConfig->getValue(self::ENVIRONMENT_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getTraceRate()
    {
        return (float)$this->scopeConfig->getValue(self::TRACE_RATE_XML_PATH, ScopeInterface::SCOPE_STORE);
    }
}
