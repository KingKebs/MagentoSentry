<?php

namespace Vodacom\MagentoSentry\Plugin;
use Magento\Framework\App\RequestInterface;
use Magento\GraphQl\Controller\GraphQl;
use Sentry\Tracing\SpanStatus;
use Magento\Framework\Serialize\SerializerInterface;
use Vodacom\MagentoSentry\Helper\Data;

class InstrumentGQlControllerAfterDispatch
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SerializerInterface
     */
    protected $jsonSerializer;

    /**
     * @param Data $helper
     * @param SerializerInterface $jsonSerializer
     */
    public function __construct(Data $helper, SerializerInterface $jsonSerializer)
    {
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param GraphQl $subject
     * @param $result
     * @param RequestInterface $request
     * @return mixed
     */
    public function afterDispatch(GraphQL $subject, $result, RequestInterface $request)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }

        $activeTransaction = \Sentry\SentrySdk::getCurrentHub()->getSpan();

        if ($activeTransaction !== null) {
            //A GraphQL API will return a 200 OK Status Code even in case of error.
            $activeTransaction->setStatus(SpanStatus::createFromHttpStatusCode($result->getHttpResponseCode()));
            $body = $this->jsonSerializer->unserialize($result->getBody());

            //https://spec.graphql.org/October2021/#sec-Errors.Error-result-format
            if (!isset($body->errors) && isset($body['errors'])) {
                foreach ($body['errors'] as $e) {
                    $message = $e['message'] ?? '[]';
                    $locations = $e['locations'] ?? '[]';
                    $ext = $e['extensions'] ?? '[]';
                    $data = $e['data'] ?? '[]';
                    $path = $e['path'] ?? '[]';

                    \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($message,$locations,$ext,$data,$path): void {
                        $scope->setContext('GQL Error Meta',[
                            'locations' => $locations,
                            'extensions' => $ext,
                            'data' => $data,
                            'path' => $path
                            ]);
                        $scope->setFingerprint([$message,'{{default}}']);
                        \Sentry\captureException(new \Exception("$message"));
                    });
                }
            }

            $activeTransaction->finish();
        }

        return $result;
    }
}
