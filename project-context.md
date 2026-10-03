# Cash Afandy — Project Context

Read this when porting code from the old repo, working from the design, or touching translations and UI. The short rules live in `AGENTS.md`.

## What It Is

Cash Afandy is a personal product (not client work): cashbacks, vouchers, and coupons, plus expense management tools. Favor clean, maintainable code and strong UI/UX polish over quick hacks.

## Old Repo (Waffrlly)

`C:\Users\ahmed\Desktop\waffrlly` is the previous version of this project (Laravel 11, same Redot scaffold).

- Port only domain logic:
    - Models: `Broker`, `Cashback`, `Category`, `Client`, `Coupon`, `Language`, `LanguageToken`, `News`, `Post`, `Representative`, `Setting`, `Slider`, `UserPreference`.
    - Their related Livewire tables and controllers.
- Don't copy `redot/core` scaffold files that already exist here:
    - `Admin`, `Country`, `Memo`, `ShortenedUrl`, `User`, and the base Http/Livewire structure.
    - This repo is on Laravel 13 and a newer `redot/core`, so the base versions differ. Check for conflicts first.

## Design Reference

[Figma file](https://www.figma.com/design/dT8q5Wko4Kus947BM3Mm02/Waffrlly?node-id=62-249&t=YNPsJ33ChpKbXkfC-1). It is still titled "Waffrlly" in Figma.

## UI Requirements

- Every view must be fully responsive (mobile, tablet, desktop).
- Every view must support both RTL (Arabic) and LTR (English), matching `lang/ar.json` and `lang/en.json`.
- Use logical CSS properties (`margin-inline-start`, not `margin-left`) and responsive utility classes instead of hardcoded directions or pixel values.

## Translations

Redot tracks language tokens in the database, so `lang:publish` rewrites the json files from that state.

1. Add `__('New Key')` in Blade or PHP.
2. Run `php artisan lang:extract`, then `php artisan lang:publish`.
3. Translate only the keys from the current task (in `lang/ar.json` or the Language Tokens screen).

Don't hand-edit `lang/*.json` for new keys. A manual translation is overwritten the next time `lang:publish` runs. It also surfaces unrelated pending tokens. Leave those alone.

## Verifying UI Changes

Don't start a dev server, open a browser, or take screenshots to check UI work. Run non-visual checks only (lint, route list, view compile, HTTP status), then tell the user what changed. The user checks the result visually and reports back.
