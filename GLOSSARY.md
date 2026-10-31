# AllGlass Market

A panel where AllGlass staff and external sellers list used glass for sale, and where AllGlass runs the sale through its shop and marketplaces.

## Users and access

**User**:
A person with an account in the panel. AllGlass staff and external sellers are both Users and differ only by Role.
_Avoid_: account (for the person), customer, member

**Deactivated User**:
A User who can no longer sign in but whose data, history and offers remain. A User is never deleted from the panel.
_Avoid_: deleted user, archived user, banned user

**Role**:
A named set of Permissions held by a User, defined in code by a module. Each User holds exactly one Role.
_Avoid_: group, tier

**Super Admin**:
The Role that passes every Permission check. It can be granted only outside the panel.
_Avoid_: root, owner

**Permission**:
The right to perform one kind of action. Every module declares its own Permissions, and they are granted to Users through Roles.
_Avoid_: ability, gate, policy

**Extra Permission**:
A Permission granted to one User beyond their Role, only by a Super Admin.
_Avoid_: bonus permission, direct permission

**Account type**:
Whether a User sells as Private or Company. A User is Company only after accepting the seller declaration.
_Avoid_: rodzaj konta (in code and docs), company flag

**Seller declaration**:
The User's statement that they are an active VAT payer selling as part of a business. Required for a Company account type.
_Avoid_: oświadczenie (in code and docs), terms

**Journal**:
A User's personal record of things that happened to their affairs, written by any module and read only by that User.
_Avoid_: inbox, dziennik (in code and docs), notifications

**Journal entry**:
One message in a Journal. It has a kind, which names the area it concerns (for example "offer").
_Avoid_: notification, message
