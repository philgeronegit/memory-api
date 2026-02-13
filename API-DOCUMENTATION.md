# Documentation API Memory

Documentation complète de l'API RESTful Memory avec Swagger/OpenAPI 3.0.

## 📖 Accès à la documentation

### Interface Swagger UI

Ouvrez votre navigateur et accédez à :

```
http://localhost:8000/swagger-ui.html
```

ou si vous utilisez WAMP :

```
http://localhost/memory/swagger-ui.html
```

### Fichier OpenAPI brut

Le fichier de spécification OpenAPI est disponible à :

```
http://localhost:8000/openapi.yaml
```

## 🔐 Authentification

L'API utilise l'authentification JWT (JSON Web Token).

### Obtenir un token

**Endpoint:** `POST /memory/login`

**Body:**
```json
{
  "username": "votre-username",
  "password": "votre-mot-de-passe"
}
```

**Réponse:**
```json
{
  "id_user": 1,
  "username": "john.doe",
  "email": "john.doe@example.com",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_at": "2026-01-19T12:00:00Z"
}
```

### Utiliser le token

Incluez le token dans l'en-tête `Authorization` de toutes vos requêtes :

```
Authorization: Bearer {votre-token}
```

**Exemple avec curl:**
```bash
curl -X GET "http://localhost:8000/memory/user" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

**Exemple avec JavaScript (fetch):**
```javascript
fetch('http://localhost:8000/memory/user', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## 📚 Ressources principales

### Users (`/user`)
- Gestion des utilisateurs
- Profils utilisateurs
- Associations avec projets, tâches, notes

### Notes (`/note`)
- Création et gestion de notes
- Support du markdown et code
- Partage entre utilisateurs
- Tags et scores

### Projects (`/project`)
- Organisation des notes par projets
- Gestion collaborative
- Association d'utilisateurs

### Tasks (`/task`)
- Gestion de tâches
- Système Kanban (avec réorganisation)
- Statuts personnalisables

### Comments (`/comment`)
- Commentaires sur les notes
- Système de discussion

### Tags (`/tag`)
- Classification des notes
- Tags colorés

### Uploads (`/upload`)
- Upload de fichiers
- Téléchargement sécurisé
- Visualisation publique (`/view/{id}/{filename}`)

## 🚀 Exemples d'utilisation

### Créer une note

```bash
curl -X POST "http://localhost:8000/memory/note" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Ma note de code",
    "content": "```javascript\nconst hello = \"world\";\n```",
    "type": "code",
    "id_user": 1,
    "id_project": 3,
    "is_public": false,
    "id_programming_language": 2
  }'
```

### Récupérer les notes d'un utilisateur

```bash
curl -X GET "http://localhost:8000/memory/user/1/note" \
  -H "Authorization: Bearer {token}"
```

### Partager une note

```bash
curl -X POST "http://localhost:8000/memory/note/5/share" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "id_item": 5,
    "id_user": 7
  }'
```

### Créer un projet

```bash
curl -X POST "http://localhost:8000/memory/project" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "API Memory",
    "description": "API RESTful pour notes",
    "id_user": 1
  }'
```

### Ajouter un commentaire

```bash
curl -X POST "http://localhost:8000/memory/comment" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Excellente note !",
    "id_user": 1,
    "id_item": 5
  }'
```

### Réorganiser les tâches (Kanban)

```bash
curl -X PUT "http://localhost:8000/memory/task/order" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "tasks": [
      {"id_task": 1, "display_order": 1},
      {"id_task": 2, "display_order": 2},
      {"id_task": 3, "display_order": 3}
    ]
  }'
```

## 🔒 Sécurité

- ✅ Authentification JWT pour toutes les routes (sauf `/login` et `/view`)
- ✅ Tokens avec expiration
- ✅ CORS configuré pour le frontend
- ✅ Validation des entrées côté serveur
- ✅ Mots de passe hashés avec bcrypt
- ✅ Prepared statements pour les requêtes SQL
- ✅ Logs de sécurité (tentatives de connexion, etc.)

## 📊 Codes de réponse HTTP

| Code | Description |
|------|-------------|
| 200  | Succès |
| 201  | Ressource créée |
| 400  | Requête invalide |
| 401  | Non autorisé (token manquant/invalide) |
| 403  | Accès interdit |
| 404  | Ressource non trouvée |
| 422  | Entité non traitable |
| 500  | Erreur serveur |

## 🛠️ Structure des endpoints

### Endpoints simples CRUD
```
GET    /memory/{resource}         - Liste toutes les ressources
POST   /memory/{resource}         - Crée une nouvelle ressource
GET    /memory/{resource}/{id}    - Récupère une ressource
PUT    /memory/{resource}/{id}    - Met à jour une ressource
DELETE /memory/{resource}/{id}    - Supprime une ressource
```

### Endpoints relationnels
```
GET    /memory/user/{id}/note           - Notes d'un utilisateur
GET    /memory/user/{id}/project        - Projets d'un utilisateur
GET    /memory/user/{id}/task           - Tâches d'un utilisateur
GET    /memory/note/{id}/comment        - Commentaires d'une note
GET    /memory/note/{id}/tag            - Tags d'une note
POST   /memory/note/{id}/share          - Partager une note
POST   /memory/note/{id}/score          - Scorer une note
```

## 📝 Format des données

Toutes les requêtes et réponses utilisent le format JSON.

**En-têtes requis:**
```
Content-Type: application/json
Authorization: Bearer {token}
```

## 🧪 Tester l'API

### Avec Swagger UI

1. Ouvrez `http://localhost:8000/swagger-ui.html`
2. Cliquez sur "Authorize"
3. Entrez votre token JWT : `Bearer {token}`
4. Testez les endpoints directement depuis l'interface

### Avec Postman

1. Importez le fichier `openapi.yaml` dans Postman
2. Configurez l'authentification Bearer Token
3. Testez les endpoints

### Avec curl

Voir les exemples ci-dessus.

## 📦 Export de la documentation

### Formats disponibles

- **YAML:** `openapi.yaml` - Spécification OpenAPI 3.0
- **HTML:** `swagger-ui.html` - Interface interactive

### Générer d'autres formats

Vous pouvez utiliser des outils comme [swagger-codegen](https://github.com/swagger-api/swagger-codegen) pour générer :
- Clients API (JavaScript, Python, PHP, etc.)
- Documentation PDF
- Documentation Markdown
- Serveurs mock

## 🔄 Mise à jour de la documentation

Le fichier `openapi.yaml` doit être mis à jour lorsque :
- De nouveaux endpoints sont ajoutés
- Des schémas de données changent
- Des règles de validation évoluent
- De nouveaux codes de réponse sont introduits

## 📞 Support

Pour toute question sur l'API :
- Consultez la documentation Swagger UI
- Vérifiez les logs de sécurité dans `/logs`
- Contactez l'équipe de développement

## 🌐 URLs des serveurs

| Environnement | URL |
|--------------|-----|
| Développement local | `http://localhost:8000/memory` |
| WAMP local | `http://localhost/memory` |
| Frontend | `http://localhost:3000` |

---

**Version de l'API:** 1.0.0
**Dernière mise à jour:** Janvier 2026
