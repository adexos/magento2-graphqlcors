<?php
namespace Adexos\GraphqlCors\Controller;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\GraphQl\Exception\ExceptionFormatter;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\SchemaGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Response;
use Magento\GraphQl\Controller\HttpRequestProcessor;

/**
 *  Front controller for web API GraphQL area.
 *
 * @package Adexos\GraphqlCors\Controller
 */
class GraphQl implements FrontControllerInterface
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var SchemaGeneratorInterface
     */
    private $schemaGenerator;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var QueryProcessor
     */
    private $queryProcessor;

    /**
     * @var \Magento\Framework\GraphQl\Exception\ExceptionFormatter
     */
    private $graphQlError;

    /**
     * @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface
     */
    private $resolverContext;

    /**
     * @var HttpRequestProcessor
     */
    private $requestProcessor;

    /**
     * @var QueryFields
     */
    private $queryFields;

    /**
     * @param Response $response
     * @param SchemaGeneratorInterface $schemaGenerator
     * @param SerializerInterface $jsonSerializer
     * @param QueryProcessor $queryProcessor
     * @param \Magento\Framework\GraphQl\Exception\ExceptionFormatter $graphQlError
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $resolverContext
     * @param HttpRequestProcessor $requestProcessor
     * @param QueryFields $queryFields
     */
    public function __construct(
        Response $response,
        SchemaGeneratorInterface $schemaGenerator,
        SerializerInterface $jsonSerializer,
        QueryProcessor $queryProcessor,
        ExceptionFormatter $graphQlError,
        ContextInterface $resolverContext,
        HttpRequestProcessor $requestProcessor,
        QueryFields $queryFields
    ) {
        $this->response = $response;
        $this->schemaGenerator = $schemaGenerator;
        $this->jsonSerializer = $jsonSerializer;
        $this->queryProcessor = $queryProcessor;
        $this->graphQlError = $graphQlError;
        $this->resolverContext = $resolverContext;
        $this->requestProcessor = $requestProcessor;
        $this->queryFields = $queryFields;
    }

    /**
     * Handle GraphQL request
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @since 100.3.0
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $statusCode = 200;
        try {
            /** @var Http $request */
            $this->requestProcessor->processHeaders($request);
            $content = ($request->getContent() === '') ? '{}' : $request->getContent();
            $data = $this->jsonSerializer->unserialize($content);

            if ($request->isOptions()) {
                $this->response->setBody('')->setHeader(
                    'Content-Type',
                    'application/json'
                )
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setHeader('Access-Control-Allow-Headers', 'content-type')
                    ->setHeader('Access-Control-Allow-Methods', 'GET, HEAD, POST, OPTIONS')
                    ->setHttpResponseCode($statusCode);
                return $this->response;
            }

            $query = isset($data['query']) ? $data['query'] : '';
            $variables = isset($data['variables']) ? $data['variables'] : null;
            // We have to extract queried field names to avoid instantiation of non necessary fields in webonyx schema
            // Temporal coupling is required for performance optimization
            $this->queryFields->setQuery($query, $variables);
            $schema = $this->schemaGenerator->generate();

            $result = $this->queryProcessor->process(
                $schema,
                $query,
                $this->resolverContext,
                isset($data['variables']) ? $data['variables'] : []
            );
        } catch (\Throwable $error) {
            $result['errors'] = isset($result) && isset($result['errors']) ? $result['errors'] : [];
            $result['errors'][] = $this->graphQlError->create($error);
            $statusCode = ExceptionFormatter::HTTP_GRAPH_QL_SCHEMA_ERROR_STATUS;
        }
        $this->response->setBody($this->jsonSerializer->serialize($result))->setHeader(
            'Content-Type',
            'application/json'
        )
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'content-type')
            ->setHeader('Access-Control-Allow-Methods', 'GET, HEAD, éPOST, OPTIONS')
            ->setHttpResponseCode($statusCode);
        return $this->response;
    }
}
