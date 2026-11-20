# 09 - Glass model picker slide-over and in-place creation (for Offers)

Status: resolved
Blocked by: 08

Spec: FR-19 (amended 2026-11-20), FR-20, WF-06, WF-01 (from approval). Decisions: D-17, D-19, D-38, D-52, D-57, D-67. Offers spec: FR-02, FR-10, FR-14 (`.scratch/offers/functional-spec.md`).

## What

- A reusable **Glass model picker**: a slide-over holding the catalogue card list with its search and filters (the same cards as the admin list, issues 06 and 11), in a seller variant (no "N ofert", no withdrawn models) and an admin variant. The user marks one card and confirms at the bottom; the picker hands the chosen Glass model id back to the form or action that opened it. It can open prefilled with a search text (Offers' identification hint).
- **In-place creation** for admins from the picker (FR-20): create a Glass model (WF-01) with the main Eurocode suggested from the prefill text, normalised by RULE-03, for the admin to confirm or correct; the new model is returned as the choice.
- Catalogue still knows nothing of Offers: the picker is a UI element of Catalogue that Offers' form and actions embed. A small demo use (e.g. on a styleguide or test page) is enough to test it before Offers exists.
- Moved out of this issue (2026-11-20): the in-use guard and the "N ofert" link → Offers issue 11.

## Done when

- Seller variant hides withdrawn models and counts; confirming returns the id; prefill searches; admin in-place creation returns the new model and refuses a taken Eurocode naming its model.
- Livewire tests for the picker; `composer check` passes.

## Comments

- 2026-11-20 (Offers grilling): the Offers spec is written (`.scratch/offers/functional-spec.md`). Inputs for triage: the picker is a **slide-over** with the catalogue card list (Catalogue D-67, FR-19 amended; Offers D-38, FR-02); in-place creation during approval (Offers FR-10, D-44); "N ofert" counts Offers outside the Trash in every status and links to the filtered offer list (Offers FR-33, D-48); the in-use guard counts every Offer Offers keeps, Trash included (Offers FR-25, D-43). Offers also needs bulk "change Glass model" (Offers FR-14), which uses the picker. No new Catalogue module interface needed beyond §9.

### Worker — 2026-11-20
- **Built:** the Glass model picker (FR-19, FR-20, WF-06) as a Catalogue UI element, in `UserInterface/Filament/GlassModelPicker/`:
  - `GlassModelPicker` (Livewire component `catalogue-glass-model-picker`): the card list of issue 11 (`GlassModelsTable::columns()`, now public, plus `candidates()` and `GlassModelsTableFilters::make(withWithdrawnToggle:)`), search and filters; a click on a card marks it (click again clears); marked row styled in `glass-card.css`. Seller variant: only active models, no withdrawn toggle, no creation, no "N ofert"; Admin variant: toggle for withdrawn, "Utwórz nowy model szkła".
  - In-place creation (FR-20): `Actions/CreateGlassModelInPlaceAction` sends `CreateGlassModelCommand` with `CurrentActor`; main Eurocode suggested from the search/prefill text through the new ModuleApi `EurocodeSuggestions` (over new `Eurocode::normalise()`, RULE-03); a taken code shows `eurocode_is_taken` (naming the holder) on the field; the new model is marked and searched.
  - Tests: `tests/Feature/.../GlassModelPicker/GlassModelPickerTest.php` (variants, marking, prefill, creation, refusal, slide-over host), `EurocodeSuggestionsTest`, `EurocodeTest`.
- **Seam for Offers 01/03/09 (document):**
  1. **Slide-over from any action** (Offers form "Wybierz model szyby", approval, table action, bulk "Zmień model szyby", FR-14): `GlassModelPickerSlideOver::configure(Action::make(...), search: <string|Closure hint>, variant: <PickerVariant|Closure|null>)->action(fn (array $data) => $data[GlassModelPickerSlideOver::STATE_KEY] /* 'glass_model_id' */)`. Works on `BulkAction` too (`fn (Collection $records, array $data)`). Show the current choice marked with `->fillForm([STATE_KEY => $id])`. Confirm is the modal's submit button at the bottom; "required" is validated.
  2. **Inline in a form/modal**: `GlassModelPickerField::make('glass_model_id')->search($hint)->variant(...)`; state is the id or null.
  3. **Raw component**: `<livewire:catalogue-glass-model-picker wire:model="x" :prefill="..." :variant="'seller'|'admin'" />` (`#[Modelable] $selectedId`).
  - Default variant = Admin for a user holding `catalogue.manage`, else Seller; an Admin variant is never honoured for a non-admin (locked + re-checked). The id is client state: Offers' command must verify the model exists and is not withdrawn (a seller must not pick a withdrawn one). The selected model's card shown in the Offers section comes from `GlassModelSummaries` (08). Catalogue imports nothing of Offers.
  - The "N ofert" link in the admin variant is not filled here (moved to Offers 11); Offers can later add its own slot/closure to the picker, nothing is built for it now.
- **Deviations:** (1) Demo/test of the seam is a fixture host component in the tests (`Fixtures/PickerHost`), not a styleguide section. (2) Livewire tests cannot run the browser side of `wire:model` (child to parent sync in the slide-over); the host test covers the action receiving `$data['glass_model_id']`, the picker component is tested alone. Needs a hand check. (3) In-place creation asks only for main Eurocode, position (required) and colour; the rest (OEM codes, equipment, vehicles) is edited on the model's page afterwards. (4) Added ModuleApi `EurocodeSuggestions` and `Eurocode::normalise()` because the UI may not touch Domain value objects. (5) No screenshots (instructed); card CSS width in the slide-over (`SevenExtraLarge`) unchecked visually.
- **Seams left:** Offers 01/03/09/11 as above; admin "N ofert" link slot.
- **Open questions:** should the Admin variant offer the "with withdrawn" toggle at all (FR-19 only says sellers never see withdrawn)? Kept it for admins, as on the list.
- **Check:** composer check green (1082 tests); `npm run build` done.

### Review — 2026-11-20
- **Verdict:** approved after 0 fix rounds
- **Notes:** seam for Offers is `GlassModelPickerSlideOver::configure()` (actions and bulk actions, id in `$data['glass_model_id']`), `GlassModelPickerField`, or the raw `catalogue-glass-model-picker` component. Offers' commands must re-check that the id exists and is not withdrawn (client state). Accepted: in-place creation asks for Eurocode, position and colour only; admin variant keeps the "with withdrawn" toggle (off by default) — open for the user. Browser-side `wire:model` sync in the slide-over needs a hand check. `npm run build` needed (glass-card.css).
