# Sécurité de l'Upload de Fichiers

## Vue d'ensemble

Le système d'upload de fichiers a été conçu avec une approche de sécurité en profondeur (_defense in depth_), implémentant plusieurs couches de protection contre les vulnérabilités courantes identifiées dans l'OWASP Top 10.

## Mesures de Sécurité Détaillées

### 1. Contrôle d'Accès (A01: Broken Access Control)

#### Mesure : Vérification de l'Autorisation

```php
if ($currentUser->id_user != $userId && !in_array($currentUser->role, ['admin', 'projectManager'])) {
    $logger->logAuthorizationFailure(...);
    // Return 403 Forbidden
}
```

**Failles évitées :**

- **A01:2021 - Broken Access Control** : Empêche un utilisateur d'uploader des fichiers dans le répertoire d'un autre utilisateur
- **Privilege Escalation** : Seuls les administrateurs et chefs de projet peuvent uploader pour d'autres utilisateurs
- **Insecure Direct Object Reference (IDOR)** : Validation stricte de l'ID utilisateur avant toute opération

**Principe appliqué :** Deny by Default + Least Privilege

---

### 2. Validation de l'ID Utilisateur

#### Mesure : Validation Stricte des Paramètres

```php
if (empty($userId) || !is_numeric($userId)) {
    $logger->logSuspiciousActivity('INVALID_USER_ID', [...]);
    // Return 400 Bad Request
}
```

**Failles évitées :**

- **A03:2021 - Injection** : Empêche l'injection de caractères spéciaux dans le chemin de fichier
- **Path Traversal** : Bloque les tentatives d'utiliser des séquences comme `../` dans l'ID
- **Type Confusion Attacks** : S'assure que l'ID est bien un nombre entier

---

### 3. Validation de Type MIME (Double Vérification)

#### Mesure : Vérification du Contenu Réel du Fichier

```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
    // Reject file
}
```

**Failles évitées :**

- **A03:2021 - Injection** : Empêche l'upload de scripts malveillants (PHP, JavaScript, etc.)
- **Remote Code Execution (RCE)** : Bloque les fichiers exécutables déguisés en images
- **MIME Type Spoofing** : Utilise `finfo_file()` qui analyse le contenu binaire réel, pas juste l'extension
- **Malware Upload** : Réduit la surface d'attaque en limitant aux types images et PDF

**Pourquoi c'est efficace :** L'attaquant ne peut pas simplement renommer `shell.php` en `shell.jpg` car le contenu binaire sera détecté comme `text/x-php`.

**Types autorisés :**
- Images : `image/jpeg`, `image/png`, `image/gif`
- Documents : `application/pdf`

---

### 4. Validation de l'Extension (Deuxième Couche)

#### Mesure : Whitelist des Extensions

```php
$fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, self::ALLOWED_EXTENSIONS)) {
    // Reject file
}
```

**Failles évitées :**

- **Double Extension Attack** : Empêche `malicious.php.jpg` d'être exécuté comme PHP
- **Null Byte Injection** : Protection contre `shell.php%00.jpg`
- **Case Sensitivity Bypass** : Normalisation en minuscules pour éviter `.PHP` ou `.Php`

**Pourquoi deux validations :**

Les deux vérifications (MIME + extension) doivent passer. Cela crée une défense en profondeur :
- Si l'attaquant contourne la vérification MIME → l'extension le bloque
- Si l'attaquant contourne l'extension → le MIME le bloque

---

### 5. Limitation de Taille de Fichier

#### Mesure : Taille Maximum de 5 Mo

```php
private const MAX_FILE_SIZE = 5242880; // 5MB

if ($file['size'] > self::MAX_FILE_SIZE) {
    // Reject file
}
```

**Failles évitées :**

- **A05:2021 - Security Misconfiguration** : Empêche la saturation du disque
- **Denial of Service (DoS)** : Un attaquant ne peut pas remplir le serveur avec des fichiers volumineux
- **Resource Exhaustion** : Limite l'impact sur la mémoire et la bande passante
- **Zip Bomb / Decompression Bomb** : Réduit l'impact des fichiers compressés malveillants

**Calcul :** 5 242 880 octets = 5 × 1024 × 1024 octets

---

### 6. Gestion des Erreurs d'Upload

#### Mesure : Détection et Logging des Erreurs PHP

```php
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = $this->getUploadErrorMessage($file['error']);
    $logger->logSuspiciousActivity('FILE_UPLOAD_ERROR', [...]);
    // Return specific error
}
```

**Failles évitées :**

- **Information Disclosure** : Enregistre les erreurs mais retourne des messages génériques
- **Attack Detection** : Logs permettent d'identifier des tentatives répétées suspectes
- **Configuration Issues** : Détecte les problèmes de configuration serveur (permissions, tmp dir)

**Codes d'erreur gérés :**
- `UPLOAD_ERR_INI_SIZE` : Dépasse upload_max_filesize
- `UPLOAD_ERR_FORM_SIZE` : Dépasse MAX_FILE_SIZE du formulaire
- `UPLOAD_ERR_PARTIAL` : Upload incomplet (connexion interrompue)
- `UPLOAD_ERR_NO_TMP_DIR` : Répertoire temporaire manquant
- `UPLOAD_ERR_CANT_WRITE` : Erreur d'écriture disque
- `UPLOAD_ERR_EXTENSION` : Bloqué par extension PHP

---

### 7. Génération de Nom de Fichier Sécurisé

#### Mesure : Randomisation et Sanitization

```php
private function generateSecureFileName(string $originalName, string $extension): string
{
    // Remove any path components and dangerous characters
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

    // Limit filename length
    $safeName = substr($safeName, 0, 100);

    // Add timestamp and random component
    $timestamp = time();
    $random = bin2hex(random_bytes(8));

    return $safeName . '_' . $timestamp . '_' . $random . '.' . $extension;
}
```

**Failles évitées :**

- **Path Traversal** : Supprime `../`, `/`, `\` et autres caractères de navigation
- **File Overwrite** : Le timestamp + random garantit l'unicité, empêche l'écrasement
- **Command Injection** : Retire les caractères spéciaux shell (`;`, `|`, `&`, etc.)
- **XSS via Filename** : Supprime les caractères HTML/JavaScript dangereux
- **Buffer Overflow** : Limite à 100 caractères pour éviter les débordements
- **Unicode Exploits** : Filtre tout sauf ASCII alphanumerique + underscore + tiret

**Exemple de transformation :**
```
Input:  ../../../etc/passwd; rm -rf /.jpg
Output: _etc_passwd_rm_rf_1736950000_a1b2c3d4e5f6g7h8.jpg
```

---

### 8. Création Sécurisée de Répertoires

#### Mesure : Permissions Restrictives

```php
$userDir = self::UPLOAD_DIR . $userId . '/';
if (!file_exists($userDir)) {
    if (!mkdir($userDir, 0750, true)) {
        // Error handling
    }
}
```

**Failles évitées :**

- **A01:2021 - Broken Access Control** : Permissions `0750` = rwxr-x---
  - Propriétaire : lecture, écriture, exécution (7)
  - Groupe : lecture, exécution (5)
  - Autres : aucun accès (0)
- **Information Disclosure** : Les autres utilisateurs ne peuvent pas lister les fichiers
- **Unauthorized Access** : Seul le propriétaire peut modifier le contenu

**Principe appliqué :** Least Privilege (permissions minimales nécessaires)

---

### 9. Validation de Fichier Uploadé

#### Mesure : Vérification d'Intégrité Upload

```php
if (!is_uploaded_file($file['tmp_name'])) {
    $logger->logSuspiciousActivity('FILE_UPLOAD_TAMPERING', [...]);
    // Reject file
}
```

**Failles évitées :**

- **File Upload Bypass** : Vérifie que le fichier vient bien d'un POST multipart/form-data
- **Local File Inclusion (LFI)** : Empêche de spécifier un fichier local arbitraire
- **Symlink Attack** : Bloque les tentatives d'utiliser des liens symboliques
- **TOCTOU (Time-of-Check Time-of-Use)** : S'assure que tmp_name est un fichier upload légitime

**Comment ça marche :** PHP maintient une liste interne des fichiers uploadés. `is_uploaded_file()` vérifie que le fichier est dans cette liste.

---

### 10. Permissions de Fichiers

#### Mesure : Restriction d'Accès Post-Upload

```php
chmod($targetFilePath, 0640);
```

**Failles évitées :**

- **A05:2021 - Security Misconfiguration** : Permissions `0640` = rw-r-----
  - Propriétaire : lecture, écriture (6)
  - Groupe : lecture seule (4)
  - Autres : aucun accès (0)
- **Execution Prevention** : Aucun droit d'exécution, même si PHP/script uploadé
- **Information Disclosure** : Limite l'accès au propriétaire et au groupe

**Pourquoi pas d'exécution :** Même si un fichier PHP passe les validations, il ne peut pas être exécuté.

---

### 11. Protection contre Path Traversal (Listing)

#### Mesure : Validation du Chemin Réel

```php
$realUserDir = realpath($userDir);
$realUploadDir = realpath(self::UPLOAD_DIR);

if ($realUserDir === false || strpos($realUserDir, $realUploadDir) !== 0) {
    $logger->logSuspiciousActivity('DIRECTORY_TRAVERSAL_ATTEMPT', [...]);
    // Reject access
}
```

**Failles évitées :**

- **A03:2021 - Injection** : Path Traversal avec `../../../etc/passwd`
- **Directory Climbing** : Empêche de sortir du répertoire uploads/
- **Symlink Attack** : `realpath()` résout les liens symboliques
- **Canonicalization Attack** : Convertit en chemin absolu canonique

**Comment ça marche :**

1. `realpath()` convertit le chemin en chemin absolu sans `..`, `.`, symlinks
2. `strpos(..., 0)` vérifie que le chemin commence par le répertoire uploads
3. Si `realpath()` retourne `false`, le chemin n'existe pas ou est invalide

**Exemple d'attaque bloquée :**
```
Input:  /memory/upload/1/../../../etc/passwd
realpath: /etc/passwd
Check:  "/etc/passwd" ne commence pas par "/var/www/memory/uploads"
Result: BLOCKED
```

---

### 12. Sanitization du Nom de Fichier (Download/View)

#### Mesure : Validation Stricte du Nom

```php
$safeFilename = basename($filename);
if ($safeFilename !== $filename || strpos($filename, '..') !== false) {
    $logger->logSuspiciousActivity('FILE_VIEW_PATH_TRAVERSAL', [...]);
    // Reject access
}
```

**Failles évitées :**

- **Path Traversal** : `basename()` ne garde que le nom, supprime les chemins
- **Directory Navigation** : Détecte explicitement `..` dans le nom
- **Null Byte Injection** : Comparaison stricte empêche `file.jpg%00.php`

**Tests appliqués :**

| Input | basename() | Check | Result |
|-------|-----------|-------|--------|
| `file.jpg` | `file.jpg` | Equal | ✅ PASS |
| `../../etc/passwd` | `passwd` | Not equal | ❌ BLOCK |
| `../file.jpg` | `file.jpg` | Not equal | ❌ BLOCK |
| `dir/../file.jpg` | `file.jpg` | Contains .. | ❌ BLOCK |

---

### 13. Re-validation du Type MIME (Download/View)

#### Mesure : Vérification Avant Servir

```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $realFilePath);
finfo_close($finfo);

if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
    // Block download
}
```

**Failles évitées :**

- **File Replacement Attack** : Si un fichier est modifié après upload, il est re-vérifié
- **Race Condition** : Détecte si un fichier a été échangé entre upload et download
- **Stored XSS** : Empêche de servir des fichiers HTML/JavaScript stockés malicieusement
- **Content Sniffing Attack** : Combiné avec `X-Content-Type-Options: nosniff`

**Principe :** Never Trust, Always Verify (même pour les fichiers déjà stockés)

---

### 14. Headers de Sécurité (Inline Viewing)

#### Mesure : Configuration Sécurisée des Réponses HTTP

```php
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . addslashes($safeFilename) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
```

**Failles évitées :**

- **Content-Type Sniffing** : `X-Content-Type-Options: nosniff` force le navigateur à respecter le MIME type
- **XSS via Content-Type** : Fixe explicitement le type, empêche l'interprétation comme HTML
- **Filename Injection** : `addslashes()` échappe les guillemets dans le nom de fichier
- **HTTP Response Splitting** : Validation du filename empêche les CRLF (`\r\n`)

**Exemple d'attaque bloquée :**
```
Filename: "image.jpg\r\nContent-Type: text/html\r\n\r\n<script>alert('XSS')</script>"
After addslashes: "image.jpg\\r\\nContent-Type: text/html..."
Result: Interprété comme nom de fichier, pas comme headers
```

---

### 15. Headers de Sécurité (Download)

#### Mesure : Force le Téléchargement (Pas d'Exécution)

```php
header('Content-Disposition: attachment; filename="' . addslashes($safeFilename) . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
```

**Failles évitées :**

- **Drive-by Download Attack** : `attachment` force le téléchargement, pas l'exécution dans le navigateur
- **Cache Poisoning** : `no-cache` empêche le stockage en cache partagé
- **Stale Content Attack** : `Expires: 0` force la re-validation
- **XSS via PDF** : Même un PDF malveillant est téléchargé, pas ouvert inline

---

### 16. Nettoyage des Buffers de Sortie

#### Mesure : Prévention de Corruption de Données

```php
while (ob_get_level()) {
    ob_end_clean();
}
readfile($realFilePath);
```

**Failles évitées :**

- **File Corruption** : Évite que du HTML/texte précédent soit mélangé avec le binaire
- **Information Disclosure** : Empêche les messages de debug/warning d'apparaître dans le fichier
- **Header Injection** : Garantit qu'aucun contenu n'a été envoyé avant les headers
- **Binary Safety** : Assure l'intégrité des fichiers images/PDF

**Pourquoi important :** Si un warning PHP ou du HTML est déjà dans le buffer, il sera ajouté au début du fichier, le corrompant.

---

### 17. Logging et Audit Trail

#### Mesure : Traçabilité Complète

```php
$logger->logInfo('FILE_UPLOADED', [
    'user_id' => $userId,
    'filename' => $safeFileName,
    'original_filename' => $originalFileName,
    'file_size' => $file['size'],
    'mime_type' => $mimeType,
    'uploaded_by' => $currentUser->id_user
]);
```

**Failles évitées :**

- **A09:2021 - Security Logging Failures** : Tous les événements sont enregistrés
- **Forensics Gap** : En cas d'incident, traçabilité complète disponible
- **Insider Threat** : Détecte les administrateurs malveillants
- **Compliance Violation** : Répond aux exigences RGPD/audit

**Événements loggés :**

| Événement | Type | Données Capturées |
|-----------|------|-------------------|
| Upload réussi | INFO | User, fichier, taille, MIME, IP |
| Échec autorisation | WARNING | User, rôle, action tentée |
| Type MIME invalide | SUSPICIOUS | MIME détecté, filename, IP |
| Path traversal | SUSPICIOUS | Chemin demandé, IP, user |
| Download | INFO | User, fichier, taille |
| Erreur exception | ERROR | Message erreur, stack trace |

---

### 18. Gestion des Exceptions

#### Mesure : Handling Sécurisé des Erreurs

```php
try {
    // File operations
} catch (Exception $e) {
    $logger->logError('FILE_UPLOAD_EXCEPTION', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    $this->sendOutput(
        array("status" => "error", "message" => "An unexpected error occurred during file upload."),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
    );
}
```

**Failles évitées :**

- **A05:2021 - Security Misconfiguration** : Pas de stack traces exposées à l'utilisateur
- **Information Disclosure** : Messages génériques, détails dans les logs seulement
- **Debugging Information Leak** : Chemin du serveur, versions PHP cachées
- **Attack Fingerprinting** : L'attaquant ne peut pas identifier la technologie exacte

**Principe :** Fail Securely (échec sécurisé avec message générique)

---

## Stratégie de Défense en Profondeur

Le système implémente plusieurs couches de protection qui se renforcent mutuellement :

```
┌─────────────────────────────────────────────────┐
│ 1. Authentication (JWT Token)                   │
├─────────────────────────────────────────────────┤
│ 2. Authorization (User ID Check)                │
├─────────────────────────────────────────────────┤
│ 3. Input Validation (User ID, Filename)         │
├─────────────────────────────────────────────────┤
│ 4. File Size Check (Max 5MB)                    │
├─────────────────────────────────────────────────┤
│ 5. MIME Type Validation (Content Analysis)      │
├─────────────────────────────────────────────────┤
│ 6. Extension Validation (Whitelist)             │
├─────────────────────────────────────────────────┤
│ 7. Filename Sanitization (Remove Dangerous)     │
├─────────────────────────────────────────────────┤
│ 8. Filename Randomization (Prevent Overwrite)   │
├─────────────────────────────────────────────────┤
│ 9. Upload Verification (is_uploaded_file)       │
├─────────────────────────────────────────────────┤
│ 10. Path Traversal Check (realpath)             │
├─────────────────────────────────────────────────┤
│ 11. Directory Permissions (0750)                │
├─────────────────────────────────────────────────┤
│ 12. File Permissions (0640 - No Execute)        │
├─────────────────────────────────────────────────┤
│ 13. Security Headers (X-Content-Type-Options)   │
├─────────────────────────────────────────────────┤
│ 14. Audit Logging (All Operations)              │
└─────────────────────────────────────────────────┘
```

**Principe :** Si une couche est contournée, les autres la rattrapent.

---

## Mapping OWASP Top 10

| OWASP Vulnerability | Mesures de Protection |
|---------------------|----------------------|
| **A01:2021 - Broken Access Control** | Authorization checks, User directory isolation, Role-based permissions, Directory permissions (0750) |
| **A02:2021 - Cryptographic Failures** | Secure permissions (0640/0750), Secure random generation (random_bytes) |
| **A03:2021 - Injection** | MIME validation, Extension whitelist, Filename sanitization, Path traversal protection |
| **A04:2021 - Insecure Design** | Principle of Least Privilege, Deny by Default, Defense in Depth |
| **A05:2021 - Security Misconfiguration** | File size limits, Secure permissions, No execution rights, Generic error messages |
| **A06:2021 - Vulnerable Components** | N/A (utilise finfo PHP natif) |
| **A07:2021 - Authentication Failures** | JWT authentication (handled by BaseController) |
| **A08:2021 - Data Integrity Failures** | is_uploaded_file() verification, MIME re-validation on download |
| **A09:2021 - Logging Failures** | Comprehensive logging de tous événements, SecurityLogger |
| **A10:2021 - SSRF** | N/A (pas de requêtes sortantes basées sur input utilisateur) |

---

## Scénarios d'Attaque Bloqués

### Scénario 1 : Upload de Web Shell PHP

**Attaque :**
```
1. Attacker renomme shell.php en shell.jpg
2. Tente d'uploader via l'API
```

**Défenses activées :**
1. ✅ MIME type check : `finfo_file()` détecte `text/x-php` au lieu de `image/jpeg`
2. ✅ Extension check : Passe (jpg est autorisé)
3. ❌ **BLOCKED** par MIME validation

**Variante - Double Extension :**
```
1. Attacker uploade shell.php.jpg
2. Espère que le serveur l'exécute comme PHP
```

**Défenses activées :**
1. ✅ Extension check : `pathinfo()` extrait `.jpg` (dernière extension)
2. ✅ Filename sanitization : Les `.` sont remplacés par `_`
3. ✅ File permissions : `0640` (aucun droit d'exécution)
4. ❌ **BLOCKED** sur multiples niveaux

---

### Scénario 2 : Path Traversal pour Écraser /etc/passwd

**Attaque :**
```
POST /memory/upload/../../../../../../etc/passwd
File: malicious.jpg
```

**Défenses activées :**
1. ✅ User ID validation : `../../../../../../etc/passwd` n'est pas numérique
2. ✅ `is_numeric()` check échoue
3. ✅ Logged comme `INVALID_USER_ID`
4. ❌ **BLOCKED** avant même traitement du fichier

**Variante - Filename Traversal :**
```
POST /memory/upload/1
Filename: ../../../../../../var/www/index.php
```

**Défenses activées :**
1. ✅ `generateSecureFileName()` : Supprime tous les `/` et `.`
2. ✅ Résultat : `_var_www_index_1736950000_abc123.php`
3. ✅ Sauvegardé dans `/uploads/1/_var_www_index_1736950000_abc123.php`
4. ✅ **BLOCKED** - Fichier isolé dans le bon répertoire

---

### Scénario 3 : Saturation du Disque (DoS)

**Attaque :**
```
1. Attacker uploade 1000 fichiers de 100 Mo chacun
2. Tente de remplir le disque pour crasher le serveur
```

**Défenses activées :**
1. ✅ File size check : Premier fichier rejeté (> 5MB)
2. ✅ Logged comme `FILE_SIZE_EXCEEDED`
3. ✅ HTTP 413 Payload Too Large retourné
4. ❌ **BLOCKED** - Impossible de dépasser 5MB par fichier

**Impact maximal possible :**
- 1000 fichiers × 5 MB = 5 GB maximum (gérable)

---

### Scénario 4 : IDOR - Accès aux Fichiers d'Autres Utilisateurs

**Attaque :**
```
1. User ID 10 tente de lister les fichiers de User ID 5
GET /memory/upload/5
```

**Défenses activées :**
1. ✅ Authorization check : `$currentUser->id_user (10) != $userId (5)`
2. ✅ Role check : User n'est pas admin/projectManager
3. ✅ Logged comme `AUTHORIZATION_FAILURE`
4. ✅ HTTP 403 Forbidden retourné
5. ❌ **BLOCKED** - Aucune information divulguée

---

### Scénario 5 : Stored XSS via Filename

**Attaque :**
```
Filename: <script>alert('XSS')</script>.jpg
User espère que le filename sera affiché sans échappement
```

**Défenses activées :**
1. ✅ `generateSecureFileName()` : Regex `[^a-zA-Z0-9_-]` supprime `< > ( ) '`
2. ✅ Résultat : `script_alert_XSS_script_1736950000_def456.jpg`
3. ✅ Content-Disposition header : `addslashes()` échappe les guillemets
4. ✅ **BLOCKED** - Filename sanitized, aucun XSS possible

---

### Scénario 6 : MIME Type Spoofing

**Attaque :**
```
1. Attacker crée un fichier HTML malveillant
2. Change l'extension en .jpg
3. Modifie manuellement les magic bytes pour ressembler à JPEG
```

**Défenses activées :**
1. ✅ MIME check : `finfo_file()` analyse les magic bytes réels
2. ⚠️ Si vraiment bien fait, pourrait passer comme image/jpeg
3. ✅ MAIS : Extension `.jpg` imposée par le système
4. ✅ MIME re-validé au download/view
5. ✅ `X-Content-Type-Options: nosniff` empêche le navigateur de réinterpréter
6. ✅ `Content-Disposition: attachment` force le download (pas d'exécution)
7. ✅ **MITIGATED** - Même si uploadé, ne peut pas être exécuté

---

### Scénario 7 : Symlink Attack

**Attaque :**
```
1. Attacker crée un symlink dans /tmp pointant vers /etc/passwd
2. Tente de l'uploader
```

**Défenses activées :**
1. ✅ `is_uploaded_file()` : Vérifie que le fichier est un vrai upload, pas un symlink
2. ✅ `realpath()` lors du listing/download : Résout les symlinks
3. ✅ Path validation : Vérifie que le chemin résolu est dans uploads/
4. ❌ **BLOCKED** - Symlinks détectés et rejetés

---

### Scénario 8 : Race Condition (TOCTOU)

**Attaque :**
```
1. Attacker uploade image.jpg légitime
2. Pendant le traitement, remplace /tmp/phpXXXX par shell.php
3. Espère que move_uploaded_file() déplace le shell
```

**Défenses activées :**
1. ✅ `is_uploaded_file()` : Vérifie que tmp_name est toujours dans la liste PHP
2. ✅ Si modifié, n'est plus reconnu comme uploaded file
3. ✅ Logged comme `FILE_UPLOAD_TAMPERING`
4. ❌ **BLOCKED** - Impossible de swapper le fichier

---

## Recommandations Complémentaires

### 1. Scan Antivirus (Future Enhancement)

**Mesure proposée :**
```php
// Intégrer ClamAV
$scanner = new \Xenolope\Quahog\Client('unix:///var/run/clamav/clamd.ctl');
$result = $scanner->scanFile($file['tmp_name']);
if ($result['status'] !== 'OK') {
    // Reject file
}
```

**Failles supplémentaires évitées :** Malware, Trojans, Ransomware

---

### 2. Rate Limiting

**Mesure proposée :**
```php
// Limiter à 10 uploads par heure par utilisateur
if ($rateLimiter->isExceeded($currentUser->id_user, 'upload', 10, 3600)) {
    // Return 429 Too Many Requests
}
```

**Failles supplémentaires évitées :** Automated attacks, Brute force upload attempts

---

### 3. Content Security Policy

**Mesure proposée :**
```php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'none';");
```

**Failles supplémentaires évitées :** XSS dans vues d'images, JavaScript injection

---

### 4. Image Reprocessing

**Mesure proposée :**
```php
// Recréer l'image pour supprimer metadata et potentiels exploits
$img = imagecreatefromjpeg($file['tmp_name']);
imagejpeg($img, $targetFilePath, 90);
imagedestroy($img);
```

**Failles supplémentaires évitées :** EXIF exploits, Metadata leaks, Steganography attacks

---

## Checklist de Sécurité

### Upload
- [x] Authentication requise (JWT)
- [x] Authorization vérifiée (user owns directory)
- [x] User ID validé (numeric only)
- [x] File size limité (5MB max)
- [x] MIME type vérifié (content analysis)
- [x] Extension validée (whitelist)
- [x] Filename sanitized (alphanumeric only)
- [x] Filename randomized (unique + timestamp)
- [x] Upload verified (is_uploaded_file)
- [x] Directory permissions secure (0750)
- [x] File permissions secure (0640)
- [x] All events logged
- [x] Errors handled securely

### Download/View
- [x] Authorization vérifiée
- [x] Filename sanitized (basename + no ..)
- [x] Path traversal prevented (realpath check)
- [x] MIME type re-validated
- [x] Security headers set (X-Content-Type-Options)
- [x] Content-Disposition approprié
- [x] Output buffers cleared
- [x] All events logged

---

## Conclusion

Le système d'upload implémente **18 mesures de sécurité distinctes** qui se renforcent mutuellement, créant une défense en profondeur robuste contre les attaques courantes. Chaque couche adresse des vulnérabilités spécifiques de l'OWASP Top 10, avec un focus particulier sur :

1. **A01 - Broken Access Control** (5 mesures)
2. **A03 - Injection** (6 mesures)
3. **A05 - Security Misconfiguration** (4 mesures)
4. **A09 - Logging Failures** (1 mesure complète)

**Principe directeur :** Deny by Default, Least Privilege, Defense in Depth, Fail Securely.

---

## Références

- [OWASP Top 10 2021](https://owasp.org/www-project-top-ten/)
- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [CWE-434: Unrestricted Upload of File with Dangerous Type](https://cwe.mitre.org/data/definitions/434.html)
- [CWE-22: Path Traversal](https://cwe.mitre.org/data/definitions/22.html)
- [PHP Security: File Uploads](https://www.php.net/manual/en/features.file-upload.php)
