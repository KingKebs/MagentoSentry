<?php

namespace Vodacom\MagentoSentry\Plugin;
use Magento\Framework\AppInterface;
use Vodacom\MagentoSentry\Helper\Data;

class SentryOnAppStart
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param AppInterface $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundLaunch(AppInterface $subject, callable $proceed)
    {
        if (!$this->helper->isEnabled()) {
            return $proceed();
        }

        //https://github.com/magento/magento2/blob/2.3/lib/internal/Magento/Framework/App/DeploymentConfig.php
        \Sentry\init([
            'dsn' => $this->helper->getDsn(),
            'traces_sample_rate' => $this->helper->getTraceRate(),
            'environment' => $this->helper->getEnviroment(),
            'max_breadcrumbs' => 50
        ]);

        return $proceed();
    }
}
