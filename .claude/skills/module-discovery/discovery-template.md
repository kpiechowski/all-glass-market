# <Module> — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** scoping | investigating | ready-for-grilling | grilling | grilling-additions | spec-written | done
- **Next actions:** <what the next session does first>
- **Started:** <YYYY-MM-DD> · **Last updated:** <YYYY-MM-DD>
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `<sha>` (<clean | dirty: files>)
- **V2 state:** <module absent | exists in app/Modules/<Name>, predates this discovery>
- **Prior material:** <archive scan path and others, treated as leads>

### Scope

- **Apparent responsibility:** <one or two lines>
- **Anchors:** <files>
- **Vocabulary:** <class names, tables, columns, morph aliases, routes, translation keys, Polish labels, config and setting keys, enum values>
- **Boundary assumptions:** <from the archive boundary table, and why>
- **Out of scope:** <what, and which module owns it>
- **Limitations:** <for example: no V1 runtime or production data access; findings come from static reading>

## B. Module overview

<What the module is responsible for, its main business concepts, its apparent boundaries, and its relationships with other modules. Refined as waves return.>

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| F-01 | | | W-01 / n/a: <reason> | spot / r05 / — | high/med/low | Q-02 |

### F-01 <name in business words>

- **Purpose:** <why it exists for the business>
- **Description:** <what an actor or the system can do, and the result>
- **Actors and permissions:**
- **Entry points:** <kind: path>
- **Workflows · rules · dependencies:** W-, BR-, X-
- **Evidence:** r01, r02, plus key `path:line`
- **Status:** mapped | traced | verified · **Confidence:** <level, and why>

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** <rule> — applies to F-; evidence

### Inferred or questionable

- **BR-02** <rule> — `inferred` because <reason>; see Q-

### Domain terms

<Terms as V1 uses them, including Polish labels, and any conflicts with GLOSSARY.md.>

## E. Functional workflows

### W-01 <operation>

- **Trigger / entry points:**
- **Preconditions:**
- **Main flow:** 1. … 2. …
- **State changes:** <data before and after, status transitions>
- **Side effects and external interactions:**
- **Error scenarios:**
- **Alternative paths:** <other entry points, and how they differ>
- **Evidence:** rNN; trace stopped at <point>, because <reason>

## F. Cross-module dependencies

- **X-01** <module or integration> — <direction and business nature of the dependency>; evidence

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 <title> — `ambiguous` | contradiction | `defect?`

- **Evidence:**
- **Interpretations:** a) … b) …
- **Question for the user:**
- **Resolution:** open | D-

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-01 | | not-found / not-investigated / undeterminable / needs-runtime / needs-business | | open / rNN / D- |

## I. Potential improvements

<!-- Changes to existing behaviour. Not requirements until a D- decision approves them. -->

- **IMP-01** <title> — <current behaviour (F-/W-)> → <proposal>; value: …; decision: open | D-

## J. Additional functionality to implement

<!-- New capabilities that go beyond V1. Never requirements without a D- decision. Write "none identified yet" if empty. -->

### ADD-01 <name>

- **Description:**
- **Value:**
- **Kind:** entirely new | extends F-
- **Dependencies:**
- **Open questions:**
- **Source:** discovery (rNN) | manager | user
- **Discussed:** no | yes (date) · **Decision:** open | D-

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | | dispatched / returned / weak→rNN | | | | spot: BR-03 |

## L. Completeness assessment

<!-- Written at the Phase 5 gate. -->

- **Exit criteria:** 1 ✓ … 8 ✓ (note any accepted exceptions)
- **Mapped:** <counts>
- **Verified:** <what has a second source>
- **Still uncertain:** <Q- and G- ids>
- **Weak coverage:** <areas and why>
- **Ready because:** <justification>

## M. Decision log

<!-- Written during grilling. Class: preserve | correct | new | exclude | pending -->

- **D-01** (<date>) re <ids> — <decision>. Class: <class>. Rationale: <user's reasoning>.
