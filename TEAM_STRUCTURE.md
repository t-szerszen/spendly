# Podział Odpowiedzialności w Projekcie Spendly

Dokument określa główne obszary odpowiedzialności członków zespołu projektowego oraz opisuje strukturę techniczną projektu Spendly. Został przygotowany na potrzeby dokumentacji do obrony praktyk zawodowych.

## Cel dokumentu

Celem dokumentu jest przedstawienie:
- podziału odpowiedzialności pomiędzy członków zespołu,
- zakresu prac realizowanych w poszczególnych warstwach aplikacji,
- podstawowych założeń organizacyjnych i architektonicznych projektu.

Należy przy tym zaznaczyć, że wskazany podział odzwierciedla dominujące obszary odpowiedzialności, jednak prace nad projektem były prowadzone w sposób zespołowy i wymagały wzajemnej współpracy przy integracji poszczególnych modułów.

## Frontend

**Główna odpowiedzialność:** Mateusz Grzelczyk

Obszar frontendowy obejmował przygotowanie warstwy wizualnej aplikacji, organizację interfejsu użytkownika, obsługę interakcji po stronie klienta oraz prezentację danych zwracanych przez backend.

| Obszar | Zakres odpowiedzialności |
| --- | --- |
| `views/` | Szablony PHP generujące widoki aplikacji. |
| `views/components/` | Współdzielone elementy interfejsu, takie jak nawigacja, sekcja `<head>` oraz komponenty wielokrotnego użytku. |
| `styles/` | Arkusze CSS odpowiadające za wygląd, układ i responsywność aplikacji. |
| `scripts/` | Logika JavaScript wspierająca interakcję użytkownika po stronie klienta. |
| `images/` | Zasoby graficzne, w tym logo, ikony oraz pozostałe materiały wizualne. |

## Backend

**Główna odpowiedzialność:** Bartosz Linke, Tobiasz Szerszeń

Obszar backendowy obejmował implementację logiki biznesowej, obsługę żądań HTTP, komunikację z bazą danych, organizację warstwy usług oraz przygotowanie mechanizmów wspierających działanie aplikacji.

| Obszar | Zakres odpowiedzialności |
| --- | --- |
| `core/` | Rdzeń aplikacji, w tym routing oraz obsługa połączenia z bazą danych z wykorzystaniem PDO. |
| `controllers/` | Obsługa żądań HTTP, walidacja wejścia oraz przekazywanie danych pomiędzy modelami i widokami. |
| `models/` | Operacje na danych, zapytania do bazy oraz obsługa encji domenowych, m.in. użytkowników i transakcji. |
| `services/` | Logika pomocnicza i warstwa usług, np. autoryzacja, obsługa sesji oraz funkcje wspierające procesy biznesowe. |
| `db/` | Schemat bazy danych oraz migracje służące do zarządzania jej strukturą. |
| Główny katalog projektu | Pliki konfiguracyjne i startowe niezbędne do działania aplikacji. |

## Pozostałe pliki backendowe

- `index.php` - punkt wejścia aplikacji, odpowiedzialny za inicjalizację sesji oraz przekazanie żądania do routera.
- `.htaccess` - konfiguracja przekierowująca ruch do `index.php` i wspierająca routing oparty na adresach URL.
- `.env` - dane środowiskowe, w szczególności parametry połączenia z bazą danych.
- `config.php` - podstawowa konfiguracja aplikacji, m.in. definicja `BASE_URL`.
- `helpers.php` - zestaw globalnych funkcji pomocniczych wykorzystywanych w projekcie.

## Odpowiedzialność Architektoniczna

**Główna odpowiedzialność:** Tobiasz Szerszeń

Zakres ten obejmował nadzór nad spójnością struktury aplikacji, organizacją warstw systemu, podejściem do rozwoju bazy danych oraz utrzymaniem czytelnego podziału odpowiedzialności pomiędzy modułami.

## Założenia Architektoniczne

W projekcie zastosowano architekturę MVC oraz uporządkowany model pracy oparty na systemie kontroli wersji Git. Struktura aplikacji została przygotowana w sposób umożliwiający czytelne oddzielenie warstwy prezentacji, logiki biznesowej oraz dostępu do danych.

Kluczowe decyzje architektoniczne:
- zastosowanie wzorca MVC (Model-View-Controller) w celu rozdzielenia logiki biznesowej, operacji na danych oraz warstwy prezentacji,
- wykorzystanie pojedynczej współdzielonej instancji połączenia PDO zgodnie z podejściem typu Singleton,
- wprowadzenie migracyjnego sposobu zarządzania strukturą bazy danych, co ułatwia synchronizację zmian pomiędzy środowiskami,
- zastosowanie routingu opartego na URI, umożliwiającego budowanie czytelnych i przewidywalnych ścieżek aplikacji,
- organizacja pracy z wykorzystaniem systemu Git, wspierająca kontrolę wersji, współpracę zespołową oraz bezpieczne rozwijanie projektu.

## Współpraca Zespołowa

Realizacja projektu wymagała współpracy pomiędzy osobami odpowiedzialnymi za frontend, backend oraz architekturę. W praktyce oznaczało to:
- bieżącą integrację zmian pomiędzy warstwą interfejsu a logiką aplikacyjną,
- wspólne uzgadnianie decyzji dotyczących struktury danych i sposobu komunikacji modułów,
- utrzymywanie spójności projektu pod względem organizacji katalogów, nazewnictwa i odpowiedzialności warstw,
- koordynację zmian w bazie danych oraz ich wpływu na działanie aplikacji.

Dokument przedstawia główne zakresy odpowiedzialności i ma charakter porządkujący. Nie oznacza on całkowitej izolacji prac, ponieważ rozwój projektu przebiegał iteracyjnie i wymagał współdziałania zespołu na wielu etapach implementacji.

*Dokument sporządzony na potrzeby dokumentacji projektu Spendly.*
