# Sawyer website and product pages

Canonical checkout: `~/products/Sawyer-zh.github.io` (moved from `~/www/Sawyer-zh.github.io`).
The existing Git history, `dev` source branch and GitHub remote are preserved.

## Edit and build

All public pages are maintained in this repository:

| Product | Source directory | Public URL |
|---|---|---|
| Court Score | `source/court-score/` | `/court-score/` |
| Pocket Draw | `source/pocket-draw/` | `/pocket-draw/` |
| Silent Stage | `source/silent-stage/` | `/silent-stage/` |
| Delivery Ledger | `source/delivery-ledger/` | `/delivery-ledger/` |
| SiteProof | `source/siteproof/` | `/siteproof/` |
| Last Done | `source/last-done/` | `/last-done/` |
| Row Count | `source/row-count/` | `/row-count/` |

Each product has `index.html`, `support/index.html` and `privacy/index.html`.
Public origin: https://sawyer007.duckdns.org . Advertising seller declaration: `source/app-ads.txt`.
The personal homepage stays in `source/index.html`; blog content stays in `source/_posts/`.

Use Node 18 or newer, then `npm ci` (only when dependencies are missing) and `npm run build`.
Preview with `npm run serve`. Hexo generates `public/`; never edit or commit generated output.
Plain imported product HTML is copied unchanged via `skip_render`; SiteProof keeps its existing Hexo front matter.

## Version control and publishing

Review `git diff`, commit source changes to `dev`, and push with `git push origin dev` when ready.
Do not use `hexo deploy` or push generated HTML over the source branch.

`npm run deploy:vps` builds and uploads the complete generated site to a new release directory,
then switches the VPS `current` symlink. It requires a clean Git checkout and existing SSH access.
The previous release is retained; rollback by pointing `current` back to its previous target.
Set `SITE_SSH_TARGET` to select the existing VPS login (default `root@107.175.92.73`).
No credentials are stored here. This migration does not deploy or change public URLs.

## Migration

Product pages were reconciled against the live VPS on 2026-09-13.
Sibling app `website/`, `website-vps/`, and `release/website/` copies are historical references;
use each app's `WEBSITE.md` to locate the canonical source. Do not publish those old copies.
