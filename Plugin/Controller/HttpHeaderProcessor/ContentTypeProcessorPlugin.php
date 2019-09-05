<?php
namespace Adexos\GraphqlCors\Plugin\Controller\HttpHeaderProcessor;

use Magento\Framework\App\RequestInterface;
use Magento\GraphQl\Controller\HttpHeaderProcessor\ContentTypeProcessor;

/**
 * Class ContentTypeProcessorPlugin
 *
 * @package Adexos\GraphqlCors\Plugin\Controller\HttpHeaderProcessor
 */
class ContentTypeProcessorPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * ContentTypeProcessorPlugin constructor.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Disable content type verification for preflight request.
     *
     * @param ContentTypeProcessor $subject
     * @param callable $proceed
     * @param string $headerValue
     *
     * @return void
     *
     * @SuppressWarnings("unused")
     */
    public function aroundProcessHeaderValue(ContentTypeProcessor $subject, callable $proceed, string $headerValue)
    {
        if (!$this->request->isOptions()) {
            $proceed($headerValue);
        }
    }
}
