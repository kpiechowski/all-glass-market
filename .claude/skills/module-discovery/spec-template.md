# <Module> — functional spec

<!-- The authoritative functional reference for implementing <Module> in V2.
     It describes behaviour, not implementation: no classes, tables, patterns or framework terms,
     except inside legacy traces. Data is described as information the business needs, not as schema.
     Every requirement carries a trace: (F-/BR-/W- ids, rNN; D- decision). -->

- **Agreed:** <YYYY-MM-DD> · **Discovery:** `.scratch/<module>/discovery/discovery.md` · **V1 baseline:** `<sha>`
- **Decision counts:** preserve <n> · correct <n> · new <n> · exclude <n> · pending <n>

## 1. Purpose and responsibilities

<What the module is for, and what it owns and does not own.>

## 2. Domain concepts

<Each term the module uses. Point to GLOSSARY.md entries rather than redefining them; define here only what is module-local.>

## 3. Actors and permissions

| Actor | May | May not | Trace |
|---|---|---|---|

## 4. Functional requirements

<!-- Grouped by functional area. Class: Preserve | Correct (state what changes vs V1) | New -->

### <Area>

- **FR-01** [Preserve] <the actor or system can …, when …, resulting in …> — (F-01, BR-02, r03; D-04)

## 5. Business rules and constraints

- **RULE-01** <rule> — (BR-01, r02; D-05)

## 6. Workflows

### WF-01 <operation>

- **Actors / trigger:**
- **Preconditions:**
- **Main flow:** 1. … 2. …
- **Postconditions:**
- **Alternative paths:**
- **Errors and exceptional scenarios:** <condition → expected behaviour>
- **Trace:** (W-01, r04; D-06)

## 7. States and transitions

<Each state, the allowed transitions, who or what triggers them, the guards, and the effects. A table or list; no diagrams of code.>

## 8. Data requirements

<The information that must be captured, retained, derived or deleted, and when. Constraints on migrating existing V1 data: which V1 data must survive and with what meaning.>

## 9. Cross-module interactions

- **<Module>:** <what this module needs from it, or provides to it, in business terms> — (X-01; D-)

## 10. External integrations

<The external systems involved, what must happen and when, and the expected behaviour when they fail.>

## 11. Edge cases

- <case → expected behaviour> — (trace)

## 12. Differences from V1

| V1 behaviour | V2 behaviour | Class | Reason | Trace |
|---|---|---|---|---|
| | | correct / exclude / new | | D- |

## 13. Approved additional functionality

<Each approved ADD- item, specified to the same depth as section 4 and 6, each with its D- decision. Or: "None — D-nn".>

## 14. Exclusions and out of scope

- <excluded behaviour or capability> — <why> (D-)

## 15. Pending decisions

- **D-nn** <question> — <options>; blocks <FR/WF ids>

## 16. Traceability

| Spec item | Discovery items | Reports | Decisions |
|---|---|---|---|
