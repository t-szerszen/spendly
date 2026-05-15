<?php
/**
 * Komponent: Nawigacja Publiczna (Nav)
 * 
 * Górny pasek nawigacyjny dołączany do widoków przeznaczonych dla niezalogowanych
 * użytkowników (strona główna, o nas, kontakt). Zawiera logo oraz odnośniki.
 */
?>
<header>
	<div class="nav-container">
		<a href="<?= url('/') ?>" class="logo">
			<img src="<?= asset('logo-napis.png') ?>" alt="Spendly Logo">
		</a>
		<nav class="nav-links">
			<a href="<?= url('/') ?>">Strona Główna</a>
			<a href="<?= url('about') ?>">O nas</a>
			<a href="<?= url('contact') ?>">Kontakt</a>
			<a href="<?= url('login') ?>" class="btn-primary">Zaloguj się</a>
		</nav>
	</div>
</header>