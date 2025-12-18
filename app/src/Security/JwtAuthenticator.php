<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class JwtAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();

        // No exigir JWT para la documentación
        if (str_starts_with($path, '/api/doc') || str_starts_with($path, '/api/docs')) {
            return false;
        }

        return str_starts_with($path, '/api');
    }


    public function authenticate(Request $request): SelfValidatingPassport
    {
        $auth = $request->headers->get('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            throw new AuthenticationException('Missing Bearer token');
        }

        $jwt = trim($m[1]);

        try {
            $secret  = $_ENV['JWT_SECRET'] ?? '';
            $issuer  = $_ENV['JWT_ISSUER'] ?? '';
            $aud     = $_ENV['JWT_AUDIENCE'] ?? '';

            if ($secret === '') {
                throw new AuthenticationException('JWT_SECRET not configured');
            }

            // Firma + decodificación (HS256)
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));

            // Validaciones equivalentes a .NET:
            // ValidateIssuer = true
            if ($issuer !== '' && (($decoded->iss ?? null) !== $issuer)) {
                throw new AuthenticationException('Invalid issuer');
            }

            // ValidateAudience = true
            if ($aud !== '' && (($decoded->aud ?? null) !== $aud)) {
                throw new AuthenticationException('Invalid audience');
            }

            // ValidateLifetime = true (firebase/php-jwt valida exp si existe;
            // aquí exigimos que exista para mantener estándar)
            if (!isset($decoded->exp)) {
                throw new AuthenticationException('Token missing exp');
            }

            // Identidad: usa "sub" típicamente
            $identifier = (string)($decoded->sub ?? 'user');

        } catch (\Throwable $e) {
            throw new AuthenticationException('Invalid token');
        }

        // Usuario “ligero” para autorizar ROLE_USER (stateless, sin DB)
        return new SelfValidatingPassport(
            new UserBadge($identifier, fn() => new InMemoryUser($identifier, null, ['ROLE_USER']))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        return null; // continúa a controller
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}
