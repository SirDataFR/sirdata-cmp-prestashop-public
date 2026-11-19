# Sirdata CMP pour PrestaShop

Module PrestaShop qui charge la [CMP Sirdata](https://www.sirdata.com) (IAB TCF v2, Google Consent Mode v2) dans le `<head>` de toutes les pages de la boutique.

Son nom technique reste `lowsirdata` : les clés de configuration (`LOWSIRDATA_CMP_ID_COMPTE`, `LOWSIRDATA_CMP_ID_SITE`) en dérivent, et le changer ferait perdre leurs réglages aux boutiques déjà installées.

## Compatibilité

PrestaShop 1.7 et versions suivantes, en multiboutique comme en boutique unique.

La 1.0.3 a été testée de bout en bout sur PrestaShop 8.2.9 sous PHP 8.0 et 8.1, et sur PrestaShop 1.7.8.11 sous PHP 7.4.

## Télécharger

Dernière version : https://github.com/SirDataFR/sirdata-cmp-prestashop-public/releases/latest/download/lowsirdata.zip

Les versions précédentes et leurs notes : https://github.com/SirDataFR/sirdata-cmp-prestashop-public/releases

## Installation

1. Dans le back-office, ouvrir *Modules → Gestionnaire de modules → Installer un module* et choisir l'archive `lowsirdata.zip`. En multiboutique, installer depuis le contexte « Toutes les boutiques » : une installation faite sur une seule boutique n'active le module que sur celle-ci.
2. Cliquer sur *Configurer* et renseigner « Sirdata Account ID » et « Site ID ». Les deux figurent dans l'URL du tag donnée par le portail CMP : `https://choices.consentframework.com/js/pa/<Sirdata Account ID>/c/<Site ID>/cmp`.

En multiboutique, choisir la boutique dans le sélecteur du back-office avant de configurer : les identifiants sont enregistrés pour cette boutique. Ceux saisis dans le contexte « Toutes les boutiques » servent aux boutiques qui n'ont pas les leurs.

## Mise à jour

Envoyer la nouvelle archive par *Installer un module*. PrestaShop reconnaît le module déjà installé et le met à jour : les identifiants de chaque boutique restent en place, même si le message affiché annonce une installation.

Depuis la 1.0.0, retirer d'abord le dossier `modules/lowsirdata` (par FTP par exemple) : sous PHP 8, son fichier `classes/Helper/UploadHelper.php` bloque le gestionnaire de modules. Ne pas copier la nouvelle version par-dessus l'ancienne, car PrestaShop ne supprime pas les fichiers absents de l'archive.

## Fonctionnement

Le module s'accroche à `displayHeader` et ajoute ces deux scripts dans le `<head>` (`views/templates/hook/header.tpl`) :

```html
<script data-keepinline="true" type="text/javascript" src="https://cache.consentframework.com/js/pa/ACCOUNT_ID/c/SITE_ID/stub" referrerpolicy="unsafe-url" charset="utf-8"></script>
<script data-keepinline="true" type="text/javascript" src="https://choices.consentframework.com/js/pa/ACCOUNT_ID/c/SITE_ID/cmp" referrerpolicy="unsafe-url" charset="utf-8" async></script>
```

L'attribut `data-keepinline` empêche PrestaShop de déplacer ou de regrouper ces scripts (`classes/Media.php`). Rien n'est ajouté tant que « Sirdata Account ID » est vide.

La désinstallation retire les scripts des pages et garde les identifiants en base.

## Signaler un problème

Ouvrir une issue sur https://github.com/SirDataFR/sirdata-cmp-prestashop-public/issues. Les issues sont publiques : une faille de sécurité se signale en privé, par « Report a vulnerability » dans l'onglet « Security » du même dépôt.

## Licence

Academic Free License 3.0 : voir [LICENSE.txt](LICENSE.txt).
