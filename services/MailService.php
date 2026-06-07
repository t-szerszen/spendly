<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Klasa MailService
 *
 * Odpowiada za wysyłkę wiadomości e-mail z aplikacji.
 * Aktualnie obsługuje zaproszenia do wspólnych budżetów z użyciem PHPMailer
 * oraz konfiguracji SMTP pobieranej ze zmiennych środowiskowych.
 */
class MailService
{
    /**
     * Wysyła wiadomość z zaproszeniem do wspólnego budżetu.
     */
    public function sendSharedBudgetInvitation($toEmail, $sharedBudgetName, $inviterName, $inviteUrl)
    {
        try {
            // Konfiguracja SMTP jest pobierana z pliku środowiskowego aplikacji.
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

            // Wersja HTML korzysta z szablonu widoku, a AltBody zapewnia treść tekstową.
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
            // Błąd wysyłki nie ujawnia szczegółów użytkownikowi, ale trafia do logów serwera.
            error_log('Błąd wysyłki maila zaproszenia: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Renderuje szablon HTML wiadomości zaproszenia.
     */
    private function renderInvitationTemplate(array $data)
    {
        // Bufor wyjścia pozwala wykorzystać plik widoku jako treść wiadomości.
        ob_start();
        $template = __DIR__ . '/../views/emails/shared_budget_invitation.php';
        include $template;

        return ob_get_clean();
    }
}
