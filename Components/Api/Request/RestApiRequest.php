<?php

namespace ShopwarePlugins\Connect\Components\Api\Request;

use ShopwarePlugins\Connect\Components\Config;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestApiRequest
{
    const AUD = 'Shopware-connect';

    /** @var Config $config */
    private $config;

    /**
     * RestApiRequest constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function verifyRequest($request)
    {
        $config = $this->config->getGeneralConfig()[0];

        $requestParams = json_decode($request, true);

        try {
            if (!isset($requestParams['connectKey'])) {
                throw new \InvalidArgumentException('Connect key must not be empty');
            }

            JWT::$leeway = 60;
            $token = JWT::decode($requestParams['connectKey'], $config['apiKey'], ['HS256']);

            if ($token->aud != self::AUD) {
                throw new \InvalidArgumentException();
            }

            if ($token->iss != $config['shopId']) {
                throw new \InvalidArgumentException();
            }
        } catch (ExpiredException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Expired authentication key"
            ], 404);
        } catch (BeforeValidException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Authentication key is not valid yet."
            ], 404);
        } catch (SignatureInvalidException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Signature verification failed."
            ], 404);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Invalid authentication token."
            ], 404);
        }

        return new JsonResponse(array(
            'success' => true,
        ), 200);
    }
}