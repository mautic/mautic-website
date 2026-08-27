# Membership page Elementor templates

Importable Elementor templates for the two membership pages. These are **scaffolds**:
they carry the structure, copy, colours and type scale from the design handoff so that
nobody has to retype 4,000 words, but expect to nudge spacing in the editor afterwards.

Page content lives in the WordPress database, not in this repo. These files exist so
that a page build is reviewable, repeatable and restorable — re-export and commit them
whenever the pages change substantially.

## Before importing

**1. Replace the media placeholders.** The mascot images are referenced as
`MEDIA_URL/mautibot-*.svg`. Upload the three SVGs from the design handoff to the media
library first (Safe SVG is already active on the site), then run:

```bash
# GNU sed (Linux, and macOS with coreutils/gnu-sed installed)
sed -i 's#MEDIA_URL#https://mautic.org/wp-content/uploads/2026/08#g' *.json

# BSD sed (macOS default) — note the mandatory empty backup suffix
sed -i '' 's#MEDIA_URL#https://mautic.org/wp-content/uploads/2026/08#g' *.json
```

Or portably, without worrying which sed you have:

```bash
perl -pi -e 's#MEDIA_URL#https://mautic.org/wp-content/uploads/2026/08#g' *.json
```

Adjust the upload path to wherever they actually land. Testimonial photos already point
at real media library URLs and need no change.

**2. Add the two missing global colours** (Site Settings → Global Colors), so the
sections that use them stay editable from one place:

| Name | Value | Used by |
| --- | --- | --- |
| Pale Cyan | `#E5F4F3` | individual banner, participatory banner, icon tiles |
| Deep Navy | `#2C355A` | "Where your membership goes" |

Everything else maps onto globals the kit already has: Dusky Blue `#4E5E9D`,
Green Teal `#00B49C`, Accent `#FDB933`.

**3. Check the Elementor version.** These were authored against Elementor 4.2.3 and
Elementor Pro 4.x. Production was still on Pro 3.26.0 at time of writing — import into
an environment on the newer Pro or the nested accordion in `08-corporate-faq.json`
will not come through.

## Importing

Templates → Saved Templates → Import Templates → upload the `.json` → then in the page
editor use the folder icon → My Templates → Insert.

- `corporate-membership-page.json` and `individual-membership-page.json` are the whole
  pages in one go. Start here.
- The numbered files are the same sections individually, for rebuilding one at a time
  or for pulling a single section into another page.

Insert into a **duplicate** of the existing page (ID 18488) rather than a new page —
the corporate page should keep its URL, ID and Rank Math metadata.

## What still has to be done by hand

**The member logo wall** (`03-corporate-logo-wall.json`) contains a placeholder HTML
widget, not a Loop Grid. Loop Grid needs a loop template that has to be created in the
editor, so it can't be shipped as JSON. Replace the placeholder with:

- Widget: **Loop Grid**
- Query → Source: `member` post type, Order by `member_type`, descending
- Query → Posts per page: 8
- Layout → Columns: 4 desktop / 3 tablet / 2 mobile
- Loop template: one **Featured Image** widget, size `medium`, linked to the post
- Advanced → CSS Classes: `mautic-logowall`

The styling for that class ships in `wp-content/themes/mautic-theme/css/membership.css`.

**Icons.** Every icon box uses a Font Awesome placeholder so the import renders
something sensible immediately. The design calls for Remix Icon line variants — upload
these 13 SVGs and swap them in the editor:

| Section | Design icon | Placeholder |
| --- | --- | --- |
| Benefits | `ri-megaphone-line` | `fa-bullhorn` |
| Benefits | `ri-shield-star-line` | `fa-shield-alt` |
| Benefits | `ri-award-line` | `fa-award` |
| Impact | `ri-rocket-2-line` | `fa-rocket` |
| Impact | `ri-team-line` | `fa-users` |
| Impact | `ri-route-line` | `fa-route` |
| Impact | `ri-user-star-line` | `fa-user-tie` |
| Impact | `ri-money-dollar-circle-line` | `fa-hand-holding-usd` |
| Impact | `ri-lightbulb-flash-line` | `fa-lightbulb` |
| Individual | `ri-government-line` | `fa-landmark` |
| Individual | `ri-eye-line` | `fa-eye` |
| Individual | `ri-hand-heart-line` | `fa-hand-holding-heart` |

Don't load Remix Icon from a CDN for thirteen glyphs — upload the SVGs and use
Elementor's icon control in SVG mode.

**Buttons.** Primary calls to action use the Button widget with no style overrides, so
they inherit the site's global pill. Secondary actions are text links rather than a
second button style. Nothing here changes the global button.

## Copy-paste HTML blocks

Two sections use an HTML widget. Their CSS lives in the theme, so paste the markup
exactly — no inline styles.

### Hero mascot spotlight

```html
<div class="mautic-spotlight">
  <div class="mautic-spotlight__beam" aria-hidden="true"></div>
  <div class="mautic-spotlight__pool" aria-hidden="true"></div>
  <img class="mautic-spotlight__figure"
       src="MEDIA_URL/mautibot-welcome.svg"
       alt="" role="presentation">
</div>
```

The mascot is decorative — it repeats nothing that the headline beside it doesn't
already say — so it carries an empty `alt`. Use `mautibot-happy.svg` on the individual
page.

### Brand gradient hairline

No HTML widget needed. Add an empty container, set Content Width to Full, Min Height to
6px, padding to 0, and give it the CSS class `mautic-hairline`.

### Anchor targets

Any container given an anchor ID also needs the CSS class `mautic-anchor`, which sets
the scroll offset that clears the sticky header. `membership.css` targets that class
rather than the IDs themselves, because it loads site-wide and `#tiers` / `#sign-up` /
`#participatory` are generic enough that another page could use them. The templates
already pair the two; keep them paired on anything you add.

## Accessibility notes carried into these templates

- Headings run `h1` → `h2` → `h3` with no skipped levels on either page.
- The FAQ uses Elementor's Nested Accordion (real buttons, arrow-key support, FAQ
  schema on) rather than the hand-rolled JS in the design reference.
- Decorative images have empty `alt`; testimonial photos are named for the person.
- The pricing table's own accessibility work lives in the widget — semantic `<table>`,
  a caption, row and column headers, text alternatives for the ✓ and — glyphs, a
  keyboard-reachable scroll region and a live region for the price change.
- Contrast: the ✓ and — markers use `#00796b` (5.32:1) and `#6f6f6f` (5.02:1) rather
  than the design's `#00B49D` (2.62:1) and `#c6c6c6` (1.71:1), which both fail AA.

### Two contrast changes away from the design reference

These are visible, so flag them if you disagree — but the design as drawn fails WCAG AA
at both points.

**The individual hero is `#00796B`, not `#00B49D`.** White text on Green Teal is
**2.62:1**. That fails the 4.5:1 body-text threshold and also the 3:1 large-text
threshold, so neither the H1 nor the lede is compliant as drawn. Darkening to `#00796B`
— already in the Mautic design system as the tier-pill ink — keeps white text and the
teal identity at **5.32:1**. The alternative is keeping `#00B49D` and switching to dark
ink (`#1F1F1F`, 6.29:1), which changes the hero's character much more.

**Links on coloured bands are overridden.** The kit's global link colour is Green Teal
`#00B49C`, which reads at 2.35:1 on the Dusky Blue hero and 2.32:1 on Pale Cyan. The
`mautic-on-dark` and `mautic-on-light` classes in `membership.css` correct these to
white and `#00796b` respectively. They are already applied in these templates — keep
them on any new link you add to a coloured section.
