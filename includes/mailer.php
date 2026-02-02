<?php
/**
 * SMTP Mailer
 * Ren PHP SMTP-klient utan externa beroenden
 * Stöd för SSL (port 465) och STARTTLS (port 587)
 */

class SmtpMailer {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private int $timeout;
    private $socket = null;

    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption = 'ssl',
        int $timeout = 30
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = strtolower($encryption);
        $this->timeout = $timeout;
    }

    /**
     * Skicka plaintext-mail
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        string $from_name = '',
        string $from_email = '',
        string $reply_to = ''
    ): bool {
        $headers = $this->buildHeaders($from_name, $from_email, $to, $subject, $reply_to, false);
        return $this->sendMessage($to, $from_email, $headers, $body);
    }

    /**
     * Skicka HTML-mail
     */
    public function sendHtml(
        string $to,
        string $subject,
        string $htmlBody,
        string $from_name = '',
        string $from_email = '',
        string $reply_to = ''
    ): bool {
        $headers = $this->buildHeaders($from_name, $from_email, $to, $subject, $reply_to, true);
        return $this->sendMessage($to, $from_email, $headers, $htmlBody);
    }

    /**
     * Bygg MIME-headers
     */
    private function buildHeaders(
        string $from_name,
        string $from_email,
        string $to,
        string $subject,
        string $reply_to,
        bool $html
    ): string {
        if (empty($from_email)) {
            $from_email = $this->username;
        }

        $from = $from_name
            ? '=?UTF-8?B?' . base64_encode($from_name) . '?= <' . $from_email . '>'
            : $from_email;

        $headers  = "Date: " . date('r') . "\r\n";
        $headers .= "From: " . $from . "\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if ($html) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        $headers .= "Content-Transfer-Encoding: base64\r\n";

        if (!empty($reply_to)) {
            $headers .= "Reply-To: " . $reply_to . "\r\n";
        }

        $headers .= "X-Mailer: Bosse-Template-PHP/1.0\r\n";

        return $headers;
    }

    /**
     * Skicka meddelande via SMTP
     */
    private function sendMessage(string $to, string $from_email, string $headers, string $body): bool {
        if (empty($from_email)) {
            $from_email = $this->username;
        }

        try {
            $this->connect();
            $this->authenticate();

            // MAIL FROM
            $response = $this->sendCommand("MAIL FROM:<{$from_email}>");
            if (!$this->isResponseOk($response, 250)) {
                throw new \RuntimeException("MAIL FROM rejected: {$response}");
            }

            // RCPT TO
            $response = $this->sendCommand("RCPT TO:<{$to}>");
            if (!$this->isResponseOk($response, 250)) {
                throw new \RuntimeException("RCPT TO rejected: {$response}");
            }

            // DATA
            $response = $this->sendCommand("DATA");
            if (!$this->isResponseOk($response, 354)) {
                throw new \RuntimeException("DATA rejected: {$response}");
            }

            // Skicka headers + body
            $message = $headers . "\r\n" . chunk_split(base64_encode($body)) . "\r\n.";
            $response = $this->sendCommand($message);
            if (!$this->isResponseOk($response, 250)) {
                throw new \RuntimeException("Message rejected: {$response}");
            }

            $this->close();
            return true;

        } catch (\Throwable $e) {
            error_log("SMTP Error: " . $e->getMessage());
            $this->close();
            return false;
        }
    }

    /**
     * Anslut till SMTP-server
     */
    private function connect(): void {
        $address = $this->host;

        if ($this->encryption === 'ssl') {
            $address = 'ssl://' . $this->host;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $this->socket = @stream_socket_client(
            "{$address}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new \RuntimeException("Could not connect to {$this->host}:{$this->port} - {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Läs server-greeting
        $greeting = $this->readResponse();
        if (!$this->isResponseOk($greeting, 220)) {
            throw new \RuntimeException("Unexpected greeting: {$greeting}");
        }

        // EHLO
        $response = $this->sendCommand("EHLO " . gethostname());
        if (!$this->isResponseOk($response, 250)) {
            // Fallback till HELO
            $response = $this->sendCommand("HELO " . gethostname());
            if (!$this->isResponseOk($response, 250)) {
                throw new \RuntimeException("EHLO/HELO rejected: {$response}");
            }
        }

        // STARTTLS om encryption = tls
        if ($this->encryption === 'tls') {
            $response = $this->sendCommand("STARTTLS");
            if (!$this->isResponseOk($response, 220)) {
                throw new \RuntimeException("STARTTLS rejected: {$response}");
            }

            $crypto = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            if (!$crypto) {
                throw new \RuntimeException("TLS handshake failed");
            }

            // EHLO igen efter TLS
            $response = $this->sendCommand("EHLO " . gethostname());
            if (!$this->isResponseOk($response, 250)) {
                throw new \RuntimeException("EHLO after STARTTLS rejected: {$response}");
            }
        }
    }

    /**
     * Autentisera med AUTH LOGIN
     */
    private function authenticate(): void {
        $response = $this->sendCommand("AUTH LOGIN");
        if (!$this->isResponseOk($response, 334)) {
            throw new \RuntimeException("AUTH LOGIN rejected: {$response}");
        }

        $response = $this->sendCommand(base64_encode($this->username));
        if (!$this->isResponseOk($response, 334)) {
            throw new \RuntimeException("Username rejected: {$response}");
        }

        $response = $this->sendCommand(base64_encode($this->password));
        if (!$this->isResponseOk($response, 235)) {
            throw new \RuntimeException("Authentication failed: {$response}");
        }
    }

    /**
     * Skicka SMTP-kommando och läs svar
     */
    private function sendCommand(string $command): string {
        if (!$this->socket) {
            throw new \RuntimeException("Not connected to SMTP server");
        }

        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }

    /**
     * Läs svar från SMTP-server
     */
    private function readResponse(): string {
        if (!$this->socket) {
            return '';
        }

        $response = '';
        while (true) {
            $line = fgets($this->socket, 4096);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // SMTP multiline: om 4:e tecknet är mellanslag är det sista raden
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
            // Enradig respons utan continuation
            if (strlen($line) < 4) {
                break;
            }
        }

        return trim($response);
    }

    /**
     * Kontrollera om responsekod matchar
     */
    private function isResponseOk(string $response, int $expected): bool {
        return (int)substr($response, 0, 3) === $expected;
    }

    /**
     * Stäng anslutning
     */
    private function close(): void {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }
}

/**
 * Wrapper-funktion för att skicka mail
 *
 * @param string $to Mottagare
 * @param string $subject Ämne
 * @param string $body Meddelande
 * @param array $options Extra alternativ: html (bool), reply_to, from_name, from_email
 * @return bool
 */
function send_mail(string $to, string $subject, string $body, array $options = []): bool {
    // Kontrollera att SMTP-config finns
    if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        error_log('send_mail: SMTP-konstanter är inte definierade. Konfigurera SMTP i config.php.');
        return false;
    }

    $html = $options['html'] ?? false;
    $reply_to = $options['reply_to'] ?? '';
    $from_name = $options['from_name'] ?? (defined('SITE_NAME') ? SITE_NAME : '');
    $from_email = $options['from_email'] ?? SMTP_USERNAME;
    $encryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'ssl';

    $mailer = new SmtpMailer(
        SMTP_HOST,
        (int)SMTP_PORT,
        SMTP_USERNAME,
        SMTP_PASSWORD,
        $encryption
    );

    if ($html) {
        return $mailer->sendHtml($to, $subject, $body, $from_name, $from_email, $reply_to);
    }

    return $mailer->send($to, $subject, $body, $from_name, $from_email, $reply_to);
}
