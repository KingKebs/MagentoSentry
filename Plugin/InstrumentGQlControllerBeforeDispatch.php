<?php

namespace Vodacom\MagentoSentry\Plugin;
use Magento\Framework\App\RequestInterface as AppRequestInterface;
use Magento\GraphQl\Controller\GraphQl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Sentry\Tracing\TransactionContext;
use Vodacom\MagentoSentry\Helper\Data;

class InstrumentGQlControllerBeforeDispatch
{
    public const TXN_OPNAME = 'gql';

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SerializerInterface
     */
    protected $jsonSerializer;

    /**
     * @var QueryFields
     */
    protected $queryFields;

    /**
     * @var null
     */
    protected $data;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var array
     */
    protected $query;

    /**
     * @var string
     */
    protected $txnName;

    /**
     * @var string
     */
    protected $method;

    /**
     * @param SerializerInterface $jsonSerializer
     * @param QueryFields $queryFields
     * @param Data $helper
     */
    public function __construct(
        SerializerInterface $jsonSerializer,
        QueryFields $queryFields,
        Data $helper
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->queryFields = $queryFields;
        $this->helper = $helper;

        $this->data = null;
        $this->variables = [];
        $this->query = [];
        $this->txnName = "GQL<No Op Name>";
        $this->method = "<unknown>";
    }

    /**
     * @param GraphQl $subject
     * @param AppRequestInterface $request
     * @return AppRequestInterface[]
     */
    public function beforeDispatch(GraphQl $subject, AppRequestInterface $request)
    {
        if (!$this->helper->isEnabled()) {
            return [$request];
        }

        //https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/GraphQl/Controller/GraphQl.php#L188
        $sentryTraceHeader = $request->getHeader('sentry_trace');
        $baggageHeader = $request->getHeader('baggage');

        //checks for sampling decision. Headers optional.
        $transactionContext = TransactionContext::fromHeaders($sentryTraceHeader, $baggageHeader);

        if ($request->isPost()) {
            $this->method = "POST";
            $this->data = $this->jsonSerializer->unserialize($request->getContent());
            $this->variables = isset($this->data['variables']) ? $this->data['variables'] : $this->variables;
        } elseif ($request->isGet()) {
            //not tested yet
            $this->method = "GET";
            $this->data = $request->getParams();
            $this->variables = isset($this->data['variables']) ? $this->jsonSerializer->unserialize($this->data['variables']) : '[]';
        }

        //There is no guarantee that queries will arrive w/ operation names
        $this->queryFields->setQuery($this->data['query'] ?? $this->query, $this->variables);

        if (isset($this->data['operationName'])) {
            $this->txnName = 'GQL<' . $this->data['operationName'] . '>';
        } elseif (isset($this->data['query'])) {
            $this->txnName = 'GQL<' . \trim((string)\strtok($this->data['query'], '{(')) . '>';
        }

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) : void {
            $this->query = $this->data['query'] ?? $this->query;
            $scope->setContext('Query',['query' => $this->query]);
            $scope->setContext('Variables',['vars' => $this->variables]);
//            \Sentry\captureMessage('Something went wrong');
        });

        $transactionContext->setOp(self::TXN_OPNAME);
        $transactionContext->setName("$this->txnName");
        $transaction = \Sentry\startTransaction($transactionContext);
        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

        return [$request];
    }
}
