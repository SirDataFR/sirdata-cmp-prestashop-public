# Changelog

## 1.0.3 - 2026-11-18

- `install()` n'envoie plus le domaine de la boutique à un serveur de licence tiers : cet appel ne vérifiait pas le certificat, et sa réponse était ignorée.
- Retrait des appels à `Helper\ScanDir` et `Helper\UploadHelper`, deux classes absentes de l'archive depuis la 1.0.1, et de `views/js/helper-low.js`, que rien ne chargeait.
- Sirdata est l'éditeur du module : auteur affiché, panneau d'information, en-têtes des fichiers.
- Même numéro de version dans `lowsirdata.php` et `config.xml`.

## 1.0.2 et versions antérieures

Versions publiées avant ce dépôt, sans historique. La 1.0.0 contient `classes/Helper/UploadHelper.php`, que PHP 8 refuse : sous PHP 8, elle bloque le gestionnaire de modules de PrestaShop. Pour la remplacer, voir la section « Mise à jour » de `Readme.md`.
