<?php
$title = $data['title'] ?? 'Błąd 404';
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo $title; ?>
    </title>
</head>

<body>
    <h1>404 - Nie znaleziono</h1>
    <p>Przepraszamy, szukana strona nie istnieje.</p>
    <nav>
        <a href="/">Wróć na stronę główną</a>
    </nav>
</body>

</html>