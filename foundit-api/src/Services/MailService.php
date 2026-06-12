<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public function sendClaimFiled(array $data): bool
    {
        $subject = 'New claim filed for "' . $data['item_title'] . '"';
        $body = $this->render([
            'Hi ' . $data['owner_name'] . ',',
            $data['claimant_name'] . ' filed a claim for your ' . $data['item_type'] . ' item: "' . $data['item_title'] . '".',
            'Location: ' . $data['item_location'],
            'Claim message:',
            $data['claim_message'],
            'Please log in to FoundIt to approve or reject this claim.',
        ]);

        return $this->send($data['owner_email'], $data['owner_name'], $subject, $body);
    }

    public function sendClaimReviewed(array $data): bool
    {
        $statusText = $data['status'] === 'approved' ? 'approved' : 'rejected';
        $subject = 'Your claim was ' . $statusText . ' for "' . $data['item_title'] . '"';
        $body = $this->render([
            'Hi ' . $data['claimant_name'] . ',',
            'Your claim for "' . $data['item_title'] . '" was ' . $statusText . '.',
            'Item type: ' . $data['item_type'],
            'Location: ' . $data['item_location'],
            $data['status'] === 'approved'
                ? 'Please contact the poster through your campus process to arrange the return.'
                : 'You can still browse other matching items on FoundIt.',
        ]);

        return $this->send($data['claimant_email'], $data['claimant_name'], $subject, $body);
    }

    private function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        if (!$this->isConfigured()) {
            error_log('FoundIt mail skipped: SMTP settings are not configured.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->Port = $this->intSetting('MAIL_PORT', 587, 1, 65535);
            $mail->Timeout = $this->intSetting('MAIL_TIMEOUT', 6, 1, 30);
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];

            $encryption = strtolower(trim($_ENV['MAIL_ENCRYPTION'] ?? 'tls'));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $fromEmail = trim($_ENV['MAIL_FROM_EMAIL'] ?? '') ?: $_ENV['MAIL_USERNAME'];
            $fromName = trim($_ENV['MAIL_FROM_NAME'] ?? '') ?: 'FoundIt';

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;
            $mail->isHTML(false);

            return $mail->send();
        } catch (MailException $e) {
            error_log('FoundIt mail failed: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            error_log('FoundIt mail failed unexpectedly: ' . $e->getMessage());
            return false;
        }
    }

    private function isConfigured(): bool
    {
        foreach (['MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD'] as $key) {
            if (trim($_ENV[$key] ?? '') === '') {
                return false;
            }
        }

        return true;
    }

    private function render(array $lines): string
    {
        return implode("\n\n", $lines) . "\n";
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = filter_var($_ENV[$key] ?? null, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            return $default;
        }

        return $value;
    }
}
