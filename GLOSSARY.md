# AllGlass Market

A panel where AllGlass staff and external sellers list used glass for sale, and where AllGlass runs the sale through its shop and marketplaces.

## Users and access

**User**:
A person with an account in the panel. AllGlass staff and external sellers are both Users and differ only by Role.
_Avoid_: account (for the person), customer, member

**Role**:
A named set of Permissions held by a User.
_Avoid_: group, tier

**Permission**:
The right to perform one kind of action. Every module declares its own Permissions, and they are granted to Users through Roles.
_Avoid_: ability, gate, policy

**Journal**:
A User's personal record of things that happened to their affairs, written by any module and read only by that User.
_Avoid_: inbox, dziennik (in code and docs), notifications

**Journal entry**:
One message in a Journal. It has a kind, which names the area it concerns (for example "offer").
_Avoid_: notification, message
