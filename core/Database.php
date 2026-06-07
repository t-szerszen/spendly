<?php
/**
 * Klasa Database
 * 
 * Odpowiada za utworzenie i udostępnienie połączenia PDO z bazą danych.
 * Implementuje wzorzec Singleton, aby w trakcie obsługi żądania korzystać
 * z jednej współdzielonej instancji połączenia.
 */
class Database
{
	private static ?self $instance = null;

	private PDO $pdo;

	private function __construct()
	{
		// Parametry połączenia są pobierane ze środowiska z wartościami domyślnymi dla lokalnego XAMPP.
		$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
		$dbname = $_ENV['DB_NAME'] ?? 'spendly';
		$username = $_ENV['DB_USER'] ?? 'root';
		$password = $_ENV['DB_PASS'] ?? '';

		try {
			// PDO pracuje w trybie wyjątków, zwraca tablice asocjacyjne i używa natywnych prepared statements.
			$this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		} catch (PDOException $e) {
			// Szczegóły błędu trafiają do logów, a użytkownik otrzymuje neutralny komunikat.
			error_log('KRYTYCZNY BŁĄD POŁĄCZENIA Z BAZĄ: ' . $e->getMessage());
			die('Wystąpił błąd serwera. Prosimy spróbować później.');
		}
	}
	public static function getInstance(): self
	{
		// Pierwsze wywołanie tworzy instancję; kolejne zwracają już istniejące połączenie.
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	public function getConnection(): PDO
	{
		// Udostępnia obiekt PDO modelom i serwisom pracującym z bazą danych.
		return $this->pdo;
	}
}

