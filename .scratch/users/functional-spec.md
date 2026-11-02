# Users — functional spec

<!-- The authoritative functional reference for implementing Users in V2.
     It describes behaviour, not implementation: no classes, tables, patterns or framework terms,
     except inside legacy traces. Data is described as information the business needs, not as schema.
     Every requirement carries a trace: (F-/BR-/W- ids, rNN; D- decision). -->

- **Agreed:** 2026-11-01 · **Discovery:** `.scratch/users/discovery/discovery.md` · **V1 baseline:** `511ce3b`
- **Decision counts:** preserve 3 · correct 30 · new 4 · exclude 7 · pending 1 (covering ADD-02, ADD-03, ADD-04)
  - Mixed decisions (preserve + correct) are counted as correct. D-15 was superseded by D-22, and D-44 is the confirmation of shared understanding; neither is counted.

## 1. Purpose and responsibilities

Users is the core module of the panel. It knows who may use the panel and what each person may do.

**Users owns:**
- **Users.** AllGlass staff and external sellers are both Users and differ only by Role. Each User has an account with contact data, company data and an Account type.
- **Sign-in and passwords.** This covers sign-in, sessions, password reset, invitations and the forced password change.
- **Roles and Permissions:**
  - a registry where every module declares its own Roles and Permissions;
  - the single place where Permission checks are answered;
  - Extra Permissions granted to individual Users;
  - the Super Admin.
- **User management:** list, detail, create, edit, deactivate and reactivate Users, and impersonation.
- **The Account type** (Private or Company) and the Seller declaration, and announcing their changes.
- **The Journal.** Every User has one, any module can write entries to it, and only its owner reads it.

**Users does not own:**
- offers, their statistics, ownership or visibility, or prices;
- the panel notification bell;
- the language switch;
- the Change history and the Technical log (Audit);
- the settings screens.

Users provides other modules with what they need about a User (§9). (D-01, D-02, D-03, D-04, D-05, D-43)

## 2. Domain concepts

These terms are defined in `GLOSSARY.md`:
- User, Deactivated User;
- Role, Super Admin, Permission, Extra Permission;
- Account type, Seller declaration;
- Journal, Journal entry.

The module also uses these local concepts:

- **Built-in Roles.** Users declares three Roles:
  - **User:** a seller who sees only their own affairs.
  - **Admin:** AllGlass staff for daily operations.
  - **Super Admin:** everything.

  Other modules may declare further Roles. A Role is "registered" when the module that declares it is installed. (D-10, D-11)
- **Role order.** Super Admin > Admin > User. Roles declared by other modules rank at the User level unless their module states otherwise. This order is used for "lower Role" rules (RULE-08, RULE-10). (D-22, D-23)
- **"Admins" recipient group.** All active Users whose Role is Admin or Super Admin. Other modules use this group when they notify AllGlass staff. (D-12, D-47)
- **Password change required.** A marker on a User. While it is set, the User must set a new password before using the panel. (D-46)
- **Invitation.** An e-mail with a time-limited link that lets a new or migrated User set their own password. (D-38, D-41)
- **Account-type lock.** For 30 minutes after a User changes their Account type, they cannot change it again. (D-27)
- **Journal entry kind** names the area an entry concerns, for example `offer` or `users`. **Severity** is one of info, success, warning or rejection, and drives only how the entry looks. (D-16)

## 3. Actors and permissions

Every action below is guarded by a Permission declared by Users. Users grants them to the built-in Roles as listed. A Super Admin passes every check. A Super Admin can also grant any of these Permissions to an individual User as an Extra Permission.

| Actor | May | May not | Trace |
|---|---|---|---|
| **Guest** | sign in; request a password-reset link; set a password from a reset or invitation link | register an account; reach any panel page | D-34, D-37 |
| **User** (seller) | use the panel; edit own profile, password and Account type; read and manage own Journal | see or manage other Users; change own Role; edit own Otomoto shipping text | D-10, D-11, D-30, D-16 |
| **Admin** | everything a User may; list and view all Users; create Users with Role User or Admin; edit, deactivate and reactivate Users and Admins; demote an Admin to User; set a temporary password, force or clear a password change, re-send an invitation or reset link; receive "Admins" notifications and Journal entries; impersonate a lower-Role User **only** when granted it as an Extra Permission | assign the Super Admin Role; edit, deactivate or impersonate a Super Admin; change own Role; deactivate self; see or edit Extra Permissions; change anyone's Account type or Seller declaration | D-11, D-13, D-22, D-23, D-30, D-39, D-40, D-41, D-46 |
| **Super Admin** | everything; grant and revoke Extra Permissions; impersonate any lower-Role User; settings and the activity log (by Permission) | be granted or revoked the Super Admin Role from the panel (console only); edit another Super Admin's account in any way (D-48); impersonate another Super Admin; change own Role; change anyone's Account type or Seller declaration | D-09, D-14, D-21, D-22, D-23, D-48 |
| **Operator (console)** | grant or revoke the Super Admin Role | — | D-09 |
| **Other modules** | declare Roles and Permissions; grant their own Permissions to Roles; ask whether a User holds a Permission; add Journal entries; read User data; react to Users' announcements | change Users' data directly | D-02, D-19, D-43 |

## 4. Functional requirements

### 4.1 Roles and Permissions

- **FR-01** [Correct] Roles and the Permissions each grants are defined only in code. The panel has no screen to create, edit or delete Roles or their Permissions. V1 had such a screen, along with editable, deletable built-in Roles. (F-04, BR-05, Q-05, r02; D-08)
- **FR-02** [Correct] Every module declares its own Roles and Permissions to Users when the application starts. Each module also declares which Roles receive its own Permissions. Users holds no central role-to-permission map. When a module is removed, its Roles, its Permissions and its grants disappear with it. (D-02, D-19)
- **FR-03** [Correct] A Permission check for a User passes when any of these holds:
  - the User's Role is Super Admin;
  - the User's Role has been granted that Permission;
  - the User holds it as an Extra Permission.

  Otherwise it fails. No behaviour anywhere is decided by the name of a Role. The only exception is the "Admins" recipient group. V1 decided admin behaviour by Role name in about 60 places, and gave Super Admin no bypass. (BR-07, BR-08, BR-22, r02, r05; D-09, D-12, D-21)
- **FR-04** [Correct] Each User holds exactly one Role. (F-05, Q-04, Q-09, r02, r07; D-10)
- **FR-05** [Correct] The Super Admin Role can be granted or revoked only through a console command, never in the panel. The Role selector in the panel never offers Super Admin. V1 let anyone with user-management rights grant it. (BR-26; D-09)
- **FR-06** [New] A Super Admin can grant any registered Permission to an individual User as an Extra Permission, and revoke it, in the user create or edit form. Only Super Admins see this part of the form. The user detail lists a User's Extra Permissions separately from their Role. Every grant and revocation is recorded in the Change history. (D-21)

### 4.2 Panel access and sign-in

- **FR-07** [Correct] A User may use the panel when all of these hold:
  - they are active, not deactivated;
  - their Role is currently registered.

  A User whose Role is not registered is refused at sign-in with a message saying their account has no valid role and they should contact AllGlass. Nothing about such a User is deleted automatically; an Admin can assign a registered Role. V1 admitted only the Roles Super Admin and User, which locked out Admin-only accounts. (F-03, BR-05, Q-04, r02; D-10, D-20, D-39)
- **FR-08** [Preserve] Users sign in to the panel with e-mail and password. The site root and the plain sign-in address lead to the panel sign-in page. Panel pages are not indexed by search engines. (F-07, r01, r04; D-37)
- **FR-09** [New] Sign-in attempts are rate-limited: at most 5 failed attempts per minute per e-mail and IP address. Exceeding the limit refuses further attempts for the rest of the window with a message saying when to retry. (F-07; D-37, D-47)
- **FR-10** [Preserve] Sessions last 120 minutes of inactivity and survive closing the browser. When a User's password changes, all their other sessions end. (BR-23, r01; D-37)
- **FR-11** [Correct] The panel sign-in page has a "Nie pamiętasz hasła?" link.
  - A Guest enters an e-mail address and the system sends a password-reset link valid for 60 minutes.
  - The response is the same whether or not the address belongs to a User.
  - Setting a password through the link signs the User in.

  V1 had the reset only at a hidden address. (F-15, r04, r08; D-34)
- **FR-12** [Correct] Nobody can register themselves. Accounts are created only by an Admin or Super Admin (FR-24). V1 had open registration that gave immediate panel access. (F-15, BR-27, W-05, r08; D-34)

### 4.3 Own profile ("Mój profil")

- **FR-13** [Preserve] Every signed-in User can open "Mój profil" from the user menu ("Edytuj profil") and from the dashboard account tile. (F-08, F-19, r01, r09; D-30, D-33)
- **FR-14** [Correct] In "Mój profil" a User edits:
  - name, e-mail and phone;
  - company name, tax identification number (NIP) and company address;
  - pickup address ("Adres do wysyłki", hint "Jeżeli inny niż adres firmy"), city and postal code;
  - Account type and Seller declaration (FR-17);
  - a new password with confirmation. A generator fills both password fields with a random 20-character password.

  "Zapisz" validates and saves the form, then shows "Zapisano dane profilu". This split between profile and admin form is agreed for now and may change later. Validation follows RULE-01 to RULE-03. V1 accepted malformed or duplicate e-mail addresses here. (F-08, BR-15, W-02, Q-13, r01; D-29, D-30)
- **FR-15** [Preserve] The User cannot see or edit their Otomoto shipping text in the profile. (F-18, F-08; D-06, D-30)
- **FR-16** [Preserve] While a User's Account type is not Company, the navigation shows an "i" badge next to "Mój profil". (F-08, r01; D-30)

### 4.4 Account type and Seller declaration

- **FR-17** [Correct] The Account type is one choice: Private or Company.
  - **Switching to Company** requires the NIP, the company address and acceptance of the Seller declaration (RULE-04). The acceptance date and the declaration text's version are recorded.
  - **Switching to Private** clears the acceptance.

  A User is Company only when they chose Company **and** their acceptance is recorded. In V1 the company flag and the declaration flag were independent and used inconsistently. (F-09, BR-12, BR-24, Q-11, r01, r04; D-24)
- **FR-18** [Correct] While the User is Private, the profile shows a notice that VAT is added to the prices of their offers before publication. Visibility is not mentioned: a Private User's offers are visible and published like anyone else's. Choosing Private shows the "Pamiętaj!" notice about VAT, with the button "Rozumiem". V1's notice wrongly claimed private offers are visible only to the owner and AllGlass. (F-09, Q-19, r06; D-25)
- **FR-19** [Preserve] After an Account type change is saved, the Account type control is locked for 30 minutes and shows "Zmiana rodzaju konta jest zablokowana do: HH:MM". The lock lets background synchronisation finish before another change. (BR-14, r01, r04; D-27)
- **FR-20** [Correct] Only the User themself can change their Account type or Seller declaration. Admins and Super Admins cannot, so a newly created User is Private. (BR-16, F-12; D-27, D-30)
- **FR-21** [Correct] A saved Account type change is announced to other modules, with the User and the new Account type. It also creates a Journal entry of kind `users` for every member of the "Admins" group: "Użytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne", linking to the User. The change is recorded in the Change history.
  - All of this happens only after the save succeeds.
  - Users itself does not reprice or republish anything; other modules do that in reaction (§9).
  - V1 ran these effects before the save, kept a separate "Aktywność użytkowników" feed, and repriced only WooCommerce.

  (F-02, W-01, BR-04, Q-03, Q-20, r03, r06; D-26, D-28)

### 4.5 User management

- **FR-22** [Correct] The user list shows these columns:
  - name and e-mail, both searchable;
  - Account type: "Firmowe" green, "Prywatne" amber;
  - Role as a badge, or "nieznana rola" when the Role is not registered;
  - a "wymaga zmiany hasła" marker when set;
  - an inactive marker for Deactivated Users;
  - join date, sortable.

  It can be filtered by Role, Account type and active/inactive. A row opens the User's detail. V1 had no filters, and its Role badge crashed on an unknown Role. (F-10, Q-08, r01, r02; D-20, D-32, D-39, D-46)
- **FR-23** [Correct] The user detail shows:
  - name, e-mail, phone and join date;
  - company name, NIP, company address, pickup address, city and postal code;
  - Account type, with the declaration acceptance date and version when Company;
  - Role, and Extra Permissions (shown only to a Super Admin);
  - Otomoto shipping text;
  - active/inactive state and the password-change marker.

  It hosts the offer statistics and the "Powiązane oferty" tab supplied by Offers. It also shows the User's history from the Change history, read-only. V1 allowed deleting audit entries from this tab and showed neither the Account type nor the shipping text. (F-11, X-03, X-06, r01; D-05, D-32)
- **FR-24** [New / Correct] An Admin creates a User. The form has:
  - Role: User or Admin; Super Admin is never offered;
  - name, e-mail and phone;
  - company name, NIP, company address, pickup address, city and postal code;
  - Otomoto shipping text;
  - Extra Permissions, shown only to a Super Admin.

  The Admin chooses how the User gets a password:
  - **Send invitation:** the User receives a link to set their own password (WF-04). Available only while e-mail sending works.
  - **Set a temporary password:** with generator and copy button. This sets "password change required".

  V1 had only an admin-typed password and offered every Role. (F-12, BR-16, W-03, IMP-02, r01, r07; D-13, D-30, D-41, D-46)
- **FR-25** [Correct] An Admin edits a User with the same form.
  - Nobody edits a Super Admin's account from the user form, other Super Admins included; a Super Admin edits their own data in "Mój profil" (D-48).
  - The Role section is shown unless the editor is editing themself or the edited User is a Super Admin, whose Role changes only through the console. An Admin may change another Admin to User.
  - Setting a new password is always possible, but shown less prominently than the other fields. Doing so sets "password change required".
  - An empty password field keeps the current password.

  V1 hid the Role section for admins, so they could not be demoted, and let anyone with edit rights edit Super Admins. (F-13, BR-11, W-03, r01, r02; D-13, D-23, D-40, D-46)
- **FR-26** [New] From the user detail or edit page, an Admin can do the following, all within the limits of RULE-08:
  - re-send an invitation or password-reset link to a User, for example when the User lost the e-mail;
  - set "password change required" without changing the password;
  - clear that marker.

  (D-38, D-46)
- **FR-27** [Correct] An Admin can deactivate a User and reactivate a Deactivated User (WF-05). Nobody can delete a User from the panel. V1 deleted Users permanently and showed inert restore and force-delete actions. (F-14, W-04, BR-17, Q-08, Q-12, r04, r07; D-39)
- **FR-28** [Correct] Impersonation (WF-06).
  - A User holding the impersonation Permission can act as a User with a lower Role.
  - Super Admin holds it through the wildcard. An Admin holds it only as an Extra Permission.
  - Nobody can impersonate a Super Admin, and nobody can start impersonating while already impersonating.

  In V1, anyone who could see the user list could impersonate anyone, Super Admins included, with no record. (F-06, BR-09, Q-07, r02, r08; D-21, D-22)

### 4.6 Journal

- **FR-29** [Correct] Any module can add a Journal entry for a User. An entry has these fields:
  - **kind:** the area it concerns;
  - **severity:** info, success, warning or rejection;
  - **title** and **content:** content is plain text, line breaks kept;
  - **link** (optional): points to the entry's subject, for example an offer.

  An entry for a User who does not exist is not created, and the writing module's own operation still completes. A Deactivated User still receives entries, because they may be reactivated (D-47). V1 had a fixed offer-only shape with HTML content. (F-01, BR-01, BR-03, X-01, r03; D-03, D-16, D-17)
- **FR-30** [Preserve] A User sees their own Journal: only their own entries, newest first, with the unread count on the navigation item. Admins see only their own Journal too. The Journal can be filtered by kind and by severity. (F-01, r03; D-16, D-28, D-47)
- **FR-31** [Preserve] In the Journal, a User can:
  - mark an entry as read;
  - open an entry's details, which marks it read and shows title and content;
  - follow the entry's link;
  - delete one or many of their entries.

  Following a link to a subject that no longer exists shows a warning instead. Nobody can create or edit entries by hand, and nobody can delete another User's entries. (F-01, BR-02, r03; D-16)
- **FR-32** [Correct] Every new Journal entry also sends the User a panel notification. Opening it shows that specific entry and marks it read. The "go to subject" button is named after the entry's kind (kind `offer`: "Przejdź do oferty") and shows only when the entry has a link. In V1 the notification opened the whole list and the button always read "Przejdź do oferty". (Q-02, r03; D-16, D-18)
- **FR-33** [Correct] Entry content is always shown as plain text. Text that looks like HTML is shown as typed, never interpreted. V1 rendered content, including admins' free-text reasons, as raw HTML. (Q-01, r03; D-17)

### 4.7 Dashboard

- **FR-34** [Correct] The dashboard's first tile shows:
  - the User's initials avatar;
  - "Użytkownik" and the name;
  - "Konto firmowe" or "Konto prywatne";
  - a sign-out button;
  - a new "Edytuj profil" link.

  (F-19, r04, r09; D-33)

### 4.8 Shipping text for Otomoto

- **FR-35** [Preserve] An Admin can write a per-User shipping text in the user form ("Wysyłka", rich text). The helper reads: "Informacja wyświetli się w sekcji WYSYŁKA opisu otomoto dla ofert dotyczących tego użytkownika. Pozostaw puste aby użyć wysyłki z oferty/kategorii". Users provides the text to Otomoto (§9). (F-18, r01, r04; D-06, D-30)

## 5. Business rules and constraints

- **RULE-01** E-mail is required, must be a well-formed address, and must be unique among all Users, Deactivated ones included. This applies wherever User data is edited. (BR-15, BR-16, Q-13; D-29)
- **RULE-02** Name is required, at most 255 characters, and need not be unique. Phone is required in "Mój profil" and optional in the admin form. V1's no-"_" and unique-name constraints are dropped. (BR-15, BR-16; D-29)
- **RULE-03** A password has at least 8 characters and must be confirmed. The same rule applies wherever a password is set: profile, admin form, reset, invitation and forced change. An empty password field in the profile or edit form keeps the current password. (BR-15, BR-16; D-29, D-46)
- **RULE-04** For Account type Company, NIP and company address are required and the Seller declaration must be accepted. The declaration text: "Oświadczam, że jestem zarejestrowanym czynnym podatnikiem podatku VAT. Jednocześnie oświadczam, że dokonuje sprzedaży oferowanych towarów w ramach prowadzonej przeze mnie działalności gospodarczej." Each wording of the text has a version, and the version accepted is recorded with the date. (BR-15, BR-24; D-24)
- **RULE-05** A User is Company only when they chose Company and their declaration acceptance is recorded. Otherwise they are Private. (BR-12; D-24)
- **RULE-06** A Private User's offers are published with VAT added to their price. Account type affects price only, never visibility. The price rule itself belongs to Offers. (BR-13, Q-19; D-25)
- **RULE-07** The Account-type lock lasts 30 minutes from a saved change and binds only the User, who is the only one able to change the Account type. (BR-14; D-27)
- **RULE-08** Limits on managing other Users:
  - An Admin may create Users with Role User or Admin.
  - An Admin may edit, deactivate, reactivate and send links to Users and Admins, but not to Super Admins.
  - Nobody edits another Super Admin's data or password, sends them links or sets their password-change marker; a Super Admin's account is read-only to everyone else (D-48).
  - Nobody changes their own Role or deactivates themself.
  - Nobody deactivates a Super Admin from the panel, not even another Super Admin (D-47).
  - A Role declared by another module ranks below Admin unless that module declares otherwise, and that module sets who may assign it (D-47).

  (BR-11, BR-26; D-13, D-23, D-39, D-47)
- **RULE-09** The Super Admin Role is granted and revoked only through the console. (D-09)
- **RULE-10** Impersonation targets only a User whose Role ranks lower than the impersonator's, and never a Super Admin. Impersonations cannot be nested. (BR-09; D-22)
- **RULE-11** Only Super Admins grant, revoke or see Extra Permissions. Any registered Permission can be granted. (D-21)
- **RULE-12** A Journal entry is visible only to its owner. Entries do not expire. They remain until the owner deletes them; deactivating the User keeps them (D-39 replaces D-16's "deleted with the User", since Users are never deleted). (BR-02, F-01; D-16, D-39)
- **RULE-13** Sign-in attempts are rate-limited per e-mail and IP address. (D-37)
- **RULE-14** Password-reset and invitation links are single-use. A reset link expires after 60 minutes, an invitation link after 7 days. A reset request for an unknown or deactivated e-mail gets the same neutral response as a known one. Setting a password from either link signs the User in. (F-15; D-34, D-38, D-41, D-47)
- **RULE-15** "Password change required" is set only by an Admin action: a temporary password, a password change, or setting the marker by hand. It is cleared by the User setting a new password (forced-change screen, reset link or invitation link) or by an Admin clearing it. (D-46)
- **RULE-16** Settings and the activity log are guarded by Permissions held by Super Admin by default, never by a specific e-mail address. (BR-10, Q-06; D-14)

## 6. Workflows

### WF-01 Sign in

- **Actors / trigger:** a Guest submits e-mail and password on the panel sign-in page.
- **Preconditions:** none.
- **Main flow:**
  1. Check the rate limit (RULE-13).
  2. Verify the credentials.
  3. Check the User is active and their Role is registered (FR-07).
  4. Start a 120-minute session.
  5. If "password change required" is set, go to WF-07. Otherwise go to the dashboard.
- **Postconditions:** the User is signed in.
- **Alternative paths:** "Nie pamiętasz hasła?" leads to WF-03.
- **Errors and exceptional scenarios:**
  - Wrong credentials: a generic failure message, and the attempt counts against the limit.
  - Limit exceeded: refusal with retry time.
  - Deactivated User: refusal saying the account is inactive.
  - Role not registered: refusal per FR-07.
- **Trace:** (F-03, F-07, BR-23, r01, r02; D-10, D-20, D-37, D-39, D-46)

### WF-02 User changes Account type

- **Actors / trigger:** a User saves "Mój profil" with a different Account type.
- **Preconditions:** signed in; the Account-type lock is not active (RULE-07).
- **Main flow:**
  1. Validate the profile (RULE-01 to RULE-05).
  2. Save the profile, the Account type, and the declaration acceptance with date and version (Company) or its clearing (Private).
  3. Only after a successful save:
     - set the 30-minute lock;
     - announce the change to other modules;
     - add a `users` Journal entry for each member of "Admins";
     - record the change in the Change history.
  4. Show "Zapisano dane profilu".
- **Postconditions:** the new Account type applies at once wherever it is read. Other modules reprice and republish the User's offers in reaction.
- **Alternative paths:** choosing Private shows the "Pamiętaj!" VAT notice first, even if the User never saves.
- **Errors and exceptional scenarios:**
  - Validation fails: nothing is saved, no lock, no announcement.
  - The save fails: no effects at all.
  - A reacting module's failure does not undo the change. Handling it is that module's concern.
- **Trace:** (W-01, BR-04, BR-14, Q-03, Q-20, r06; D-24, D-26, D-27, D-28)

### WF-03 Password reset

- **Actors / trigger:** a Guest uses "Nie pamiętasz hasła?", or an Admin sends a reset link (FR-26).
- **Preconditions:** e-mail sending works.
- **Main flow:**
  1. The Guest enters an e-mail address.
  2. If it belongs to an active User, a link valid for 60 minutes is sent. The response is the same in every case.
  3. The User opens the link and sets a password (RULE-03).
  4. The User is signed in.
  5. Their other sessions end.
  6. "Password change required" is cleared.
- **Postconditions:** the new password is in effect.
- **Errors and exceptional scenarios:**
  - Expired or used link: a message offering to request a new one.
  - Deactivated User: no link is sent, and the response stays the same.
- **Trace:** (F-15, r04, r08; D-34, D-38, D-46)

### WF-04 Create a User

- **Actors / trigger:** an Admin or Super Admin submits the create form.
- **Preconditions:** the actor may create Users. The chosen Role is User or Admin (RULE-08).
- **Main flow:**
  1. Validate (RULE-01 to RULE-03, RULE-08).
  2. Create the User as Private and active, with the chosen Role and any Extra Permissions (Super Admin only).
  3. Either send the invitation link (7 days, single-use), or store the temporary password and set "password change required".
  4. Record the creation in the Change history.
- **Postconditions:** the User exists. They can sign in after setting a password from the invitation, or with the temporary password.
- **Alternative paths:** an invitation that expired or was lost can be re-sent (FR-26).
- **Errors and exceptional scenarios:**
  - Duplicate e-mail: a field error.
  - Invitation chosen while e-mail sending is unavailable: the option is not offered.
- **Trace:** (F-12, W-03, BR-16, r01, r07; D-13, D-30, D-41, D-46)

### WF-05 Deactivate and reactivate a User

- **Actors / trigger:** an Admin or Super Admin deactivates or reactivates a User from the user detail or edit page.
- **Preconditions:** RULE-08. The target is not the actor and not a Super Admin.
- **Main flow (deactivate):**
  1. Ask for confirmation.
  2. Mark the User inactive.
  3. End all their sessions at once.
  4. Announce "User deactivated" to other modules.
  5. Record it in the Change history.
- **Main flow (reactivate):**
  1. Mark the User active.
  2. Announce "User reactivated".
  3. Record it in the Change history.
- **Postconditions:** a Deactivated User keeps all their data, Journal, history and offers. Other screens show them as "konto nieaktywne". They cannot sign in or receive reset links.
- **Errors and exceptional scenarios:** a deactivated User who is being impersonated loses that session too, and the impersonator returns to their own account.
- **Trace:** (F-14, W-04, BR-17, Q-12, r04, r07; D-39)

### WF-06 Impersonate

- **Actors / trigger:** a User with the impersonation Permission chooses "Impersonate" on a User in the list or detail.
- **Preconditions:** RULE-10. The target is active and their Role is registered.
- **Main flow:**
  1. Record the start (who, whom, when) in the Technical log, area "bezpieczeństwo" (Audit D-23).
  2. Switch the session to the target.
  3. Show the dashboard with a banner and a return button.
- **Postconditions:** the impersonator sees and acts as the target. "Password change required" on the target does not redirect the impersonator (WF-07).
- **Alternative paths:** the return button records the end in the Technical log, area "bezpieczeństwo" (Audit D-23), and restores the impersonator's own session.
- **Errors and exceptional scenarios:** a target the rules exclude has no "Impersonate" action.
- **Trace:** (F-06, BR-09, Q-07, r02, r08; D-22)

### WF-07 Forced password change

- **Actors / trigger:** a User with "password change required" signs in or opens any panel page.
- **Preconditions:** signed in as themself, not by impersonation.
- **Main flow:**
  1. Show "Ustaw nowe hasło".
  2. The User enters a new password with confirmation (RULE-03). It must differ from the current one, and the current one is not asked for.
  3. Save the password.
  4. Clear the marker.
  5. End the User's other sessions.
  6. Clearing the "password change required" marker is recorded in the Change history like any data change (Audit D-23).
  7. Go to the dashboard.
- **Postconditions:** the User uses the panel normally.
- **Alternative paths:** sign out from the screen. Setting the password by reset link (WF-03) also clears the marker.
- **Errors and exceptional scenarios:**
  - New password same as current: a field error.
  - Any other panel page while flagged: redirect back to this screen.
- **Trace:** (ADD-01; D-40, D-46)

## 7. States and transitions

**User activity**

| From | To | Trigger | Guard | Effects |
|---|---|---|---|---|
| — | Active | Admin creates the User (WF-04) or the User is migrated | RULE-08 | Private Account type; invitation sent or "password change required" set |
| Active | Deactivated | Admin deactivates (WF-05) | RULE-08, not self | sessions end; "deactivated" announced; audited |
| Deactivated | Active | Admin reactivates (WF-05) | RULE-08 | "reactivated" announced; audited |

**Account type**

| From | To | Trigger | Guard | Effects |
|---|---|---|---|---|
| Private | Company | User saves profile | lock not active; RULE-04 | acceptance date and version stored; lock; announced; Admins' Journal entry; audited |
| Company | Private | User saves profile | lock not active | acceptance cleared; lock; announced; Admins' Journal entry; audited |

**Password change required:** off → on when an Admin sets a temporary password, changes the password, or sets the marker. On → off when the User sets a new password (WF-07, WF-03, invitation) or an Admin clears it.

**Journal entry:** unread → read when the owner marks it, opens its details, or opens its notification. Either state → gone when the owner deletes it.

## 8. Data requirements

**Information kept about a User:**
- name, e-mail, phone;
- company name, NIP, company address;
- pickup address, city, postal code;
- Otomoto shipping text (rich text);
- Account type, with the declaration acceptance date and text version;
- Role and Extra Permissions;
- active or deactivated;
- "password change required";
- join date;
- the password, stored only in a form from which it cannot be recovered.

**Other information:**
- **Account-type lock.** Kept with its expiry time. It need not survive longer than 30 minutes.
- **Journal entry.** Owner, kind, severity, title, content, optional link with label, read state, creation time.
- **Retention.** Nothing about a User is deleted by the panel, because deactivation keeps everything. Journal entries go only when their owner deletes them. Erasure on request is pending (ADD-04).

**Migration from V1** (D-38):
- **Migrate:** name, e-mail, phone, company data, pickup address, city, postal code, Otomoto shipping text and join date.
- **Role:** V1 User → User, V1 Admin → Admin. V1 Super Admins are granted Super Admin through the console after migration.
- **Account type:** Company if V1 had both the company flag and the declaration flag set. The acceptance date is the migration date and the version is "V1". Everyone else is Private.
- **Not migrated:**
  - password hashes;
  - two-factor data;
  - API tokens;
  - sessions;
  - remember tokens;
  - profile photos;
  - the "Aktywność użytkowników" feed rows.
- **Passwords after migration:** every migrated User receives an invitation to set a password. Until e-mail sending works, an Admin sets temporary passwords instead, which sets "password change required".
- **V1 Journal entries** are migrated as kind `offer` entries, with their HTML stripped to plain text (D-47).
- **Declaration version:** a new version whenever the wording changes; the V1 text is version "V1" (D-47).

## 9. Cross-module interactions

- **Offers:**
  - **Users provides:** the owner's name and contact data, the Account type (which drives the VAT price rule), active/inactive, and Permission checks.
  - **Offers reacts to:**
    - "Account type changed": it reprices and republishes the owner's offers in every channel;
    - "User deactivated": the effect on published offers is decided in Offers discovery.
  - **Offers writes:** Journal entries of kind `offer` for its lifecycle events: created, draft created, activated, accepted, rejected with reason, reserved, sold and archived.
  - **Offers supplies:** the offer statistics and the "Powiązane oferty" tab on the user detail. The sales-total question (Q-17) is handed to Offers.

  (X-01, X-03; D-03, D-05, D-26, D-39, D-42, D-43)
- **WooCommerce:** reacts to "Account type changed" by updating the owner's products' regular price. The "Użytkownik dla zaczytanych ofert" setting picks an active User through Users. V1's fallback to the first User is dropped, so the setting is required. (X-02, X-05; D-26, D-43)
- **Otomoto:**
  - reads the owner's shipping text for advert descriptions; with no text, its own default applies;
  - reacts to "Account type changed" by repricing the owner's adverts;
  - records which User started a bulk action.

  (X-04; D-06, D-26, D-43)
- **Audit:** Users registers the User as an auditable record, so the Change history holds every data change of a User:
  - creation, edits, deactivation and reactivation;
  - Account type changes;
  - Extra Permission grants and revocations (relation changes);
  - setting and clearing the "password change required" marker. The password itself is not audited.

  Impersonation start and end are not data changes. They go to the Technical log, area "bezpieczeństwo", together with sign-in, failed sign-in, sign-out and password reset. Audit shows the User's history read-only on the user detail. Every change entry carries its Actor. (X-06; D-21, D-22, D-28, D-32, D-46; amended by Audit D-14, D-21, D-23, D-27 on 2026-11-02)
- **Settings:** its screens are guarded by Permissions held by Super Admin by default. (X-07; D-14)
- **Submissions / inquiries and any module notifying staff:** use the "Admins" recipient group. (X-08; D-12)
- **Every module:**
  - declares its Roles and Permissions, and the grants of its Permissions to Roles;
  - asks Users whether a User holds a Permission;
  - may write Journal entries.

  (D-02, D-03, D-19)
- **Panel notification channel** (shared, not owned by Users): Users sends through it the notification for each new Journal entry. (D-04, D-16)

## 10. External integrations

- **E-mail.** Used for password-reset and invitation links.
  - Production has no e-mail sending today; it will be configured later.
  - While it is unavailable, the invitation option is not offered and Admins set temporary passwords.
  - A link that fails to send is reported to the Admin who requested it, or shown to the Guest as the usual neutral response.

  (G-12; D-34, D-40, D-41)
- **No other external system** is reached by Users. Repricing in WooCommerce and Otomoto happens in those integrations, in reaction to Users' announcements. (D-26)

## 11. Edge cases

- A User's Role belongs to a module that is no longer installed → the User cannot sign in. The list shows "nieznana rola". An Admin can assign a registered Role. (D-20)
- An Admin opens a Super Admin → the account is shown read-only. Edit, deactivate, impersonate and send-link actions are absent. (D-23)
- An Admin tries to change their own Role or deactivate themself → the controls are absent. (D-23, D-39)
- A User changes the Account type twice within 30 minutes → the control is disabled and shows the unlock time. (D-27)
- A Company User switches to Private and back → the declaration must be accepted again, and a new date and version are recorded. (D-24)
- A Journal entry links to an offer that has since been removed → following the link shows a warning, and the entry stays. (D-16)
- A module writes a Journal entry for a Deactivated User → the entry is created as usual. (D-16, D-39, D-47)
- A Super Admin opens another Super Admin → the account is shown read-only, as for an Admin. (D-23, D-47, D-48)
- An impersonated target is flagged "password change required" → the impersonator is not redirected and cannot change the target's password. (D-46)
- A flagged User resets their password by e-mail → the flag clears. (D-46)
- A Deactivated User requests a reset link → none is sent, and the response is the usual neutral one. (D-39)
- A User loses or deletes their invitation e-mail → an Admin re-sends it. (D-38)
- An Admin who can impersonate is deactivated mid-impersonation → both sessions end. (D-39)

## 12. Differences from V1

| V1 behaviour | V2 behaviour | Class | Reason | Trace |
|---|---|---|---|---|
| Roles and permission sets editable in the panel; built-in Roles deletable | Roles and grants defined in code only, declared by modules | correct | Code depends on Roles; removing V1's mess | D-08, D-19 |
| Super Admin had only seeded permissions | Super Admin passes every check | correct | Future modules work without reseeding | D-09 |
| Anyone with user rights could grant Super Admin | Super Admin only through the console | correct | Privilege escalation | D-09, D-13 |
| Only Super Admin and User Roles could enter the panel; Admin-only accounts were locked out | Any registered Role enters | correct | V1 bug | D-10 |
| Admin behaviour decided by Role name | Every behaviour checks a Permission | correct | Custom Roles must work | D-12 |
| Settings, roles and activity log limited to one hard-coded e-mail | Permission held by Super Admin | correct | No personal e-mail in rules | D-14 |
| No per-User permissions | Extra Permissions granted by Super Admin | new | One-off rights such as impersonation | D-21 |
| Anyone with the user list could impersonate anyone, unlogged | Permission-based, lower Roles only, never Super Admin, audited | correct | Security | D-22 |
| Admin roles could not be demoted; Super Admins editable by any user manager | RULE-08 limits | correct | Security | D-13, D-23 |
| Company = two loosely used flags; declaration kept as yes/no | One Account type choice; acceptance date and version kept | correct | Consistency, evidence of consent | D-24 |
| Private notice claimed offers hidden from the public | Notice says only that VAT is added | correct | It was misleading | D-25 |
| Account-type effects ran before the save; only WooCommerce repriced | Announced after the save; every module reacts | correct | Consistency | D-26 |
| Separate "Aktywność użytkowników" feed plus bell | `users` Journal entries for Admins | correct | One place for events | D-28 |
| Journal fixed to offers, HTML content, notification opened the list | General Journal, plain text, notification opens the entry | correct | Reuse; HTML injection | D-03, D-16..D-18 |
| Profile e-mail unchecked; admin name unique and without "_" | One validation rule | correct | Duplicate e-mails broke saving | D-29 |
| Second hidden profile editor | Removed | exclude | Duplicated and broken | D-31 |
| No filters; Role badge crash; audit entries deletable from user detail | Filters; "nieznana rola"; read-only history | correct | Usability, audit integrity | D-20, D-32 |
| Open self-registration with panel access | Admin-created accounts only | correct | Security incident | D-34 |
| Password reset only at a hidden address | Link on the sign-in page | correct | Usability | D-34 |
| Admin types a password and hands it over | Invitation or temporary password with forced change | new | No shared passwords | D-41, D-46 |
| Hard delete leaving orphaned offers | Deactivation and reactivation | correct | Keeps history; V1 screens broke | D-39 |
| Panel sign-in not rate-limited | Rate-limited | new (part of correct) | Security | D-37 |
| E-mail verification, API tokens, `/api/user`, Jetstream profile page, self-deletion, unenforced 2FA | Absent | exclude | Unused or ineffective | D-34, D-35, D-36 |
| "test" printed on every panel page | Absent | exclude | Defect | D-42 |

## 13. Approved additional functionality

### ADD-01 Forced password change — D-46

- **FR-36** [New] A User can be marked "password change required". The marker is set automatically when an Admin sets a temporary password at creation (FR-24) or changes the User's password (FR-25). An Admin can also set it, or clear it, without changing the password (FR-26). It is never set when the User sets their own password. (D-40, D-46)
- **FR-37** [New] While the marker is set, every panel page after sign-in leads to "Ustaw nowe hasło". There the User can only set a new password or sign out (WF-07). (D-46)
- **FR-38** [New] The new password follows RULE-03, must differ from the current one, and does not require the current one. Saving it does the following:
  - clears the marker;
  - ends the User's other sessions;
  - clears the marker, which the Change history records as a data change (Audit D-23);
  - leads to the dashboard.

  (D-46)
- **FR-39** [New] The user list and detail show "wymaga zmiany hasła" while the marker is set. (D-46)
- **Edge cases:**
  - A reset by e-mail clears the marker.
  - An impersonator is neither redirected nor able to change the password.
  - Super Admins are subject to the marker.
  - A Deactivated User keeps the marker for reactivation.

  (D-46)
- **Out of scope:** periodic password expiry, onboarding steps (ADD-02), and an e-mail notice about the marker. (D-46)

## 14. Exclusions and out of scope

- **Panel notification bell.** A shared panel channel; Users only sends through it. (D-04)
- **Offer statistics, the user's offers tab, the dashboard's offer widgets and the language switch.** Owned by Offers or the panel. (D-05)
- **Cleaning up V1 after the October 2026 security incident.** An operational task. (D-07)
- **The role-management screen.** Roles are code-only. (D-08)
- **The second profile editor (`profile/{record}` in V1).** (D-31)
- **Self-registration, e-mail verification, API tokens, the Jetstream profile and session pages, and self-deletion.** (D-34)
- **Two-factor authentication** as a requirement, and migration of V1's 2FA data. (D-35)
- **The current-user API endpoint.** (D-36)
- **Hard delete of Users from the panel.** (D-39)
- **Leftovers handed elsewhere or dropped.** The sales total at current price goes to Offers. The "test" page text is dropped. Settings-configured Journal texts are dropped. (D-42)

## 15. Pending decisions

- **D-45** ADD-02: an onboarding wizard on first sign-in, covering the password, profile data and Account type. Options: build it later or never. It blocks nothing; it would build on FR-36..FR-39.
- **D-45** ADD-03: optional two-factor authentication that a User enables in their profile. Options: add it later or never. It blocks nothing.
- **D-45** ADD-04: erasure of a Deactivated User's personal data on request, keeping history and offers. Options: add it later or never. It blocks nothing; it would build on WF-05.
- **Handed to Offers discovery:** what happens to a Deactivated seller's published offers. Users only announces the deactivation. (D-39)

**D-48** (2026-11-02, after QA): another Super Admin's account is read-only to every User; it replaces the D-23 permission for Super Admins to edit each other's data and passwords.

Details beyond the grilling decisions raised by the spec audit (r11) were confirmed by the user as D-47 (A-01..A-12, with A-10 rejected: nobody deactivates a Super Admin from the panel).

## 16. Traceability

| Spec item | Discovery items | Reports | Decisions |
|---|---|---|---|
| FR-01, FR-02 | F-04, BR-05, BR-06, Q-05, Q-14 | r02, r05 | D-02, D-08, D-19 |
| FR-03 | BR-07, BR-08, BR-22 | r02, r05 | D-09, D-12, D-21 |
| FR-04, FR-05 | F-05, BR-18, BR-26, Q-04, Q-09, IMP-04 | r02, r07 | D-09, D-10 |
| FR-06, RULE-11 | — (new) | — | D-21 |
| FR-07, WF-01 | F-03, F-07, Q-04, Q-08 | r01, r02 | D-10, D-20, D-37, D-39 |
| FR-08..FR-10, RULE-13 | F-07, BR-23 | r01, r04 | D-37 |
| FR-11, FR-12, WF-03, RULE-14 | F-15, BR-27, W-05, Q-10, IMP-01, IMP-03 | r04, r08 | D-34, D-38 |
| FR-13..FR-16, RULE-01..RULE-03 | F-08, F-19, BR-15, BR-16, W-02, Q-13, IMP-11 | r01 | D-29, D-30 |
| FR-17..FR-21, WF-02, RULE-04..RULE-07 | F-02, F-09, BR-04, BR-12, BR-13, BR-14, BR-24, W-01, Q-03, Q-11, Q-19, Q-20, IMP-09, IMP-10 | r01, r03, r04, r06 | D-24..D-28 |
| FR-22, FR-23 | F-10, F-11, Q-08, X-06 | r01, r02 | D-05, D-20, D-32 |
| FR-24, FR-25, WF-04, RULE-08 | F-12, F-13, BR-11, BR-16, BR-26, W-03, IMP-02, IMP-05, IMP-14 | r01, r02, r07 | D-13, D-23, D-30, D-40, D-41 |
| FR-26 | G-08 | — | D-38, D-46 |
| FR-27, WF-05 | F-14, W-04, BR-17, Q-08, Q-12, IMP-08 | r04, r07 | D-39 |
| FR-28, WF-06, RULE-10 | F-06, BR-09, Q-07, IMP-07 | r02, r08 | D-21, D-22 |
| FR-29..FR-33, RULE-12 | F-01, BR-01..BR-03, Q-01, Q-02, Q-15, G-01, X-01, IMP-12 | r03 | D-03, D-16..D-18, D-28 |
| FR-34 | F-19 | r04, r09 | D-33 |
| FR-35 | F-18, X-04 | r01, r04 | D-06, D-30 |
| RULE-09 | BR-26 | r07 | D-09 |
| Audit defaults in FR-09, FR-11, FR-22, FR-29, FR-30, RULE-08, RULE-13, RULE-14, WF-05, §2, §8, §11 | — | r11 | D-47 |
| RULE-15, FR-36..FR-39, WF-07 | ADD-01, G-08 | — | D-40, D-46 |
| RULE-16 | BR-10, Q-06, IMP-06 | r02 | D-14 |
| §8 migration | G-03, G-08, Q-16 | r01, r04 | D-35, D-38 |
| §9 | X-01..X-08, G-10 | r03..r06 | D-03..D-06, D-12, D-26, D-39, D-43 |
| §14 | F-16, F-17, F-20..F-22, Q-16..Q-18, G-09, IMP-13 | r01, r04, r08, r09 | D-04, D-05, D-07, D-31, D-34..D-36, D-42 |
