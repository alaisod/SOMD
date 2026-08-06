# Spec: Auto-Default page metadata (title / page_title / breadcrumb)

> Status: **Draft — no code changes yet**
> Related files: `app/Controllers/BaseController.php`, `app/Controllers/Home.php`,
> `app/Views/layouts/main.php`, `app/Views/layouts/header.php`, `app/Views/pages/starter.php`

## 1. Problem statement

Today, page metadata (browser tab `title`, the h1 `page_title`, and the breadcrumb)
is hardcoded in each page view via `$this->section(...)` calls. The master layout
(`layouts/main.php`) supplies static fallbacks (`'Starter Page'`). The user wants
this metadata to be **auto-defaulted** so that:

- Controllers write almost nothing to get correct titles/breadcrumbs.
- The **BaseController** derives sensible defaults from the controller/method names.
- Controllers can still **override** per-page by passing `$data` to the view.
- Page views can still override everything via **sections** (last word).

## 2. Decision summary (from interview)

| Topic | Decision |
|---|---|
| Mechanism | **Layered**: BaseController derives defaults → controller `$data` overrides → view sections win |
| Precedence | `section > $data > derived default` |
| Derivation source | Humanized controller + method names |
| Breadcrumb shape | `Home > Controller > Method` (Home linked, Controller/Method plain, last active) |
| Home crumb link | `site_url('/')` |
| Tab title format | `Controller_name | site_name` → e.g. `User | mySite` |
| site_name source | **Protected constant** on `BaseController` (e.g. `protected string $siteName = 'mySite';`) |
| Humanization | **`lang()` lookup first, humanized fallback** (no lang files required to work) |
| h1 (page_title) | **Method humanized** (e.g. `Edit Profile`); title stays `User | mySite` |
| Scope | Applies to **every controller** extending `BaseController` (incl. future auth pages) |
| Controller API | **`$this->render($view, $data)` only** — the single standard way to return a view |
| Custom breadcrumb format | Associative: `['Home' => url, 'User' => url]`; last item auto-active |
| `index` method | Collapses to **controller name only**: title `User | mySite`, h1 `User`, breadcrumb `Home > User` |
| Home controller | **Converted** to `return $this->render('pages/starter');` (consistent everywhere) |
| Lang key naming | `Site.<controller>.<method>` all lowercase, e.g. `Site.usercontroller.editprofile` |
| Site-name suffix | **Always appended** to the tab title: even explicit `['title' => 'Custom']` → `Custom | mySite` |

## 3. Requirements

### 3.1 BaseController changes (`app/Controllers/BaseController.php`)

- Add a protected constant/property for the site name, e.g.:

  ```php
  /** Used as the suffix in the browser tab title, e.g. "User | mySite". */
  protected string $siteName = 'mySite';
  ```

- Add a `render(string $view, array $data = []): string` method that:
  1. Merges BaseController-derived defaults into `$data` (only for keys not
     already present — i.e. controller data wins over derived defaults).
  2. Returns `view($view, $mergedData)`.
- Add a protected helper that derives defaults from the current controller/method
  (see §3.3).

### 3.2 `$this->render()` semantics

- Signature: `protected function render(string $view, array $data = []): string`
- Controllers call: `return $this->render('pages/starter');`
- Merge order (lowest → highest):
  1. Derived defaults (from controller/method names)
  2. Controller-passed `$data`
  3. View sections (evaluated later inside the layout — the layout prefers a
     defined section over data; see §3.4)

### 3.3 Derivation rules

For `UserController::editProfile` (non-`index`):

- `title` = `<ControllerHumanized> | <siteName>` → `User | mySite`
- `page_title` = method humanized → `Edit Profile`
- `breadcrumbs` = `['Home' => site_url('/'), 'User' => '', 'Edit Profile' => '']`
  (Home linked; last entry active; middle entries plain)

For `UserController::index` (and any controller's `index`):

- `title` = `<ControllerHumanized> | <siteName>` → `User | mySite`
- `page_title` = controller humanized → `User`
- `breadcrumbs` = `['Home' => site_url('/'), 'User' => '']`

Humanization algorithm:

1. Strip the `Controller` suffix (`UserController` → `User`).
2. Split `camelCase`/`snake_case` into words (`editProfile` → `Edit Profile`).
3. **lang() lookup first**: `lang('Site.usercontroller.editprofile')`; if the key
   is missing (returns the key itself), fall back to the humanized string.

### 3.4 Layout changes (`app/Views/layouts/main.php`, `header.php`)

The layout reads metadata in precedence order: **section > data > default**.

- `header.php` `<title>`: use a `$title` view-data variable if provided, else the
  `title` section, else a site default. (Data flows to the layout automatically
  because `view($view, $data)` shares its data with the extended layout.)
- `main.php`:
  - `page_title`: if the page view defines a `page_title` section, use it;
    otherwise use `$pageTitle` from view data (derived or controller-set).
  - `breadcrumb`: if the page view defines a `breadcrumb` section, use it;
    otherwise render the `$breadcrumbs` array from view data.
  - Rendering of the breadcrumb array: iterate `label => url`; the last item gets
    `class="active"` + `aria-current="page"` and is not a link; items with a
    non-empty URL get wrapped in `<a>`; empty-URL non-last items render as plain
    `<li>` text.
- `starter.php`: no longer needs `title`/`page_title`/`breadcrumb` sections —
  they come from defaults. The `content` section is unchanged.

### 3.5 Home controller

```php
public function index(): string
{
    return $this->render('pages/starter');
}
```

Home = `HomeController`? No — `Home` has no `Controller` suffix stripping issue
(stays `Home`). Since the method is `index`, title = `Home | mySite`,
h1 = `Home`, breadcrumb = `Home` (linked via `site_url('/')`).

## 4. Data contract (`$data` keys a controller may pass)

| Key | Type | Default (derived) | Notes |
|---|---|---|---|
| `title` | string | `<Controller> | <siteName>` | Always gets ` | <siteName>` appended if not already present |
| `page_title` | string | humanized method (or controller for `index`) | Shown as h1 |
| `breadcrumbs` | array<string,string> | `['Home' => site_url('/'), <Controller> => '', <Method> => '']` | Associative `label => url`; last = active |
| `content` | (n/a) | — | Page view's `content` section remains the required body |

## 5. Edge cases

- **Missing lang keys**: fall back to humanized string; never show a raw lang key.
- **`index` method**: collapse to controller name (no "Index" word).
- **Explicit title**: still gets ` | <siteName>` appended (always-append rule).
- **Page-level override**: a view section for `title`/`page_title`/`breadcrumb`
  beats both data and defaults.
- **Auth/full-screen pages** using a different layout: defaults still generated by
  BaseController; the auth layout simply chooses which values to render.
- **`site_name` change**: single constant edit on BaseController.
- **Multi-word site names** (e.g. `mySite`): used verbatim after ` | `.
- **Controllers without the `Controller` suffix** (e.g. `Home`): humanize as-is.

## 6. Out of scope (future)

- Generating actual `lang` files / translations per page.
- Per-route static breadcrumb maps (middle crumbs are plain text, not linked).
- Auto-detection of nav sidebar "active" state from metadata (already handled
  separately via `url_is()`).

## 7. Validation plan

1. `php -l` on all touched files.
2. Serve locally, confirm:
   - `Home` renders h1 `Home`, tab title `Home | mySite`, breadcrumb `Home` (linked).
   - A stub `UserController::editProfile` renders h1 `Edit Profile`,
     tab `User | mySite`, breadcrumb `Home > User > Edit Profile`.
   - Controller override `['page_title' => 'Custom']` renders h1 `Custom`.
   - A page view redefining the `page_title` section beats data + defaults.
   - Missing lang keys fall back to humanized strings (no raw keys shown).
