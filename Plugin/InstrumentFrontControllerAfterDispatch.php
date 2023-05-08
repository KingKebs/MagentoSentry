<?php

namespace Vodacom\MagentoSentry\Plugin;
use Magento\Framework\App\RequestInterface;
use Magento\Webapi\Controller\Rest;
use Sentry\Tracing\SpanStatus;
use Vodacom\MagentoSentry\Helper\Data;

class InstrumentFrontControllerAfterDispatch
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
     * @param Rest $subject
     * @param $result
     * @param RequestInterface $request
     * @return mixed
     */
    public function afterDispatch(Rest $subject, $result, RequestInterface $request)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        $activeTransaction = \Sentry\SentrySdk::getCurrentHub()->getSpan();

        if ($activeTransaction !== null) {
            $exceptions = $result->getException();

            if (!empty($exceptions)) {
                //need to check for exceptions and set status here. Otherwise only using $result->getStatusCode() returns incorrect status code
                foreach ($exceptions as $exception) {
                    //what is the root of multiple exceptions here?
                    $activeTransaction->setStatus(SpanStatus::createFromHttpStatusCode($exception->getHttpCode()));
                }
            } else {
                $activeTransaction->setStatus(SpanStatus::createFromHttpStatusCode($result->getStatusCode()));
            }

            $activeTransaction->finish();
        }

        return $result;
    }
}
