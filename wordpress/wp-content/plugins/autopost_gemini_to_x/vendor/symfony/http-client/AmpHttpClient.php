<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient;

use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\InterceptedHttpClient;
use Amp\Http\Client\PooledHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\HttpMessage;
use Amp\Http\Tunnel\Http1TunnelConnector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Internal\AmpClientStateV4;
use Symfony\Component\HttpClient\Internal\AmpClientStateV5;
use Symfony\Component\HttpClient\Response\AmpResponseV4;
use Symfony\Component\HttpClient\Response\AmpResponseV5;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

if (!interface_exists(DelegateHttpClient::class)) {
    throw new \LogicException('You cannot use "Symfony\Component\HttpClient\AmpHttpClient" as the "amphp/http-client" package is not installed. Try running "composer require amphp/http-client:^5".');
}

if (\PHP_VERSION_ID < 80400 && is_subclass_of(Request::class, HttpMessage::class)) {
    throw new \LogicException('Using "Symfony\Component\HttpClient\AmpHttpClient" with amphp/http-client >= 5 requires PHP >= 8.4. Try running "composer require amphp/http-client:^4.2.1" or upgrade to PHP >= 8.4.');
}

/**
 * A portable implementation of the HttpClientInterface contracts based on Amp's HTTP client.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class AmpHttpClient implements HttpClientInterface, LoggerAwareInterface, ResetInterface
{
    use HttpClientTrait;
    use LoggerAwareTrait;

    public const OPTIONS_DEFAULTS = HttpClientInterface::OPTIONS_DEFAULTS + [
        'crypto_method' => \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
    ];

    private array $defaultOptions = self::OPTIONS_DEFAULTS;
    private static array $emptyDefaults = self::OPTIONS_DEFAULTS;
    private AmpClientStateV4|AmpClientStateV5 $multi;

    /**
     * @param array         $defaultOptions     Default requests' options
     * @param callable|null $clientConfigurator A callable that builds a {@see DelegateHttpClient} from a {@see PooledHttpClient};
     *                                          passing null builds an {@see InterceptedHttpClient} with 2 retries on failures
     * @param int           $maxHostConnections The maximum number of connections to a single host
     * @param int           $maxPendingPushes   The maximum number of pushed responses to accept in the queue
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public function __construct(array $defaultOptions = [], ?callable $clientConfigurator = null, int $maxHostConnections = 6, int $maxPendingPushes = 50)
    {
        $this->defaultOptions['buffer'] ??= self::shouldBuffer(...);

        if ($defaultOptions) {
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, $this->defaultOptions);
        }

        if (is_subclass_of(Request::class, HttpMessage::class)) {
            $this->multi = new AmpClientStateV5($clientConfigurator, $maxHostConnections, $maxPendingPushes, $this->logger);
        } else {
            $this->multi = new AmpClientStateV4($clientConfigurator, $maxHostConnections, $maxPendingPushes, $this->logger);
        }
    }

    /**
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        [$url, $options] = self::prepareRequest($method, $url, $options, $this->defaultOptions);

        $options['proxy'] = self::getProxy($options['proxy'], $url, $options['no_proxy']);

        if (null !== $options['proxy'] && !class_exists(Http1TunnelConnector::class)) {
            throw new \LogicException('You cannot use the "proxy" option as the "amphp/http-tunnel" package is not installed. Try running "composer require amphp/http-tunnel".');
        }

        if ($options['bindto']) {
            if (str_starts_with($options['bindto'], 'if!')) {
                throw new TransportException(__CLASS__.' cannot bind to network interfaces, use e.g. CurlHttpClient instead.');
            }
            if (str_starts_with($options['bindto'], 'host!')) {
                $options['bindto'] = substr($options['bindto'], 5);
            }
        }

        if (('' !== $options['body'] || 'POST' === $method || isset($options['normalized_headers']['content-length'])) && !isset($options['normalized_headers']['content-type'])) {
            $options['headers'][] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if (!isset($options['normalized_headers']['user-agent'])) {
            $options['headers'][] = 'User-Agent: Symfony HttpClient (Amp)';
        }

        if (0 < $options['max_duration']) {
            $options['timeout'] = min($options['max_duration'], $options['timeout']);
        }

        if ($options['resolve']) {
            $this->multi->dnsCache = $options['resolve'] + $this->multi->dnsCache;
        }

        if ($options['peer_fingerprint'] && !isset($options['peer_fingerprint']['pin-sha256'])) {
            throw new TransportException(__CLASS__.' supports only "pin-sha256" fingerprints.');
        }

        $request = new Request(implode('', $url), $method);
        $request->setBodySizeLimit(0);

        if ($options['http_version']) {
            $request->setProtocolVersions(match ((float) $options['http_version']) {
                1.0 => ['1.0'],
                1.1 => ['1.1', '1.0'],
                default => ['2', '1.1', '1.0'],
            });
        }

        foreach ($options['headers'] as $v) {
            $h = explode(': ', $v, 2);
            $request->addHeader($h[0], $h[1]);
        }

        $coef = $request instanceof HttpMessage ? 1 : 1000;
        $request->setTcpConnectTimeout($coef * $options['timeout']);
        $request->setTlsHandshakeTimeout($coef * $options['timeout']);
        $request->setTransferTimeout($coef * $options['max_duration']);
        if (method_exists($request, 'setInactivityTimeout')) {
            $request->setInactivityTimeout(0);
        }

        if ('' !== $request->getUri()->getUserInfo() && !$request->hasHeader('authorization')) {
            $auth = explode(':', $request->getUri()->getUserInfo(), 2);
            $auth = array_map('rawurldecode', $auth) + [1 => ''];
            $request->setHeader('Authorization', 'Basic '.base64_encode(implode(':', $auth)));
        }

        if ($request instanceof HttpMessage) {
            return new AmpResponseV5($this->multi, $request, $options, $this->logger);
        }

        return new AmpResponseV4($this->multi, $request, $options, $this->logger);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof AmpResponseV4 || $responses instanceof AmpResponseV5) {
            $responses = [$responses];
        }

        if ($this->multi instanceof AmpClientStateV5) {
            return new ResponseStream(AmpResponseV5::stream($responses, $timeout));
        }

        return new ResponseStream(AmpResponseV4::stream($responses, $timeout));
    }

    public function reset(): void
    {
        $this->multi->dnsCache = [];

        foreach ($this->multi->pushedResponses as $pushedResponses) {
            foreach ($pushedResponses as [$pushedUrl, $pushDeferred]) {
                if ($pushDeferred instanceof DeferredFuture) {
                    $pushDeferred->error(new CancelledException());
                } else {
                    $pushDeferred->fail(new CancelledException());
                }

                $this->logger?->debug(\sprintf('Unused pushed response: "%s"', $pushedUrl));
            }
        }

        $this->multi->pushedResponses = [];
    }
}
