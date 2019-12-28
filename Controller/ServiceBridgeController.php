<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Controller;

use Cosmologist\Bundle\SymfonyCommonBundle\Bridge\ServiceBridge;
use Cosmologist\Gears\StringType;
use Exception;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Entry-point to the ServiceBridge from HTTP.
 */
class ServiceBridgeController
{
    /**
     * @var ServiceBridge
     */
    private $serviceBridge;

    /**
     * ServiceBridgeController constructor.
     *
     * @param ServiceBridge $serviceBridge
     */
    public function __construct(ServiceBridge $serviceBridge)
    {
        $this->serviceBridge = $serviceBridge;
    }

    /**
     * Calls the specified service
     *
     * Pass the service arguments through POST-parameters.
     * If the service expects the entity as argument (type-hint exists) - pass the entity identifier instead,
     * the suitable entity will be loaded automatically (Doctrine is used, but you can use the custom loader in the future).
     *
     * @param Request $request Request
     * @param string  $service Service identifier
     * @param string  $method  Service method
     *
     * @return Response|JsonResponse Simple response for scalar results and JSON for other
     */
    public function callAction(Request $request, string $service, string $method): Response
    {
        try {
            $result = $this->serviceBridge->call($service, $method, array_merge($request->request->all(), $request->query->all()));
        } catch (ServiceNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        if (is_scalar($result)) {
            $response = new Response($result);

            if (StringType::isBinary($result)) {
                $ext = StringType::guessExtension($result);
                $filename = $ext === null ? 'file' : 'file.' . $ext;

                $response->headers->add(
                    [
                        'Content-Type'        => StringType::guessMime($result),
                        'Content-Disposition' => $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename),
                    ]
                );
            }

            return $response;
        }

        return new JsonResponse($result);
    }
}