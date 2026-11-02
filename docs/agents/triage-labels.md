# Triage Labels

The skills speak in terms of five canonical triage roles. This repo tracks issues as local markdown files (see `issue-tracker.md`), so a role is **not** a label applied via a CLI — it is the value of the `Status:` line near the top of `.scratch/<feature-slug>/issues/<NN>-<slug>.md`.

| Role in mattpocock/skills | `Status:` value in our issue files | Meaning                                  |
| ------------------------- | ---------------------------------- | ---------------------------------------- |
| `needs-triage`            | `needs-triage`                     | Maintainer needs to evaluate this issue  |
| `needs-info`              | `needs-info`                       | Waiting on reporter for more information |
| `ready-for-agent`         | `ready-for-agent`                  | Fully specified, ready for an AFK agent  |
| `ready-for-human`         | `ready-for-human`                  | Requires human implementation            |
| `wontfix`                 | `wontfix`                          | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), write the corresponding value to that issue file's `Status:` line — adding the line if it isn't there yet:

```markdown
# 03 - Extract AuditLog repository

Status: ready-for-agent
```

Edit the middle column to match whatever vocabulary you actually use.

Note: `/wayfinder` reuses the same `Status:` line for its own two values, `claimed` and `resolved`. A wayfinder ticket carries a `Type:` line alongside it — when you see one, read `Status:` as the wayfinder vocabulary, not the triage roles above.
