<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Spendly page') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('images/spendly-icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('images/spendly-icon.png') ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $pageStyles = $pageStyles ?? []; ?>
    <!-- Styl CSS -->
    <link rel="stylesheet" href="<?= url('styles/style.css') ?>?v=<?= time() ?>">
    <?php foreach ($pageStyles as $pageStyle): ?>
        <link rel="stylesheet" href="<?= url($pageStyle) ?>?v=<?= time() ?>">
    <?php endforeach; ?>
</head>
