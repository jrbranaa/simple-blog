# simple-blog

A containerized PHP markdown blog engine. Write posts in Markdown, mount your content and theme as volumes, and run it anywhere Docker runs.

The engine is baked into a single image. Every site-specific detail — styles, templates, logo, analytics, and content — lives outside the image and is mounted at runtime. Updating the engine across all your blogs means pulling one new image tag.

---

## How it works

```
simple-blog image (engine)
    + sites/myblog/theme/    (your templates, CSS, logo)
    + sites/myblog/posts/    (your Markdown posts)
    + sites/myblog/pages/    (your Markdown pages)
    + sites/myblog/config.php
    = a running blog
```

The image contains only the routing logic (`index.php`, `post.php`, `page.php`), the Markdown parser, and the Apache configuration. Everything else is volume-mounted per site.

---

## Repository structure

```
simple-blog/
├── Dockerfile
├── docker-compose.example.yml   # drop-in block for simple-webs
├── docker/
│   └── apache.conf              # enables .htaccess rewriting
├── src/                         # baked into the image — do not edit per-site
│   ├── .htaccess
│   ├── index.php
│   ├── post.php
│   ├── page.php
│   └── includes/
│       └── markdown.php
└── theme-default/               # starter theme — copy this to your site
    ├── config.example.php
    ├── header.php
    ├── footer.php
    ├── sidebar.php
    └── style.css
```

---

## Building the image

The version number lives in the `VERSION` file at the project root. Pass it as a build arg so it is embedded in the image label and displayed in the footer of any site using `theme-default`.

```bash
docker build --build-arg VERSION=$(cat VERSION) -t simple-blog:$(cat VERSION) .
```

Also tag `latest` if this is the current release:

```bash
docker tag simple-blog:$(cat VERSION) simple-blog:latest
```

To release a new version, update `VERSION` and re-run the build command above.

---

## Publishing the image

The image is published to GitHub Container Registry (GHCR) as a `linux/amd64` build.

Log in to GHCR first (one-time):

```bash
echo $GITHUB_TOKEN | docker login ghcr.io -u jrbranaa --password-stdin
```

Build and push in a single step:

```bash
./scripts/release.sh
```

The script reads the `VERSION` file itself and tags/pushes `ghcr.io/jrbranaa/simple-blog:<VERSION>` and `:latest` from that value — there's no tag to hand-type, so it can't drift from what's actually in `VERSION`. To release a new version, update `VERSION` and re-run the script.

---

## Site layout (what you mount)

Each site provides four volume mounts:

| Mount target (in container)  | What it contains                          |
|------------------------------|-------------------------------------------|
| `/var/www/html/config.php`   | Site name, analytics, pagination settings |
| `/var/www/html/theme/`       | `header.php`, `footer.php`, `sidebar.php`, `style.css`, `logo.png` |
| `/var/www/html/posts/`       | Markdown post files (`*.md`)              |
| `/var/www/html/pages/`       | Markdown page files (`*.md`)              |

### config.php reference

```php
<?php
return [
    'site_name'      => 'My Blog',       // used in <title> and footer copyright
    'posts_per_page' => 5,               // number of posts shown on the index
    'owa_base_url'   => '',              // optional: OWA instance URL
    'owa_site_id'    => '',              // optional: OWA site ID
];
```

OWA tracking is injected automatically when both `owa_base_url` and `owa_site_id` are non-empty. Leave them as empty strings to disable.

### Theme files

Start by copying `theme-default/` into your site directory and customizing:

| File          | Purpose                                                  |
|---------------|----------------------------------------------------------|
| `header.php`  | `<head>`, analytics snippet, site header/logo            |
| `footer.php`  | Footer nav, copyright, sidebar include, closing HTML     |
| `sidebar.php` | Sidebar widget content (leave empty to hide the sidebar) |
| `style.css`   | All site styles                                          |
| `logo.png`    | Logo image (referenced as `/theme/logo.png`)             |

The engine passes two PHP variables into every template:

- `$config` — the array from `config.php`
- `$page_title` — the current page's `<title>` string

### Post frontmatter

Posts are Markdown files with a YAML frontmatter block:

```markdown
---
title: My Post Title
date: 2026-06-30
description: A short summary shown on the post list.
tags: [tag1, tag2]
current state: published
publish date: 2026-06-30
---

Post body here.
```

A post appears on the index only when `current state: published` and `publish date` is today or in the past.

---

## Running a site locally (standalone)

For a quick local test without simple-webs:

```bash
docker run --rm -p 8080:80 \
  -v ./sites/myblog/posts:/var/www/html/posts:ro \
  -v ./sites/myblog/pages:/var/www/html/pages:ro \
  -v ./sites/myblog/theme:/var/www/html/theme:ro \
  -v ./sites/myblog/config.php:/var/www/html/config.php:ro \
  simple-blog:latest
```

Visit **http://localhost:8080**.

---

## Integration with simple-webs

[simple-webs](../simple-webs) runs Traefik as a reverse proxy in front of multiple site containers on a single server. simple-blog is designed to slot in as a service alongside any other sites in that setup.

### Architecture

```
Internet
   |
[Traefik] ← routes by Host header, handles SSL
   |           |             |
[site-a]   [site-b]    [simple-blog]
(nginx)    (nginx)      (php-apache)
```

### Adding a simple-blog site to simple-webs

1. **Build or pull the image** on your server:
   ```bash
   docker build -t simple-blog:latest /path/to/simple-blog
   ```

2. **Create the site directory** under `simple-webs/sites/`:
   ```
   simple-webs/sites/myblog/
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

3. **Add a service block** to `simple-webs/docker-compose.yml`:
   ```yaml
   myblog:
     image: simple-blog:latest
     volumes:
       - ./sites/myblog/posts:/var/www/html/posts:ro
       - ./sites/myblog/pages:/var/www/html/pages:ro
       - ./sites/myblog/theme:/var/www/html/theme:ro
       - ./sites/myblog/config.php:/var/www/html/config.php:ro
     networks:
       - web
     labels:
       - "traefik.enable=true"
       - "traefik.http.routers.myblog.rule=Host(`myblog.example.com`)"
       - "traefik.http.routers.myblog.entrypoints=websecure"
       - "traefik.http.routers.myblog.tls.certresolver=letsencrypt"
   ```

4. **Bring it up** — Traefik picks up the new container automatically:
   ```bash
   docker compose up -d myblog
   ```

See `docker-compose.example.yml` for a complete reference block, and the simple-webs README for DNS and SSL setup.

### Updating the engine

Because the engine is in the image and content is in volumes, updating all sites is:

```bash
docker build -t simple-blog:latest .
docker compose up -d   # restarts only containers using the updated image
```

No content or theme files are touched.
