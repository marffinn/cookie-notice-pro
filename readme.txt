=== Cookie Notice Pro ===
Contributors: custom
Tags: cookies, gdpr, privacy, consent
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Responsywny baner zgód cookies z wyborem kategorii, panelem administracyjnym i prostym mechanizmem uruchamiania oznaczonych skryptów po zgodzie.

== Instalacja ==
1. W panelu WordPress przejdź do Wtyczki > Dodaj nową > Wyślij wtyczkę na serwer.
2. Wybierz plik ZIP i kliknij Zainstaluj, a następnie Włącz.
3. Otwórz Ustawienia > Cookie Notice Pro i skonfiguruj baner.
4. Podaj adres polityki prywatności.

== Blokowanie skryptów ==
Skrypty wymagające zgody oznacz jako type="text/plain" oraz data-cookie-category="preferences", "analytics" lub "marketing". Przykład:

<script type="text/plain" data-cookie-category="analytics">
  // kod analityczny
</script>

Skrypty niezbędne mogą być ładowane normalnie. Wtyczka nie wykrywa automatycznie wszystkich plików cookies ani skryptów zewnętrznych — integracje należy oznaczyć ręcznie. Niektóre narzędzia wymagają dodatkowej konfiguracji.

== Shortcode ==
[cookie_settings] — wyświetla przycisk ponownego otwarcia ustawień zgód.

== Ważne ==
Wtyczka jest technicznym narzędziem do zbierania preferencji. Nie stanowi porady prawnej ani automatycznej gwarancji zgodności z RODO/ePrivacy. Administrator strony odpowiada za prawidłowe sklasyfikowanie cookies, blokowanie wszystkich niekoniecznych technologii przed zgodą, treść polityki prywatności i dokumentowanie wymaganych informacji.
