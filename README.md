# tdevgreen

Dev site pro tdevgreen.fr

Site vitrine MVP pour **TDEVGREEN**, installation d'infrastructures de recharge
pour véhicules électriques (IRVE) en Île-de-France.

## Stack

Site statique **HTML5 / CSS3 / JavaScript vanilla**, sans framework ni étape de
build — voir le document d'analyse produit en amont pour le détail des choix
et des points restant à valider (offre OVH exacte, contenu définitif, charte
graphique).

## Structure

```
index.html              page d'accueil (one-page, ancres #services, #apropos, #contact...)
assets/css/styles.css   styles
assets/js/main.js       menu mobile + soumission du formulaire de contact
assets/contact.php      traitement serveur du formulaire (nécessite PHP + mail())
assets/img/             favicon et icônes
robots.txt, sitemap.xml SEO de base
```

## Développement local

Aucune dépendance : ouvrir `index.html` dans un navigateur, ou servir le
dossier avec un serveur statique simple, par ex. :

```bash
python3 -m http.server 8080
```

Le formulaire de contact (`assets/contact.php`) nécessite un serveur PHP pour
être testé en local :

```bash
php -S localhost:8080
```

## Déploiement OVH

À déployer sur un hébergement OVH supportant PHP (mutualisé Perso/Pro/
Performance) : transférer l'ensemble du dossier par FTP/SFTP ou Git dans le
répertoire racine du domaine (`www/`).

## À faire avant mise en production

- Remplacer les visuels placeholder (dégradés) par les vraies photos.
- Confirmer les coordonnées (adresse, email, téléphone, mentions légales).
- Compléter les pages Mentions légales / Politique de confidentialité.
- Ajuster la palette de couleurs (HEX) selon la charte graphique officielle.
