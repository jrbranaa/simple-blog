# Migrating a site to simple-blog

This guide documents the steps to migrate an existing PHP markdown blog into a simple-blog container running under simple-webs.

Each blog site lives in its own git repo containing only site-specific content (`posts/`, `pages/`, `theme/`, `config.php`). The repo is cloned as a sibling to `simple-webs` on the server and mounted into the simple-blog container at runtime.

Use the `devlog.srchparty` migration as a reference for each new site. The steps are the same; only the site name, domain, and theme details change.

---

## Prerequisites

- simple-blog image built and pushed to GHCR:
  ```bash
  docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/jrbranaa/simple-blog:latest -t ghcr.io/jrbranaa/simple-blog:$(date +%Y-%m-%d) --push .
  ```
- simple-webs running on the server (Traefik up, `web` network exists)
- A GitHub deploy key for the site repo added to `/home/deploy/.ssh/` on the server
- Access to the existing site's files

---

## Server setup — SSH config (one-time)

The `deploy` user needs an SSH config file to map each site repo to its dedicated deploy key. Without it, SSH always falls back to the default key and authentication fails for repos that key doesn't have access to.

**How it works:** the config file defines aliases for `github.com`, each wired to a specific private key. Git remote URLs use the alias instead of `github.com` — SSH resolves the alias to the real hostname and selects the correct key. Git itself has no knowledge of this; it's handled entirely within SSH.

Create or edit `/home/deploy/.ssh/config`:

```bash
sudo -u deploy nano /home/deploy/.ssh/config
```

Add an entry for each repo:

```
Host github-<alias>
    HostName github.com
    User git
    IdentityFile ~/.ssh/<keyfile>
```

Example:

```
Host github-simpleweb
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_simpleweb

Host github-dev-srchparty
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_devlog-srchparty

Host github-fronchfry
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_fronchfry

Host github-propellerheads
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_propellerheads
```

Set permissions:

```bash
sudo chmod 600 /home/deploy/.ssh/config
sudo chown deploy:deploy /home/deploy/.ssh/config
```

Test each alias:

```bash
sudo -u deploy ssh -T git@github-<alias>
```

Should return: `Hi jrbranaa/repo-name! You've successfully authenticated...`

**Adding a new site later:** add one new `Host` block to the config and you're done — no other changes needed.

---

## Step 1 — Set up the site repo on the server

The site repo should be cloned as a sibling to `simple-webs`:

```
/srv/
├── simple-webs/
├── devlog.srchparty/    ← site repo
├── fronchfry.com/
└── ...
```

Clone using the SSH alias (not `github.com` directly):

```bash
cd /srv
sudo -u deploy git clone git@github-<alias>:jrbranaa/<repo-name>.git
```

The site repo should contain only:

```
<sitename>/
├── config.php
├── posts/
├── pages/
└── theme/
    ├── header.php
    ├── footer.php
    ├── sidebar.php
    ├── style.css
    └── logo.png
```

---

## Step 2 — Populate content

If migrating from an existing site, copy the Markdown files into the repo:

```bash
cp -r <old-site>/posts/*.md <sitename>/posts/
cp -r <old-site>/pages/*.md <sitename>/pages/
```

No changes to the Markdown files are needed. The frontmatter format is identical.

---

## Step 3 — Create config.php

Copy `simple-blog/theme-default/config.example.php` to the site repo root and fill in the values:

```php
<?php
return [
    'site_name'      => 'My Site Name',
    'posts_per_page' => 5,
    'owa_base_url'   => 'https://owa.example.com/owa/',  // or '' to disable
    'owa_site_id'    => '<your-site-id>',                 // or '' to disable
];
```

**Where to find these values in the old site:**
- `site_name`: title in `<head>` and footer copyright in old `includes/header.php` / `includes/footer.php`
- `owa_base_url` / `owa_site_id`: in the OWA tracking block inside old `includes/header.php`
- `posts_per_page`: `$per_page` in old `index.php` (default was `5`)

---

## Step 4 — Set up the theme

Start from the default theme:

```bash
cp simple-blog/theme-default/header.php  <sitename>/theme/
cp simple-blog/theme-default/footer.php  <sitename>/theme/
cp simple-blog/theme-default/sidebar.php <sitename>/theme/
cp simple-blog/theme-default/style.css   <sitename>/theme/
```

Then customize each file for the site:

### header.php

The default `header.php` reads `$config` for analytics and site name. It expects the logo at `/theme/logo.png`. Changes to make per site:

- Update any extra `<meta>` tags (Open Graph, description, etc.) if the old site had them
- Add any additional `<link>` or `<script>` tags the old site included

### footer.php

The default footer has a single "About" nav link. Update `<nav class="footer-nav">` to match the old site's footer links:

```php
<nav class="footer-nav">
    <a href="https://srchparty.com">Go->srchParty.com</a>
    <a href="/pages/about">About</a>
</nav>
```

### sidebar.php

If the old site had a sidebar widget, copy that markup here. If not, leave the file empty — an empty `sidebar.php` produces no sidebar output.

### style.css

Replace the default `style.css` with the old site's CSS, or customize the default. The CSS custom properties at the top are the main levers for rebranding:

```css
:root {
    --fg:     #59C3D7;   /* links, post titles */
    --accent: #D75B70;   /* hover states, tags, blockquotes */
    --bg:     #fafaf8;   /* page background */
}
```

### logo.png

Copy the old site's logo into the theme directory:

```bash
cp <old-site>/logo-lg.png <sitename>/theme/logo.png
```

The engine references the logo at `/theme/logo.png`. Rename as needed on copy.

---

## Step 5 — Add the service to simple-webs

Open `simple-webs/docker-compose.yml` and add a new service block. Volume paths use `../` to reference the sibling repo:

```yaml
  <sitename>:
    image: ghcr.io/jrbranaa/simple-blog:latest
    volumes:
      - ../<sitename>/posts:/var/www/html/posts:ro
      - ../<sitename>/pages:/var/www/html/pages:ro
      - ../<sitename>/theme:/var/www/html/theme:ro
      - ../<sitename>/config.php:/var/www/html/config.php:ro
    networks:
      - web
    labels:
      - "traefik.enable=true"
      # HTTPS router
      - "traefik.http.routers.<sitename>.rule=Host(`yourdomain.com`)"
      - "traefik.http.routers.<sitename>.entrypoints=websecure"
      - "traefik.http.routers.<sitename>.tls.certresolver=letsencrypt"
      # HTTP router — redirects to HTTPS
      - "traefik.http.routers.<sitename>-http.rule=Host(`yourdomain.com`)"
      - "traefik.http.routers.<sitename>-http.entrypoints=web"
      - "traefik.http.routers.<sitename>-http.middlewares=redirect-to-https"
      - "traefik.http.middlewares.redirect-to-https.redirectscheme.scheme=https"
      - "traefik.http.middlewares.redirect-to-https.redirectscheme.permanent=true"
```

---

## Step 6 — Test locally before going live

Temporarily switch the router rule to a `.localhost` domain so you can verify the site without affecting DNS:

```yaml
      - "traefik.http.routers.<sitename>.rule=Host(`<sitename>.localhost`)"
      - "traefik.http.routers.<sitename>.entrypoints=web"
```

Start the container:

```bash
cd /srv/simple-webs
docker compose up -d <sitename>
```

Visit **http://\<sitename\>.localhost** and verify:

- [ ] Post list loads and pagination works
- [ ] Individual post pages render (including code blocks and tags)
- [ ] Static pages load (e.g. `/pages/about`)
- [ ] Logo and styles are correct
- [ ] Analytics snippet is present in page source (if configured)
- [ ] Sidebar renders correctly (or is absent if not configured)
- [ ] 404 page returns correctly for a bad URL

---

## Step 7 — Switch to the production domain

Update the labels in `docker-compose.yml` to the real domain, then bring the container up:

```bash
docker compose up -d <sitename>
```

Point the domain's DNS `A` record to the server IP. Traefik provisions the Let's Encrypt certificate automatically on the first request.

---

## Step 8 — Decommission the old site

Once the new container is serving traffic correctly:

1. Remove or disable the old site's web server vhost / config
2. Remove engine files from the site repo (`index.php`, `post.php`, `page.php`, `includes/`, `css/`, `.htaccess`, deploy scripts) — only site-specific content should remain
3. Archive the old site repo if it is no longer needed as a standalone deployment

---

## Deploying content updates

On the server, content updates are a `git pull` in the site repo — no container restart needed since the files are volume-mounted:

```bash
sudo -u deploy git -C /srv/<sitename> pull
```

## Updating the engine

When a new version of simple-blog is published:

```bash
docker compose -f /srv/simple-webs/docker-compose.yml pull
docker compose -f /srv/simple-webs/docker-compose.yml up -d
```

---

## Reference: what changed between the old site and simple-blog

| Old site                         | simple-blog equivalent                                        |
|----------------------------------|---------------------------------------------------------------|
| `includes/markdown.php`          | `src/includes/markdown.php` (unchanged)                       |
| `includes/header.php`            | `theme/header.php` (site-specific values moved to `config.php`) |
| `includes/footer.php`            | `theme/footer.php` (site name from `config.php`)              |
| `includes/sidebar.php`           | `theme/sidebar.php`                                           |
| `css/style.css`                  | `theme/style.css`                                             |
| `logo-lg.png`                    | `theme/logo.png`                                              |
| Hardcoded `$per_page = 5`        | `config.php` → `posts_per_page`                               |
| Hardcoded site name in title     | `config.php` → `site_name`                                    |
| OWA credentials in `header.php`  | `config.php` → `owa_base_url` / `owa_site_id`                 |
| Files inside `simple-webs/sites/`| Sibling repo cloned alongside `simple-webs`                   |
| Apache vhost / shared hosting    | `simple-blog` container + Traefik in simple-webs              |
