<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Mailgun\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunHttpTransport;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Email;

class MailgunHttpTransportTest extends TestCase
{
    /**
     * @dataProvider getTransportData
     */
    public function testToString(MailgunHttpTransport $transport, string $expected)
    {
        $this->assertSame($expected, (string) $transport);
    }

    public function getTransportData()
    {
        return [
            [
                new MailgunHttpTransport('ACCESS_KEY', 'DOMAIN'),
                'mailgun+https://api.mailgun.net?domain=DOMAIN',
            ],
            [
                new MailgunHttpTransport('ACCESS_KEY', 'DOMAIN', 'us-east-1'),
                'mailgun+https://api.us-east-1.mailgun.net?domain=DOMAIN',
            ],
            [
                (new MailgunHttpTransport('ACCESS_KEY', 'DOMAIN'))->setHost('example.com'),
                'mailgun+https://example.com?domain=DOMAIN',
            ],
            [
                (new MailgunHttpTransport('ACCESS_KEY', 'DOMAIN'))->setHost('example.com')->setPort(99),
                'mailgun+https://example.com:99?domain=DOMAIN',
            ],
        ];
    }

    public function testTagAndMetadataHeaders()
    {
        $email = new Email();
        $email->getHeaders()->addTextHeader('foo', 'bar');
        $email->getHeaders()->add(new TagHeader('password-reset'));
        $email->getHeaders()->add(new MetadataHeader('Color', 'blue'));
        $email->getHeaders()->add(new MetadataHeader('Client-ID', '12345'));

        $transport = new MailgunHttpTransport('key', 'domain');
        $method = new \ReflectionMethod(MailgunHttpTransport::class, 'addMailgunHeaders');
        $method->setAccessible(true);
        $method->invoke($transport, $email);

        $this->assertCount(3, $email->getHeaders()->toArray());
        $this->assertSame('foo: bar', $email->getHeaders()->get('foo')->toString());
        $this->assertSame('X-Mailgun-Tag: password-reset', $email->getHeaders()->get('X-Mailgun-Tag')->toString());
        $this->assertSame('X-Mailgun-Variables: '.json_encode(['Color' => 'blue', 'Client-ID' => '12345']), $email->getHeaders()->get('X-Mailgun-Variables')->toString());
    }
}