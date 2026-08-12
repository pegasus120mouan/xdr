<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TotpService
{
    public function __construct(protected Google2FA $google2fa)
    {
        $this->google2fa->setWindow(1);
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function otpauthUrl(string $email, string $secret): string
    {
        $issuer = (string) config('xdr.mfa_issuer', 'Kora Shield');

        return $this->google2fa->getQRCodeUrl($issuer, $email, $secret);
    }

    public function qrSvg(string $otpauthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    public function verify(string $secret, string $code, ?int $lastUsedTs = null): int|false
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $result = $this->google2fa->verifyKeyNewer($secret, $code, $lastUsedTs);
        if ($result === false) {
            return false;
        }

        return is_int($result) ? $result : (int) $this->google2fa->getTimestamp();
    }

    public function currentCode(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }
}
