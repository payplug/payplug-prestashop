# Migration des hooks Git: Husky -> CaptainHook

## Pourquoi ne plus utiliser Husky dans ce module

Ce module est principalement un projet PHP/Composer. Historiquement, Husky est tres bien pour les projets Node, mais dans ce repository:

- les controles utilises dans les hooks sont PHP (`php-cs-fixer`),
- les dependances existent deja dans `vendor/bin`,
- l'equipe travaille deja avec Composer comme point d'entree.

Conserver Husky pour des checks PHP ajoute une couche Node supplementaire (configuration, maintenance, versions) sans benefice direct.

## Avantages de CaptainHook

CaptainHook est oriente Git hooks pour l'ecosysteme PHP et s'integre nativement avec Composer:

- **Alignement stack**: hooks et outils executes via `php`/`composer`.
- **Configuration versionnee**: `captainhook.json` dans le repo.
- **Onboarding simple**: installation des hooks via `vendor/bin/captainhook install`.
- **Lisibilite**: un fichier de config explicite par hook/action.
- **Evolutif**: ajout facile de `phpstan`, `phpunit`, `phpcs`, etc.

## Configuration appliquee dans ce repository

### 1) Dependance Composer

`captainhook/captainhook` est ajoute en `require-dev` dans `composer.json`.

### 2) Fichier de configuration

Le fichier `captainhook.json` definit:

- `pre-commit` -> execute `composer cs:fix`
- `post-checkout` -> execute `composer cs:cache:reset`

### 3) Scripts Composer

Dans `composer.json`, les scripts suivants sont definis:

- `cs:fix`: lance `php-cs-fixer`
- `cs:cache:reset`: reset le fichier `.php_cs.cache`
- `hooks:install`: installe/force les hooks Git CaptainHook

## Comment l'utiliser

### Installation locale (premiere fois)

```bash
composer install
```

CaptainHook s'installe automatiquement en tant que dependance dev. Les hooks Git sont initialises au premier `composer install`. Si vous avez un repo existant et que les hooks ne sont pas actifs, relancez:

```bash
php vendor/bin/captainhook install -f
```

Ou plus simplement via le script Composer:

```bash
composer hooks:install
```

### Execution manuelle (optionnel)

```bash
composer cs:fix         # Lance php-cs-fixer
composer cs:cache:reset # Reset le cache
```

### Cycle quotidien

- au `git commit`, le hook `pre-commit` lance automatiquement `composer cs:fix`
  - le code est formate avant le commit,
- au `git checkout`, le hook `post-checkout` lance automatiquement `composer cs:cache:reset`
  - le cache de php-cs-fixer est reset apres un changement de branch.

### Verification que les hooks sont actifs

Verifie que les fichiers existent dans `.git/hooks`:

```bash
ls -la .git/hooks/ | grep -E "pre-commit|post-checkout"
```

Tu devrais voir:
```
pre-commit
post-checkout
```

## Migration depuis Husky

- le fichier `.huskyrc.backup` est conserve pour historique,
- aucune nouvelle config Husky ne doit etre ajoutee,
- utiliser uniquement `captainhook.json` + scripts Composer pour les hooks Git.

## Architecture CaptainHook dans ce repo

```
payplug/
├── captainhook.json          # Config des hooks Git (versionnee)
├── composer.json             # Scripts Composer + dependances
├── .git/hooks/
│   ├── pre-commit           # Genere par CaptainHook
│   └── post-checkout        # Genere par CaptainHook
└── .git/config              # Git config standard (non touche)
```

Les hooks sont generes et geres par CaptainHook lors du `composer install`. Ils ne doivent pas etre edites manuellement.

