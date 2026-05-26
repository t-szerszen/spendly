# Podział Odpowiedzialności – Projekt Spendly

Dokument określający podział prac nad projektem na potrzeby obrony praktyk zawodowych.

---

## Frontend
**Opiekun:** Mateusz Grzelczyk

Odpowiada za warstwę wizualną, interakcję z użytkownikiem oraz logikę prezentacji danych.

| `views/` | Szablony PHP generujące strony aplikacji. |
| `views/components/` | Współdzielone elementy interfejsu, np. nawigacja, head, sekcje powtarzalne. |
| `styles/` | Arkusze CSS, stylowanie widoków i responsywność. |
| `scripts/` | Logika JavaScript obsługująca interakcje po stronie klienta. |
| `images/` | Zasoby graficzne, logo, ikony i pozostałe multimedia. |

---

## Backend & Architecture
**Opiekunowie:** Bartosz Linke, Tobiasz Szerszeń

Odpowiada za warstwę wizualną, interakcję z użytkownikiem oraz logikę prezentacji danych.

| `core/` | Rdzeń aplikacji: routing oraz połączenie z bazą danych przez PDO. |
| `controllers/` | Obsługa żądań HTTP i przekazywanie danych między modelem a widokiem. |
| `models/` | Operacje na danych i zapytania do bazy, m.in. użytkownicy i transakcje. |
| `services/` | Logika pomocnicza, np. autoryzacja i obsługa sesji. |
| `db/` | Schemat bazy danych i migracje. |
| **Główny katalog** | Pliki konfiguracyjne serwera i środowiska. |

---

## Pozostałe pliki backendowe

- `index.php` - Punkt wejścia aplikacji. Inicjalizuje sesję i przekazuje żądanie do routera. 
- `.htaccess` - Przekierowuje ruch do `index.php` i wspiera routing oparty o URL.
- `.env` - Zawiera dane do połączenia z bazą danych.
- `config.php` - Definiuje podstawową konfigurację aplikacji, np. `BASE_URL`.
- `helpers.php` - Zawiera globalne funkcje pomocnicze, używane w całym projekcie.
- `db/schema.sql` - Definiuje strukturę bazy danych.
- `db/migrations/` - Historia migracji bazodanowych.

---

## Architektura
**Opiekun** Tobiasz Szerszeń

W projekcie zastosowano architekturę MVC oraz uporządkowany workflow pracy oparty na systemie kontroli wersji Git. Struktura aplikacji została zaprojektowana w sposób umożliwiający czytelny podział odpowiedzialności między modele, widoki i kontrolery.

Kluczowe decyzje architektoniczne:
- Zastosowanie wzorca MVC (Model-View-Controller) w celu oddzielenia logiki biznesowej, operacji na danych oraz warstwy prezentacji.
- Wykorzystanie wzorca Singleton dla połączenia PDO, co zapewnia jedną współdzieloną instancję połączenia z bazą danych.
- Wprowadzenie migracyjnego podejścia do zarządzania bazą danych, ułatwiającego synchronizację struktury bazy oraz bezpieczne wdrażanie zmian.
- Zastosowanie routingu opartego na URI, umożliwiającego tworzenie czytelnych i przyjaznych ścieżek aplikacji.
- Organizacja pracy z wykorzystaniem Git, co pozwoliło na kontrolę zmian, współpracę zespołową i bezpieczne rozwijanie projektu.

---

*Dokument sporządzony na potrzeby dokumentacji projektu Spendly.*