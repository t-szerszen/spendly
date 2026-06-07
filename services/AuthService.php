<?php
// services/AuthService.php

/**
 * Klasa AuthService
 * 
 * Odpowiada za logikę biznesową związaną z autoryzacją i uwierzytelnianiem.
 * Stanowi łącznik między kontrolerami a modelem User.
 */
class AuthService
{
    private const NAME_MAX_LENGTH = 50;
    private const EMAIL_MAX_LENGTH = 191;
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 72;

    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Rejestruje nowego użytkownika.
     */
    public function register($data)
    {
        $normalizedData = $this->normalizeRegistrationData($data);
        $validationError = $this->validateRegistrationData($normalizedData);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError];
        }

        // Sprawdzenie czy email już istnieje
        if ($this->userModel->findByEmail($normalizedData['email'])) {
            return ['success' => false, 'error' => 'Ten email jest już zajęty.'];
        }

        // Hashowanie hasła
        $normalizedData['password'] = password_hash($normalizedData['password'], PASSWORD_BCRYPT);

        $result = $this->userModel->create($normalizedData);

        if ($result) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Wystąpił błąd podczas rejestracji.'];
    }

    /**
     * Loguje użytkownika.
     */
    public function login($email, $password)
    {
        $normalizedEmail = mb_strtolower(trim((string) $email));
        $password = (string) $password;

        if ($normalizedEmail === '' || $password === '') {
            return ['success' => false, 'error' => 'Podaj email i hasło.'];
        }

        if (mb_strlen($normalizedEmail) > self::EMAIL_MAX_LENGTH || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Podaj poprawny adres e-mail.'];
        }

        $user = $this->userModel->findByEmail($normalizedEmail);

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email'] = $user['email'];

            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                $this->userModel->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));
            }

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Błędny email lub hasło.'];
    }

    /**
     * Wylogowuje użytkownika.
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
    }

    /**
     * Sprawdza czy użytkownik jest zalogowany.
     */
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }

    private function normalizeRegistrationData(array $data): array
    {
        return [
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($data['email'] ?? ''))),
            'password' => (string) ($data['password'] ?? ''),
        ];
    }

    private function validateRegistrationData(array $data): ?string
    {
        if ($data['first_name'] === '' || $data['last_name'] === '' || $data['email'] === '' || $data['password'] === '') {
            return 'Wypełnij wszystkie wymagane pola.';
        }

        if (!$this->isValidName($data['first_name'])) {
            return 'Podaj poprawne imię.';
        }

        if (!$this->isValidName($data['last_name'])) {
            return 'Podaj poprawne nazwisko.';
        }

        if (mb_strlen($data['email']) > self::EMAIL_MAX_LENGTH || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Podaj poprawny adres e-mail.';
        }

        $passwordLength = strlen($data['password']);
        if ($passwordLength < self::PASSWORD_MIN_LENGTH) {
            return 'Hasło musi mieć co najmniej 8 znaków.';
        }

        if ($passwordLength > self::PASSWORD_MAX_LENGTH) {
            return 'Hasło jest zbyt długie.';
        }

        if (
            !preg_match('/[A-Za-z]/', $data['password'])
            || !preg_match('/\d/', $data['password'])
            || !preg_match('/[^A-Za-z0-9]/', $data['password'])
        ) {
            return 'Hasło musi zawierać co najmniej jedną literę, jedną cyfrę i jeden znak specjalny.';
        }

        return null;
    }

    private function isValidName(string $value): bool
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > self::NAME_MAX_LENGTH) {
            return false;
        }

        return preg_match("/^[\\p{L}][\\p{L}\\s'\\-]*$/u", $value) === 1;
    }
}
