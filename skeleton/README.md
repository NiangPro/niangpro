# Mon application NiangPro

Créée avec :

```bash
composer create-project niangpro/niangpro mon-app
```

## Démarrer

```bash
./bin/niang migrate
./bin/niang serve        # http://127.0.0.1:8000
./vendor/bin/phpunit     # les tests de l'application
```

Votre code est dans `app/`, `routes/`, `resources/views/` et `config/`. Le framework est dans
`vendor/niangpro/framework` : ne le modifiez pas, il se met à jour avec Composer :

```bash
composer update niangpro/framework
```

Framework : https://github.com/NiangPro/niangpro · Documentation (FR + EN) : https://github.com/NiangPro/niangpro-docs
