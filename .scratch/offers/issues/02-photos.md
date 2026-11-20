# 02 - Public and private photos

Status: resolved
Blocked by: 01

Spec: FR-05 (photos), §8 Offer photos. Decisions: D-29, D-49.

## What

- Up to **10 public photos**, ordered; the first is the main photo; the order changes by dragging.
- Up to **10 private photos**, visible only to the owner and admins, never part of anything published.
- Photos travel in command data (golden rule 5); the form section "Zdjęcia" and the gallery on the view page.
- Public photos are part of the Offer's data that 03's re-approval rule watches; private photos are not.

## Done when

- Limits refused above 10 per kind; order kept; a seller cannot see another Offer's private photos.
- Handler and Livewire tests; `composer check` passes.

### Worker — 2026-11-20
- **Built:** FR-05 photos. `OfferPhotos` VO (up to 10 public ordered + 10 private, no repeats, no `..`/absolute paths); `Offer::arrangePhotos()` (a new path must sit in the uploader's own folder `offers/{public|private}/{userId}/`, one already on the Offer stays whoever saves, so nobody can name another user's private file as their own; rule in `OfferDomainPolicy::photosAreFromUploader`). `CreateOffer`/`UpdateOffer` commands carry `publicPhotos`/`privatePhotos` path lists (Update: null keeps that kind); migration adds json `public_photos` / `private_photos` (the public column is the data 03's re-approval rule watches, the private one is not). Form sections "Zdjęcia" (public disk, reorderable by dragging, first is main) and "Zdjęcia prywatne" (`local` disk, no URL); view page galleries with a "Główne" badge. Private photos are served only by `PrivatePhotoController` (`/admin/offers/{offer}/private-photos/{position}`, auth + owner or ManageAll, 404 otherwise, named by position not path). Audit: both fields registered with new `ValuePresenter::fileCount()` ("3 pliki") in Audit's ModuleApi + test.
- **Deviations:** (1) Removing a file in the upload field does not delete it from disk (`deleteUploadedFileUsing` no-op), and replaced/removed files stay as orphans; cleanup belongs to FR-26 permanent delete / a later job. (2) History shows a photo change as a count, so a pure reorder shows "3 pliki -> 3 pliki". (3) Photos stored as json columns on `offers` (no photo entity/table) since they are few, never edited alone. (4) Folder names and the limit constant live on `OfferDomainPolicy` because UserInterface may not depend on Domain value objects (deptrac). (5) `Offer::reconstitute` takes a new required `OfferPhotos` argument.
- **Seams left:** 03 reads `Offer::photos()->public` (column `public_photos`) for re-approval, and the FR-06 "one public photo" send check; FR-26 file removal; adverts' public photo list.
- **Open questions:** none
- **Check:** composer check green (1198 tests; copied the gitignored `public/build` into the worktree for Vite-dependent tests)

### Review — 2026-11-20
- **Verdict:** approved after 0 fix rounds
- **Notes:** photos are ordered path lists in json columns on `offers` (`public_photos` on the `public` disk, `private_photos` on `local`, no URL). The entity accepts a new path only from the uploader's own folder (`offers/{public|private}/{userId}/`), so nobody can claim someone else's file. Private photos are served only by `PrivatePhotoController` (route `offers.private-photo`, by position, 404 for anyone but owner/admin). History shows photo changes as a count (Audit `ValuePresenter::fileCount()`), so a pure reorder reads "3 pliki → 3 pliki". Removed/replaced files stay on disk as orphans until FR-26 (permanent delete) or a cleanup job. Seam for 03: `Offer::photos()->public`.
