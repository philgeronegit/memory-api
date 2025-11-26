### General Principles

* **Backend (PHP API)**: It serves as the source of truth and enforcer of data integrity. It must revalidate all received client data, never trusting anything from the frontend. It applies security rules, access restrictions, and complex business logic.

---

### Business Rules by Functionality

#### 1. User Management and Authentication

* **Registration**
    * **Backend (Validation & Restriction)**:
        * **Validation**: Re-validate all frontend data.
        * **Restriction**: The email address must be unique in the database.
        * **Exception**: Throw a `409 Conflict` error if the email already exists.
        * **Logic**: Hash the password before saving (never store passwords in plaintext).

* **Login**
    * **Backend (Validation & Restriction)**:
        * **Validation**: Verify that the user exists with the provided email.
        * **Validation**: Compare the hashed provided password with the stored one.
        * **Restriction**: Implement a limit on login attempts (e.g., 5 failed attempts lock the account for 15 minutes) to prevent brute-force attacks.
        * **Exception**: Return a generic `401 Unauthorized` error ("Email or password incorrect") without indicating which information is wrong.

* **Profile and Role Management**
    * **Backend (Restriction)**:
        * A user can only modify their own profile, unless they are "Project Manager" or "Administrator".
        * The role of a user (`Alternant`, `Dev Senior`, `Project Manager`) can only be modified by an "Administrator".
        * Technical skills must come from a predefined list or follow a specific format to ensure consistency.

#### 2. Note Management

* **Creation / Modification**
    * **Backend (Precondition & Validation)**:
        * **Precondition**: The user must be authenticated to create a note. Return `401 Unauthorized` if not.
        * **Validation**: Validate that the title and content are not empty.
        * **Validation**: Verify that the `project_id`, `user_ids` or `tags` sent exist in the database to avoid inconsistencies.
        * **Restriction**: A user can only modify or delete a note they created (except for roles with extended permissions).

#### 3. Comment Management

* **Adding a comment**
    * **Backend (Precondition & Restriction)**:
        * **Precondition**: The user must be authenticated.
        * **Restriction**: The user must have access to the note to be able to comment on it (either because it is shared with the entire team or directly with them).
        * **Validation**: The `note_id` must correspond to an existing note.
        * **Exception**: Return `403 Forbidden` if the user tries to comment on a note they don't have access to.

#### 4. Access Control and Confidentiality

These rules are almost exclusively handled on the **Backend**.

* **Access to Notes**
    * **Backend (Restriction)**:
        * A user can only see notes:
            1. They created.
            2. Shared specifically with them.
            3. Shared with the entire team.
        * This logic should be applied to every `GET` request on a note or list of notes.

* **Access to Participation Reports**
    * **Backend (Restriction)**:
        * Only users with the role "Project Manager" or "Administrator" can access metrics and generate reports.
        * **Exception**: Return `403 Forbidden` for any other user trying to access these resources.

* **Access to Detailed Profiles**
    * **Backend (Restriction)**:
        * A user can see the detailed profile (including skills) of any other user to facilitate task assignment.
        * However, sensitive information (like the email) should only be visible to "Project Managers" or the user themselves.