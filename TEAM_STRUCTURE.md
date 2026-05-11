# 📋 Podział Odpowiedzialności – Projekt Spendly

Dokument określający podział prac nad projektem na potrzeby obrony praktyk zawodowych.

---

## 🎨 Frontend
**Opiekun:** Mateusz Grzelczyk

Odpowiada za warstwę wizualną, interakcję z użytkownikiem oraz logikę prezentacji danych.

| Obszar | Opis |
| :--- | :--- |
| `scripts/` | Logika kliencka (JavaScript), obsługa dynamicznych elementów UI. |
| `styles/` | Warstwa wizualna, arkusze stylów CSS, responsywność (RWD). |
| `views/` | Szablony PHP generujące strukturę HTML stron. |
| `views/components/` | Reużywalne komponenty interfejsu (modale, przyciski, nawigacja). |
| `images/` | Zasoby graficzne, ikony i multimedia wykorzystywane w aplikacji. |

---

## ⚙️ Backend & Architecture
**Opiekunowie:** Bartosz Linke, Tobiasz Szerszeń

Odpowiadają za logikę biznesową, strukturę bazy danych, bezpieczeństwo oraz integrację systemową.

| Obszar | Opis |
| :--- | :--- |
| `controllers/` | Obsługa żądań HTTP, pośrednictwo między Modelem a Widokiem. |
| `core/` | Rdzeń aplikacji: Routing, obsługa sesji, połączenie z bazą danych (PDO). |
| `models/` | Reprezentacja danych, operacje na bazie danych (CRUD), logika encji. |
| `services/` | Usługi pomocnicze (np. Auth, Walidacja, obsługa transakcji). |
| **Główny katalog** | Konfiguracja serwera, `.htaccess`, `index.php`, pliki środowiskowe `.env`. |

---

### 📂 Pozostałe pliki (Backend)
- `index.php` – Główny punkt wejścia aplikacji (Entry point).
- `.htaccess` – Konfiguracja przekierowań i bezpieczeństwa serwera.
- `.env` / `config.php` – Zarządzanie konfiguracją i danymi wrażliwymi.
- `db.sql` – Struktura bazy danych.
- `helpers.php` – Funkcje pomocnicze wykorzystywane globalnie w projekcie.

---

*Dokument sporządzony na potrzeby dokumentacji projektu Spendly.*
