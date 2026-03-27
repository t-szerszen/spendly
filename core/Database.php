<?php
class Database
{
	private static ?self $instance = null;

	private PDO $pdo;

	private function __construct()
	{
		$host = 'localhost';
		$dbname = 'spendly';
		$username = 'root';
		$password = '';

		try {
			$this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

		} catch (PDOException $e) {
			error_log('KRYTYCZNY BŁĄD POŁĄCZENIA Z BAZĄ: ' . $e->getMessage());
			die('Wystąpił błąd serwera. Prosimy spróbować później.');
		}
	}
	public static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	public function getConnection(): PDO
{
    return $this->pdo;
}
}