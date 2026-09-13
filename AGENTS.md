# Product website source of truth

This repository owns all public product home, support and privacy pages under `source/<product>/`.
Keep existing public URLs unchanged. Update these pages here, not in sibling app repositories.
Use English for product-facing pages. Preserve the existing personal/blog content.
Author comments use `@author Codex`.
Run `npm run build` before committing website changes. Generated `public/` is not tracked.
Do not use `hexo deploy`: the source branch is `dev`; production is the existing VPS.
Deployment instructions are in README.md. Never put SSH keys or credentials into this repository.
