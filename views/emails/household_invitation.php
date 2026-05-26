<?php
$householdName = $data['householdName'];
$inviterName = $data['inviterName'];
$inviteUrl = $data['inviteUrl'];
$toEmail = $data['toEmail'];
?>
<!doctype html>
<html lang="pl">
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:600px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:20px;padding:32px;border:1px solid #e5e7eb;">
            <div style="margin-bottom:24px;">
                <div style="font-size:14px;letter-spacing:0.08em;text-transform:uppercase;color:#6b7280;">Spendly</div>
                <h1 style="margin:8px 0 0;font-size:28px;line-height:1.2;color:#111827;">Zaproszenie do gospodarstwa</h1>
            </div>

            <p style="font-size:16px;line-height:1.6;margin:0 0 16px;">
                <strong><?= htmlspecialchars($inviterName) ?></strong> zaprosił Cię do gospodarstwa domowego
                <strong><?= htmlspecialchars($householdName) ?></strong> w Spendly.
            </p>

            <p style="font-size:15px;line-height:1.6;margin:0 0 24px;color:#4b5563;">
                Ten link jest przeznaczony dla adresu <?= htmlspecialchars($toEmail) ?>.
            </p>

            <div style="text-align:center;margin:32px 0;">
                <a href="<?= htmlspecialchars($inviteUrl) ?>"
                   style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:999px;font-weight:700;">
                    Dołącz do gospodarstwa
                </a>
            </div>

            <p style="font-size:13px;line-height:1.6;color:#6b7280;margin:0;">
                Jeśli przycisk nie działa, skopiuj ten link do przeglądarki:<br>
                <span style="word-break:break-all;"><?= htmlspecialchars($inviteUrl) ?></span>
            </p>
        </div>
    </div>
</body>
</html>
