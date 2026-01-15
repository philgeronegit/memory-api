# Service Layer Architecture - Class Diagram

## Overview

This diagram illustrates the service layer architecture implementing separation of concerns with controllers, services, validators, and models.

## GET Note Route - Complete Flow

This sequence diagram shows the complete flow of a `GET /memory/note/123` request, including JWT validation, role extraction, and permission checking.

```mermaid
sequenceDiagram
    participant Client
    participant Router as index.php
    participant JWT as verifyJwtToken()
    participant Globals as $GLOBALS['jwt_user_data']
    participant Controller as NoteController
    participant Base as BaseController
    participant AuthSvc as AuthorizationService
    participant Logger as SecurityLogger
    participant Model as NoteModel
    participant DB as Database

    Client->>Router: GET /memory/note/123
    Note over Router: Parse URI: ['', 'memory', 'note', '123']

    Router->>Router: Check if route is 'login'
    Note over Router: Not login, requires JWT

    Router->>JWT: verifyJwtToken()
    JWT->>JWT: Extract Authorization header

    alt No Authorization Header
        JWT->>Client: 401 Unauthorized<br/>"Authorization header missing"
    end

    JWT->>JWT: Extract token from "Bearer {token}"
    JWT->>JWT: JWT::decode(token, secretKey)

    alt Invalid or Expired Token
        JWT->>Logger: logTokenValidationFailure()
        JWT->>Client: 401 Unauthorized<br/>"Invalid or expired token"
    end

    JWT-->>Router: decoded token data
    Router->>Globals: Store decoded->data
    Note over Globals: Contains: {<br/>  id_user: 1,<br/>  username: "john",<br/>  email: "john@example.com",<br/>  role: "developer"<br/>}

    Router->>Router: Parse URI segments
    Note over Router: uri[2] = 'note'<br/>uri[3] = '123'<br/>Method = GET

    Router->>Router: switch(uri[2]) case 'note'
    Router->>Controller: new NoteController()
    Note over Controller: No service injected<br/>Uses model directly

    Router->>Router: Determine action: GET + uri[3] exists
    Router->>Controller: getAction()

    Controller->>Base: doAction(fn)
    Base->>Base: try { fn() }

    Base->>Base: get_class($this)
    Note over Base: Returns "NoteController"

    Base->>Base: getResourceNameFromController("NoteController")
    Note over Base: Maps to "notes"

    Base->>Base: requiresReadPermission("notes")
    Note over Base: Protected resource = true

    Base->>Base: getAuthenticatedUser()
    Base->>Globals: Read jwt_user_data
    Globals-->>Base: user object {id_user, username, email, role}

    Base->>AuthSvc: getInstance()
    AuthSvc-->>Base: authService instance

    Base->>AuthSvc: can(user, "notes", "read")
    AuthSvc->>AuthSvc: Check user.role
    Note over AuthSvc: user.role = "developer"

    alt Role is "admin"
        AuthSvc-->>Base: true (admin has all permissions)
    end

    AuthSvc->>AuthSvc: getRolePermissions("developer")
    Note over AuthSvc: Returns array including<br/>"view:notes"

    AuthSvc->>AuthSvc: Check if "view:notes" in permissions

    alt Permission NOT found
        AuthSvc-->>Base: false
        Base->>AuthSvc: logAuthorizationFailure("notes", "read", userId, role)
        AuthSvc->>Logger: logAuthorizationFailure()
        Logger->>Logger: Write to logs/security.log
        Base->>Base: throw Exception("You do not have permission to view notes")
        Base->>Client: 403 Forbidden<br/>{"error": "You do not have permission..."}
    end

    AuthSvc-->>Base: true (permission granted)
    Note over Base: Permission check passed

    Base->>Base: getUriSegments()[3]
    Note over Base: id = 123

    Base->>Base: getQueryStringParams()
    Note over Base: args = {}

    Base->>Model: getOne(123, {})
    Model->>Model: Build SQL query with JOINs
    Note over Model: SELECT item.*, note.*, user.*<br/>FROM item JOIN note...

    Model->>DB: Execute prepared statement
    DB-->>Model: Result row

    Model->>Model: Format result as object
    Model-->>Base: note object {<br/>  id_note: 123,<br/>  title: "My Note",<br/>  content: "...",<br/>  type: "note",<br/>  id_user: 1,<br/>  username: "john",<br/>  ...<br/>}

    Base->>Base: sendOutput(noteObject)
    Base->>Base: header('Content-Type: application/json')
    Base->>Base: header('HTTP/1.1 200 OK')
    Base->>Base: echo json_encode(noteObject)
    Base->>Client: 200 OK<br/>{"id_note": 123, "title": "My Note", ...}

    Note over Client,DB: Request completed successfully
```

## Authorization Decision Tree

```mermaid
flowchart TD
    Start[GET /memory/note/123] --> JWT{JWT Token Valid?}

    JWT -->|No| Reject1[401 Unauthorized<br/>Token missing/invalid]
    JWT -->|Yes| Extract[Extract user data from token]

    Extract --> Store[Store in $GLOBALS]
    Store --> Route[Route to NoteController]
    Route --> Action[Call getAction]

    Action --> DetectController[Detect controller class:<br/>'NoteController']
    DetectController --> MapResource[Map to resource:<br/>'notes']
    MapResource --> CheckProtected{Is 'notes' protected?}

    CheckProtected -->|No| DirectCall[Call model directly]
    CheckProtected -->|Yes| GetUser[Get user from globals]

    GetUser --> AuthCheck[AuthorizationService.can<br/>user, 'notes', 'view']

    AuthCheck --> RoleCheck{Is role 'admin'?}
    RoleCheck -->|Yes| Allow[✅ Permission granted]
    RoleCheck -->|No| LookupPerms[Get permissions for role]

    LookupPerms --> HasPerm{Has 'view:notes'<br/>permission?}

    HasPerm -->|No| LogFail[Log authorization failure]
    LogFail --> Reject2[403 Forbidden<br/>No permission to view notes]

    HasPerm -->|Yes| Allow
    Allow --> QueryDB[Query NoteModel.getOne]
    QueryDB --> CheckExists{Note exists?}

    CheckExists -->|No| NotFound[Return null/empty]
    CheckExists -->|Yes| Return[Return note data]

    DirectCall --> QueryDB
    Return --> Success[200 OK + JSON]
    NotFound --> Success

    style Reject1 fill:#f88
    style Reject2 fill:#f88
    style Success fill:#8f8
    style Allow fill:#8f8
```

## Architecture Diagram

```mermaid
classDiagram
    %% Base Classes
    class BaseController {
        #model
        #getAuthenticatedUser()
        #getCurrentUserId()
        #getCurrentUserRole()
        #hasUserModificationPermission()
        #hasPermission(requiredRole)
        #getResourceNameFromController(controllerClass)
        #requiresReadPermission(resourceName)
        #getUriSegments()
        #getQueryStringParams()
        #getRequestBody(name)
        #sanitizeInput(value)
        #sendOutput(data, httpHeaders)
        #getQueryString(name, default)
        #doAction(fn, args)
        +listAction(args)
        +getAction()
        +removeAction()
    }

    class BaseService {
        #authorizationService
        #validationService
        #getAuthenticatedUser()
        #getCurrentUserId()
        #getCurrentUserRole()
        #throwValidationError(message)
        #throwAuthorizationError(message)
    }

    class Database {
        #connection
        #baseQuery
        +select(query, params)
        +selectOne(query, params)
        +insert(query, params)
        +update(query, params)
        +delete(query, params)
    }

    class IModel {
        <<interface>>
        +getAll(args)
        +getOne(id, args)
        +add(paramsArray)
        +modify(paramsArray)
        +remove(id)
    }

    %% Authorization Service (Singleton)
    class AuthorizationService {
        -roleModel
        -instance$
        +getInstance()$
        +can(user, resource, action) bool
        +owns(user, resourceOwnerId) bool
        +canModify(user, resourceOwnerId) bool
        +logAuthorizationFailure(resource, action, userId, role)
        -getRolePermissions(role) array
    }

    %% Validators
    class NoteValidator {
        +validateCreate(data) array
        +validateUpdate(data) array
    }

    class UserValidator {
        -userModel
        +validateCreate(data) array
        +validateUpdate(data) array
    }

    class ProjectValidator {
        +validateCreate(data) array
        +validateUpdate(data) array
    }

    %% Services
    class NoteService {
        -noteModel
        -validator
        +createNote(data) array
        +updateNote(noteId, data) array
        +deleteNote(noteId) bool
        +shareNote(data) mixed
    }

    class UserService {
        -userModel
        -validator
        +createUser(data) array
        +updateUser(userId, data) array
        +deleteUser(userId) bool
    }

    class ProjectService {
        -projectModel
        -validator
        +createProject(data) array
        +updateProject(projectId, data) array
        +deleteProject(projectId) bool
        +addUsersToProject(data) mixed
        +removeUsersFromProject(data) mixed
    }

    %% Models
    class NoteModel {
        +getAll(args) array
        +getOne(id, args) object
        +add(paramsArray) array
        +modify(paramsArray) array
        +remove(id) bool
        +shareNoteWithUser(data) mixed
    }

    class UserModel {
        +getAll(args) array
        +getOne(id, args) object
        +add(paramsArray) array
        +modify(paramsArray) array
        +remove(id) bool
        +validate(data) array
        +validateUpdate(data) array
    }

    class ProjectModel {
        +getAll(args) array
        +getOne(id, args) object
        +add(paramsArray) array
        +modify(paramsArray) array
        +remove(id) bool
        +addToProject(data) mixed
        +deleteFromProject(data) mixed
    }

    %% Controllers
    class NoteController {
        -noteService
        +addAction()
        +updateAction()
        +removeAction()
        +shareNoteWithUser()
    }

    class UserController {
        -userService
        +addAction()
        +updateAction()
        +removeAction()
    }

    class ProjectController {
        -projectService
        +addAction()
        +updateAction()
        +removeAction()
        +addProjectToUser()
        +removeProjectFromUser()
    }

    %% Inheritance Relationships
    BaseController <|-- NoteController
    BaseController <|-- UserController
    BaseController <|-- ProjectController

    BaseService <|-- NoteService
    BaseService <|-- UserService
    BaseService <|-- ProjectService

    Database <|-- NoteModel
    Database <|-- UserModel
    Database <|-- ProjectModel

    IModel <|.. NoteModel
    IModel <|.. UserModel
    IModel <|.. ProjectModel

    %% Composition Relationships
    NoteController *-- NoteService : uses
    UserController *-- UserService : uses
    ProjectController *-- ProjectService : uses

    NoteService *-- NoteModel : uses
    NoteService *-- NoteValidator : uses
    NoteService ..> AuthorizationService : depends on

    UserService *-- UserModel : uses
    UserService *-- UserValidator : uses
    UserService ..> AuthorizationService : depends on

    ProjectService *-- ProjectModel : uses
    ProjectService *-- ProjectValidator : uses
    ProjectService ..> AuthorizationService : depends on

    UserValidator ..> UserModel : validates with

    %% Notes
    note for BaseController "Provides common HTTP handling\nand automatic permission checks\nfor list/get actions"

    note for AuthorizationService "Singleton pattern\nCentralized RBAC:\n- admin: all permissions\n- projectManager: full CRUD\n- leadDeveloper: limited CRUD\n- developer: basic CRUD"

    note for BaseService "Provides common service\nfunctionality and user context"

    note for NoteService "Enforces business rules:\n- Field validation\n- Permission checks\n- Ownership verification"
```

## Data Flow

```mermaid
sequenceDiagram
    participant Client
    participant Router as index.php
    participant Controller
    participant Service
    participant Validator
    participant AuthService as AuthorizationService
    participant Model
    participant DB as Database

    Client->>Router: POST /memory/note
    Router->>Router: Verify JWT
    Router->>Controller: new NoteController(noteService)
    Router->>Controller: addAction()

    Controller->>Controller: Extract request data
    Controller->>Service: createNote(data)

    Service->>Service: getAuthenticatedUser()
    Service->>Validator: validateCreate(data)
    Validator-->>Service: {valid: bool, errors: []}

    alt Validation Failed
        Service-->>Controller: throw ValidationError
        Controller-->>Client: 400 Bad Request
    end

    Service->>AuthService: can(user, 'notes', 'create')
    AuthService->>AuthService: getRolePermissions(role)
    AuthService-->>Service: bool

    alt No Permission
        AuthService->>AuthService: logAuthorizationFailure()
        Service-->>Controller: throw AuthorizationError
        Controller-->>Client: 403 Forbidden
    end

    Service->>Model: add(data)
    Model->>DB: INSERT INTO item/note
    DB-->>Model: created data
    Model-->>Service: note object
    Service-->>Controller: note object
    Controller-->>Client: 200 OK + JSON
```

## Permission Flow

```mermaid
flowchart TD
    A[Request arrives] --> B{JWT Valid?}
    B -->|No| C[401 Unauthorized]
    B -->|Yes| D[Controller Action Called]

    D --> E{Is list/get action?}
    E -->|Yes| F[BaseController checks read permission]
    E -->|No| G[Action method executes]

    F --> H{Has read permission?}
    H -->|No| I[Log failure + 403 Forbidden]
    H -->|Yes| G

    G --> J{Uses Service?}
    J -->|No| K[Direct Model Call]
    J -->|Yes| L[Service validates data]

    L --> M{Valid?}
    M -->|No| N[400 Bad Request]
    M -->|Yes| O[Service checks permission]

    O --> P{AuthService.can?}
    P -->|No| Q[Log failure + 403 Forbidden]
    P -->|Yes| R{Needs ownership check?}

    R -->|Yes| S{Owns resource or admin?}
    R -->|No| T[Service calls Model]

    S -->|No| Q
    S -->|Yes| T

    T --> U[Model executes SQL]
    U --> V[200 OK + Response]

    K --> V
```

## Key Architectural Principles

### 1. Separation of Concerns
- **Controllers**: HTTP handling, request/response formatting
- **Services**: Business logic, validation, authorization
- **Validators**: Data validation rules
- **Models**: Database operations only
- **AuthorizationService**: Centralized permission management

### 2. Dependency Injection
- Services injected into controllers via constructor
- Models injected into services
- Validators instantiated within services

### 3. Single Responsibility
- Each class has one clear purpose
- Validators only validate
- Services only handle business logic
- Models only interact with database

### 4. Security Layers
1. **JWT verification** (index.php)
2. **BaseController read permissions** (list/get actions)
3. **Service-level authorization** (CRUD operations)
4. **Ownership verification** (update/delete)
5. **Audit logging** (all authorization failures)

### 5. Gradual Migration
- BaseController provides fallback for non-migrated controllers
- Services can be added incrementally
- Existing functionality preserved during migration

## Resource Permission Matrix

| Role            | create:notes | view:notes | update:notes | delete:notes | create:projects | view:projects | update:projects | delete:projects |
|-----------------|--------------|------------|--------------|--------------|-----------------|---------------|-----------------|-----------------|
| admin           | ✅           | ✅         | ✅           | ✅           | ✅              | ✅            | ✅              | ✅              |
| projectManager  | ✅           | ✅         | ✅           | ✅           | ✅              | ✅            | ✅              | ✅              |
| leadDeveloper   | ✅           | ✅         | ✅*          | ✅*          | ❌              | ✅            | ❌              | ❌              |
| developer       | ✅           | ✅         | ✅*          | ✅*          | ❌              | ✅            | ❌              | ❌              |

*Only own resources or if admin

## File Structure

```
memory/
├── Controllers/Api/
│   ├── BaseController.php          # Base with auto permission checks
│   ├── NoteController.php          # ✅ Migrated to services
│   ├── UserController.php          # ✅ Migrated to services
│   ├── ProjectController.php       # ✅ Migrated to services
│   └── ...OtherControllers.php     # 🔄 Not yet migrated
├── Services/
│   ├── BaseService.php             # Common service functionality
│   ├── AuthorizationService.php    # Singleton RBAC manager
│   ├── NoteService.php             # Note business logic
│   ├── NoteValidator.php           # Note validation
│   ├── UserService.php             # User business logic
│   ├── UserValidator.php           # User validation
│   ├── ProjectService.php          # Project business logic
│   └── ProjectValidator.php        # Project validation
├── Models/
│   ├── Database.php                # Base database class
│   ├── IModel.php                  # Model interface
│   ├── NoteModel.php               # Note repository
│   ├── UserModel.php               # User repository
│   └── ProjectModel.php            # Project repository
└── index.php                       # Router + DI container
```
