# Elementor templates

Importable Elementor templates. These are **scaffolds**: they carry the structure,
copy, colours and type scale from the design handoff so that nobody has to retype
thousands of words, but expect to nudge spacing in the editor afterwards.

Page content lives in the WordPress database, not in this repo. These files exist so
that a page build is reviewable, repeatable and restorable — re-export and commit them
whenever the pages change substantially.

| Files | Page |
| --- | --- |
| `01`–`09`, `corporate-membership-page.json` | Corporate membership |
| `20`–`24`, `individual-membership-page.json` | Individual membership |
| `30`–`35`, `support-mautic-page.json` | Support Mautic |

# Membership pages

## Before importing

**1. Mascots ship in the theme.** No media-library upload and no find-and-replace.
The templates point at `/wp-content/themes/mautic-theme/assets/membership/`.
Hero, impact, and the individual-membership banner use HTML `<img>` tags, not
Image widgets — Elementor import blanks Image widgets whose URL is a theme file
rather than a media-library attachment.

| File | Used on |
| --- | --- |
| `mautibot-welcome.svg` | Corporate hero |
| `mautibot-happy.svg` | Individual hero, corporate impact section |
| `mautibot-information.svg` | Banner pointing at individual membership |

`mautibot-danger.svg`, `mautibot-warning.svg` and `mautibot-error.svg` live in the same
folder for other UI, but are not used on these pages. Testimonial photos already point
at real media library URLs and need no change. The theme must be deployed before the
templates will show the mascots.

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
It also centers an incomplete last row; the Loop Grid Columns control is then
ignored in favour of 4 / 3 / 2 via CSS.

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
       src="/wp-content/themes/mautic-theme/assets/membership/mautibot-welcome.svg"
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

# Support Mautic page

A new page asking people to fund the project — individual membership, corporate
membership or a donation — with the reasoning, the transparency links and a FAQ
around it. It sits beside the membership pages rather than replacing anything:
the membership pages sell membership, this one explains why the project needs
funding at all and routes people to the right option.

`support-mautic-page.json` is the whole page. `30`–`35` are the same sections
individually.

| File | Section |
| --- | --- |
| `30-support-hero.json` | Hero + brand hairline |
| `31-support-why.json` | Free to use does not mean free to maintain |
| `32-support-options.json` | The three ways to support, partner strip |
| `33-support-transparency.json` | Testimonial and Open Collective |
| `34-support-faq.json` | Practical questions |
| `35-support-final-cta.json` | Brand hairline + final call to action |

## Before importing

**1. Nothing to replace.** The hero mascot is served from the theme at
`/wp-content/themes/mautic-theme/assets/membership/mautibot-happy.svg`, the same way
the individual membership page loads it, so there is no media-library import and no
find-and-replace step for this page.

The community photo and the testimonial photo point at real media library URLs on
mautic.org and need no change.

**2. No new global colours.** This page uses Dusky Blue `#4E5E9D`, Accent `#FDB933`
and Pale Cyan `#E5F4F3`. Pale Cyan is the one the membership README asks you to add;
if you have already done that, there is nothing to do here.

**3. Check the individual membership page exists.** The "Explore individual
membership" button points at `/become-a-member-of-mautic/individual/`, which is
created by PR #62, phase 4. **Do not put this page live before that one** — or point
the button back at `/become-a-member-of-mautic/#sign-up` until it is there.

**4. Give the page the slug `support-mautic`.** `support.css` is enqueued on that slug
only, rather than site-wide, because it is one page. If you need a different slug — or
a second one on staging — filter it:

```php
add_filter('mautic_support_page_slugs', function ($slugs) {
    $slugs[] = 'support-the-project';
    return $slugs;
});
```

Without the stylesheet the page still reads correctly, but the "Recommended" badge
sits inline instead of on the card's border, the three calls to action no longer line
up, and the tick lists lose their ticks. If that is what you are seeing, the slug is
the first thing to check.

## Importing

Create a **new** page — this one has no existing URL to preserve — then Templates →
Saved Templates → Import, and insert `support-mautic-page.json`.

## What still has to be done by hand

**Icons.** Six icon boxes ship with Font Awesome placeholders. The design calls for
Remix Icon line variants; upload the SVGs and swap them in the editor, the same way as
on the membership pages.

| Section | Design icon | Placeholder |
| --- | --- | --- |
| Why support | `ri-git-branch-line` | `fa-code-branch` |
| Why support | `ri-book-2-line` | `fa-book` |
| Why support | `ri-compass-3-line` | `fa-compass` |
| Partner strip | `ri-shield-star-line` | `fa-shield-alt` |

**FAQ styling.** The Nested Accordion imports with Elementor's default look. The design
draws each question as a white card with a 1px `#E0E0E0` border and a 10.8px radius —
set that on the widget's Style tab. The content settings that matter (one item open at
a time, `h3` titles, FAQ schema on, plus/minus icons) are already in the template.

**Check the heading levels after any edit.** The page runs `h1` → `h2` → `h3` with no
skips. Elementor makes it easy to change a heading's size and take its level with it.

## Copy-paste HTML blocks

Three HTML widgets. Their CSS is in `support.css` and `membership.css`, so paste the
markup exactly — no inline styles.

### Hero mascot spotlight

As on the membership pages, with the happy mascot:

```html
<div class="mautic-spotlight">
  <div class="mautic-spotlight__beam" aria-hidden="true"></div>
  <div class="mautic-spotlight__pool" aria-hidden="true"></div>
  <img class="mautic-spotlight__figure"
       src="/wp-content/themes/mautic-theme/assets/membership/mautibot-happy.svg"
       alt="" role="presentation">
</div>
```

### "Recommended" badge

First element inside the individual membership card, which carries the CSS class
`mautic-supportcard`:

```html
<span class="mautic-badge">Recommended</span>
```

### Tick lists

A real list in a Text Editor widget, not an Icon List. The tick is a background image
on `::before` with empty content, so a screen reader reads the three items and nothing
else — an Icon List puts an `<i>` in front of each one, which some screen readers
announce.

```html
<ul role="list" class="mautic-ticklist">
  <li>Support Mautic annually</li>
  <li>Participate in member voting</li>
  <li>Regional rates shown before joining</li>
</ul>
```

`role="list"` is not redundant here. Safari drops list semantics from a `<ul>` whose
`list-style` is `none` and whose marker is empty generated content, which is exactly
what this list is — VoiceOver would read the items but not announce them as a list or
let you jump between them. Keep the attribute on any tick list you add.

### Aligning the calls to action

The three cards hold different amounts of copy, so two classes do the work:

- `mautic-supportcard__foot` on the container holding the button and the small print,
  which pushes that block to the bottom of the card.
- `mautic-supportcard__note` on the small print itself, which reserves two lines.

Both are needed. The first aligns the bottom of the cards; without the second a card
whose small print runs to one line instead of two still sits its button a line higher
than the others. **Keep the small print to two lines or fewer** — if it ever needs
three, change the `min-block-size` in `support.css` rather than leaving the buttons
out of line.

## Deliberate departures from the design

**Buttons.** The design draws three button styles — the yellow pill, a solid blue one
and an outline one — and pairs the hero's primary button with a ghost button. The
templates use the site's single global button for every primary action and make the
secondary ones text links, which is the same rule the membership pages follow. Nothing
here changes the global button. If the project does want a secondary button style,
that is a global kit decision, not something to introduce on one page.

**Anchors.** `#membership` and `#transparency` are on containers that also carry
`mautic-anchor`, so the scroll offset clears the sticky header. Keep the two paired.

**Column widths.** The multi-column rows set an explicit percentage width on each
child and do *not* set Flex Wrap. An Elementor container with no width is 100% wide,
so a row of three cards with no widths puts one card per line; and a row that does
wrap, whose children add up to 100% plus a gap, pushes its last column onto a second
line. Leaving Flex Wrap alone gives `nowrap` on desktop, and Elementor's own
`max-width: 767px` rule sets every container back to 100% wide and wrapping — so the
phone layout needs no second set of widths. If you add a column, give it a width and
leave the gap room.

## Accessibility notes carried into these templates

- Headings run `h1` → `h2` → `h3` with no skipped levels.
- The FAQ uses the Nested Accordion — real buttons, arrow-key support, FAQ schema on.
- The hero mascot is decorative and carries an empty `alt`. The community photo and
  the testimonial photo are content and are described.
- Tick markers are CSS background images on empty `::before`, so they are never
  announced, and they fall back to list bullets under `forced-colors`.
- Links on the blue hero and the final CTA carry `mautic-on-dark`; links on the pale
  cyan card carry `mautic-on-light`. Without those the kit's global Green Teal link
  reads at 2.35:1 and 2.32:1 — both fail.
- The tick marker is `#00796B` on white (5.32:1), not the design's `#00B49D` (2.62:1).
