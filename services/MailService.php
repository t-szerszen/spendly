<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public function sendSharedBudgetInvitation($toEmail, $sharedBudgetName, $inviterName, $inviteUrl)
    {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->setFrom(
                $_ENV['MAIL_FROM'] ?? ($_ENV['MAIL_USERNAME'] ?? ''),
                $_ENV['MAIL_FROM_NAME'] ?? 'Spendly'
            );
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Zaproszenie do wspólnego budżetu w Spendly';

            $mail->Body = $this->renderInvitationTemplate([
                'sharedBudgetName' => $sharedBudgetName,
                'inviterName' => $inviterName,
                'inviteUrl' => $inviteUrl,
                'toEmail' => $toEmail,
            ]);

            $mail->AltBody = sprintf(
                '%s zaprosił Cię do wspólnego budżetu "%s" w Spendly. Otwórz link: %s',
                $inviterName,
                $sharedBudgetName,
                $inviteUrl
            );

            return $mail->send();
        } catch (Exception $e) {
            error_log('Błąd wysyłki maila zaproszenia: ' . $e->getMessage());
            return false;
        }
    }

    private function renderInvitationTemplate(array $data)
    {
        ob_start();
        $template = __DIR__ . '/../views/emails/shared_budget_invitation.php';
        include $template;

        return ob_get_clean();
    }
}
