<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Base64Url\Base64Url;
use Ramsey\Uuid\Uuid;

class BogConsent extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'bog:consent';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Grab consent url from BOG';

    /**
     * API url.
     *
     * @var string
     */
    public $apiUrl = 'https://xs2a-sandbox.bog.ge/0.8/v1/consents';

    /**
     * API host.
     *
     * @var string
     */
    public $apiHost = 'xs2a-sandbox.bog.ge';

    /**
     * Payload to initiate consent.
     *
     * @var array
     */
    public $payload = [
        'access' => [
            'availableAccounts' => 'allAccounts',
        ],
        'recurringIndicator' => 'false',
        'validUntil' => '2022-04-30',
        'frequencyPerDay' => 1,
    ];

    /**
     * JWS header params.
     *
     * @var array
     */
    public $jwsHeaderParams = [
        'b64' => false,
        'crit' => [
            'sigT',
            'sigD',
            'b64',
        ],
        'sigT' => '',
        'sigD' => [
            'pars' => [
                '(request-target)',
                'host',
                'content-type',
                'psu-ip-address',
                'digest',
                'x-request-id',
            ],
            'mId' => 'http://uri.etsi.org/19182/HttpHeaders',
        ],
        'alg' => 'RS256',
        'x5c' => [],
    ];

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $theirCertName;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $ourCertName;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $certPassphrase;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $proxyPort;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $proxy;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $proxyUserPwd;

    /**
     * Their cert name.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->theirCertName  = \config('bog.theirCertName');
        $this->ourCertName    = \config('bog.ourCertName');
        $this->certPassphrase = \config('bog.certPassphrase');
        $this->proxyPort      = \config('bog.proxyPort');
        $this->proxy          = \config('bog.proxy');
        $this->proxyUserPwd   = \config('bog.proxyUserPwd');
        $this->redirectUrl    = \config('bog.redirectUrl');

        $this->jwsHeaderParams['sigT'] = gmdate('Y-m-d\TH:i:s\Z');
        $this->jwsHeaderParams['x5c']  = [ $this->getx5c() ];

        $headers      = $this->getHeader($this->payload, Uuid::uuid4()->toString());
        $signedHeader = $this->getSignedHeader($headers, $this->jwsHeaderParams);

        $this->request($this->payload, $signedHeader);
    }

    /**
     * Get x5c from PEM.
     *
     * @return string
     */
    protected function getx5c()
    {
        openssl_x509_export('file://certs/' . $this->theirCertName, $cert);
        $cert = array_filter(explode(PHP_EOL, $cert));

        // Remove comments.
        array_pop($cert);
        array_shift($cert);

        return implode('', $cert);
    }

    /**
     * Send request.
     *
     * @param array $payload    Payload.
     * @param array $httpHeader httpHeader.
     *
     * @return void
     */
    protected function request($payload, $httpHeader)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);

        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUserPwd);

        curl_setopt($ch, CURLOPT_SSLCERT, 'certs/' . $this->ourCertName);
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPassphrase);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // This needs to be fixed, 0 is a security risk.
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $result = curl_exec($ch);

        var_export($result);

        curl_close($ch);
    }

    /**
     * Get signed header for request.
     *
     * @param array $headers         Headers.
     * @param array $jwsHeaderParams JWS header parameters.
     *
     * @return array
     */
    protected function getSignedHeader($headers, $jwsHeaderParams)
    {
        $jwsHeaderParams = Base64Url::encode(json_encode($jwsHeaderParams));
        $toSign = $jwsHeaderParams . ".(request-target): post /0.8/v1/consents\n" . implode("\n", $headers);

        $pKey = openssl_pkey_get_private('file://certs/' . $this->theirCertName, $this->certPassphrase);
        openssl_sign($toSign, $jwsSignature, $pKey, OPENSSL_ALGO_SHA256);

        $headers[] = 'x-jws-signature: ' . $jwsHeaderParams . '..' . Base64Url::encode($jwsSignature);
        $headers[] = 'TPP-Redirect-URI: ' . $this->redirectUrl;

        return $headers;
    }

    /**
     * Get header for request.
     *
     * @param array  $payload Payload.
     * @param string $id      UUID.
     *
     * @return array
     */
    protected function getHeader($payload, $id)
    {
        return [
            'host: ' . $this->apiHost,
            'content-type: application/json',
            'psu-ip-address: ' . $this->proxy,
            'digest: SHA-256=' . base64_encode(
                hash(
                    'sha256',
                    json_encode($payload),
                    true
                )
            ),
            'x-request-id: ' . $id,
        ];
    }

}
