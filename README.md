# Checkout After Product - Plugin WordPress

Un plugin WordPress qui affiche un formulaire de paiement simplifié après la description courte du produit, s'intégrant parfaitement au flux de paiement par défaut de WooCommerce pour une expérience d'achat optimisée.

## Fonctionnalités

- **Affichage Automatique** : Le formulaire de paiement apparaît automatiquement après la description courte sur les pages de produits individuels
- **Formulaire Simplifié** : Formulaire propre et minimal avec seulement les champs essentiels (nom complet, adresse de facturation, ville, gouvernorat)
- **Éléments Masqués** : Masque automatiquement l'image du produit, le prix et les éléments d'ajout au panier/quantité de WooCommerce
- **Intégration WooCommerce** : Redirige vers le paiement par défaut de WooCommerce avec des données pré-remplies
- **Copie d'Expédition** : Copie automatiquement les informations de facturation vers l'expédition (masquée)
- **Validation de Formulaire** : Validation en temps réel côté client et serveur
- **Design Responsive** : Formulaire de paiement adaptatif avec mise en page en grille responsive
- **Support Shortcode** : Utilisez le shortcode `[checkout_after_product]` n'importe où
- **Traitement AJAX** : Soumission de formulaire fluide sans rechargement de page
- **Sécurité** : Vérification nonce et assainissement des données
- **Internationalisation** : Prêt pour la traduction avec domaine de texte

## Prérequis

- WordPress 5.0 ou supérieur
- WooCommerce 4.0 ou supérieur
- PHP 7.4 ou supérieur

## Installation

### Méthode 1 : Installation Manuelle

1. Téléchargez les fichiers du plugin
2. Uploadez le dossier `checkout-after-product` dans votre répertoire `/wp-content/plugins/`
3. Activez le plugin via le menu 'Plugins' dans WordPress
4. Le formulaire de paiement apparaîtra automatiquement sur les pages de produits

### Méthode 2 : Administration WordPress

1. Allez dans Plugins > Ajouter
2. Cliquez sur "Téléverser un plugin"
3. Choisissez le fichier zip du plugin
4. Cliquez sur "Installer maintenant" puis "Activer"

## Utilisation

### Affichage Automatique

Une fois activé, le formulaire de paiement apparaîtra automatiquement après le contenu du produit sur toutes les pages de produits individuels.

### Utilisation du Shortcode

Vous pouvez également afficher le formulaire de paiement n'importe où en utilisant le shortcode :

```
[checkout_after_product]
```

Ou spécifiez un produit particulier :

```
[checkout_after_product product_id="123"]
```

### Personnalisation

#### Style

Le plugin inclut du CSS qui peut être personnalisé. Vous pouvez remplacer les styles en ajoutant du CSS personnalisé à votre thème :

```css
/* Styles personnalisés pour le formulaire de paiement */
.cap-checkout-section {
    background: #votre-couleur;
}

.cap-submit-btn {
    background: #couleur-de-votre-bouton;
}
```

#### Hooks et Filtres

Le plugin fournit plusieurs hooks pour la personnalisation :

```php
// Modifier les données du formulaire de paiement avant le traitement
add_filter('cap_checkout_data', function($data) {
    // Modifiez $data selon vos besoins
    return $data;
});

// Validation personnalisée
add_filter('cap_validate_checkout', function($errors, $data) {
    // Ajoutez votre logique de validation personnalisée
    return $errors;
}, 10, 2);
```

## Champs du Formulaire

Le formulaire de paiement inclut les champs simplifiés suivants :

### Informations Client
- **Nom Complet** (requis) - Nom complet du client (prénom et nom)
- **Adresse de Facturation** (requise) - Adresse de facturation complète
- **Ville de Facturation** (requise) - Ville pour l'adresse de facturation (chargée dynamiquement selon la sélection du gouvernorat)
- **Gouvernorat** (optionnel) - Sélection parmi les 24 gouvernorats tunisiens

### Fonctionnalités Masquées
- **Informations d'Expédition** : Copiées automatiquement depuis les informations de facturation et masquées
- **Détails du Produit** : L'image et le prix du produit sont masqués pour une interface plus propre
- **Éléments WooCommerce** : Les sélecteurs d'ajout au panier et de quantité sont automatiquement masqués
- **Pays** : Automatiquement défini sur Tunisie (TN)

## Comment Ça Fonctionne

1. **Le client remplit le formulaire simplifié** avec son nom complet et ses informations d'adresse de facturation
2. **Chargement dynamique des villes** - Les villes sont automatiquement chargées selon le gouvernorat tunisien sélectionné
3. **Validation du formulaire** s'assure que tous les champs requis sont remplis et correctement formatés
4. **Le produit est ajouté au panier** automatiquement
5. **Les données client sont stockées** dans la session WooCommerce pour la facturation et l'expédition (expédition copiée depuis la facturation)
6. **Redirection vers le paiement WooCommerce** avec les informations de facturation et d'expédition pré-remplies
7. **Le flux de paiement standard WooCommerce** gère le traitement du paiement

## Traitement du Paiement

Le plugin s'intègre parfaitement aux passerelles de paiement par défaut de WooCommerce :

- **Aucun traitement de paiement personnalisé** - Utilise les méthodes de paiement intégrées de WooCommerce
- **Gestion automatique du panier** - Le produit est ajouté au panier et le client est redirigé vers le paiement
- **Formulaire de paiement pré-rempli** - Le nom et l'adresse du client sont automatiquement remplis dans le paiement WooCommerce
- **Flux WooCommerce standard** - Tout le traitement du paiement suit le processus de paiement standard de WooCommerce

## Fonctionnalités de Sécurité

- **Vérification Nonce** : Toutes les requêtes AJAX sont protégées avec des nonces WordPress
- **Assainissement des Données** : Toutes les données du formulaire sont correctement assainies avant le traitement
- **Validation des Entrées** : Validation complète côté client et serveur
- **Protection contre l'Injection SQL** : Utilise les déclarations préparées WordPress
- **Protection XSS** : La sortie est correctement échappée

## Structure des Fichiers

```
checkout-after-product/
├── checkout-after-product.php    # Fichier principal du plugin
├── assets/
│   ├── css/
│   │   └── checkout.css         # Feuille de style
│   └── js/
│       └── checkout.js          # Fonctionnalité JavaScript
├── languages/                   # Fichiers de traduction (si applicable)
└── README.md                   # Ce fichier
```

## Dépannage

### Problèmes Courants

1. **Le formulaire n'apparaît pas** : Assurez-vous que WooCommerce est actif et que vous êtes sur une page de produit
2. **Erreurs AJAX** : Vérifiez la console du navigateur pour les erreurs JavaScript
3. **Problèmes de style** : Vérifiez que le CSS se charge correctement
4. **Problèmes de redirection de paiement** : Assurez-vous que la page de paiement WooCommerce est correctement configurée
5. **Le panier ne se met pas à jour** : Vérifiez si la fonctionnalité de panier WooCommerce fonctionne correctement

### Mode Debug

Activez le mode debug WordPress pour voir les messages d'erreur détaillés :

```php
// Ajoutez à wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Exemples de Personnalisation

### Ajouter des Champs Personnalisés

```php
// Ajouter un champ personnalisé au formulaire de paiement
add_action('cap_after_shipping_fields', function() {
    echo '<div class="cap-form-field">';
    echo '<label for="custom_field">Champ Personnalisé</label>';
    echo '<input type="text" name="custom_field" id="custom_field">';
    echo '</div>';
});
```

### Modifier les Méthodes de Paiement

```php
// Ajouter une méthode de paiement personnalisée
add_filter('cap_payment_methods', function($methods) {
    $methods['custom_method'] = 'Paiement Personnalisé';
    return $methods;
});
```

### Validation Personnalisée

```php
// Ajouter une validation personnalisée
add_filter('cap_validate_checkout', function($errors, $data) {
    if (empty($data['custom_field'])) {
        $errors[] = 'Le champ personnalisé est requis.';
    }
    return $errors;
}, 10, 2);
```

## Support

Pour le support et les demandes de fonctionnalités, veuillez contacter le développeur du plugin ou créer un problème dans le dépôt du plugin.

## Journal des Modifications

### Version 1.0.0
- Version initiale
- Fonctionnalité de paiement de base
- Méthodes de paiement multiples
- Validation de formulaire
- Design responsive

## Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## Crédits

Développé pour l'intégration WordPress et WooCommerce.

---

**Note** : Ce plugin est conçu pour fonctionner avec WooCommerce. Assurez-vous que WooCommerce est correctement installé et configuré avant d'utiliser ce plugin. #   c h e c k o u t - a f t e r - p r o d u c t  
 