# Design reference previews

Rendered from the Claude Design handoffs (`Membership Page.dc.html`,
`Individual Membership.dc.html` and `SupportMautic.dc.html`) at a 1440px
viewport, cropped by section. These show the intended look — they are not
screenshots of the built pages.

The handoff's placeholder header and footer are cropped out; the site keeps its
real Elementor Theme Builder header and footer.

# Membership pages

## Where the build deliberately differs

| Design reference shows | The build does | Why |
| --- | --- | --- |
| MautiCon tickets 1/2/2/4/6/5/10 | 1/2/3/4/5/7/10 | The design mirrored the live site, where Gold sits above Platinum. Corrected — don't copy the numbers out of `03-corporate-pricing-table.png`. |
| Buttons at 20px radius | The site's global 50px pill | Keeping the existing global button style rather than introducing a second one. |
| ✓ `#00B49D`, — `#c6c6c6` | ✓ `#00796b`, — `#6f6f6f` | 2.62:1 and 1.71:1 both fail WCAG AA. Replacements are 5.32:1 and 5.02:1. |
| Individual hero `#00B49D` | `#00796B` | White on Green Teal is 2.62:1 — fails the large-text threshold too. See the pair below. |
| Eight hand-placed logo slots | Loop Grid off the member CPT | Keeps itself current. |
| Remix Icon from a CDN | The same icons uploaded as SVGs | No third-party request on a payment page. |

## Files

| File | Section |
| --- | --- |
| `01-corporate-hero.png` | Hero |
| `02-corporate-benefits-and-logos.png` | Benefit cards, member logo wall |
| `03-corporate-pricing-table.png` | Tier comparison table |
| `04-corporate-impact-and-testimonials.png` | Where your membership goes, testimonials |
| `05-corporate-faq-and-cta.png` | Individual banner, FAQ, final CTA |
| `06-individual-hero-and-perks.png` | Individual hero, what your $100 buys |
| `07-individual-testimonials-and-cta.png` | Testimonials, participatory route, final CTA |
| `08-individual-hero-contrast-design-ref.png` | Individual hero as drawn — white on `#00B49D`, 2.62:1 |
| `09-individual-hero-contrast-as-built.png` | Individual hero as built — white on `#00796B`, 5.32:1 |

# Support Mautic page

## Where the build deliberately differs

Read the button styling in these images with care — it is the one place where
the templates and the design diverge visibly, and it diverges in every section.

| Design reference shows | The build does | Why |
| --- | --- | --- |
| Three button styles — yellow, blue and outline | One style: the site's global pill | The site has a single global button and it stays that way. In the three option cards all three calls to action are the same pill; the card border and the "Recommended" badge carry the emphasis instead. |
| Hero with two buttons side by side | One button, then a line of text links | Same reason. "Donate monthly" and the one-time donation become links under the button. |
| Open Collective and members as two outline buttons | Two text links | Same reason. They carry `mautic-on-light`, which fixes the link contrast on pale cyan. |
| Hand-drawn inline SVG icons | Font Awesome placeholders | So the import renders something sensible immediately. Swap for the uploaded SVGs in the editor. |
| FAQ as bordered white cards | Elementor's Nested Accordion, unstyled | Real buttons, arrow-key support and FAQ schema. The border and radius are set in the editor, not in the template. |
| Decorative quote mark above the testimonial | Not in the template | One SVG for one card. Add it in the editor if it's wanted. |
| Kicker pill in solid pale cyan with teal ink | White ink on the shared translucent pill | The shared `mautic-kicker` is a veil over the band behind it. Teal ink on that veil over the hero blue is **1.19:1**; white is **5.36:1**. The pill is fainter than the design draws it, which is the cost of keeping one kicker rule across all three pages. |

The community photo and Sven's photo in these renders are the real images from
mautic.org, at the URLs the templates use. Nothing there is a placeholder.

## Files

| File | Section |
| --- | --- |
| `10-support-hero.png` | Hero |
| `11-support-why.png` | Free to use does not mean free to maintain |
| `12-support-options.png` | The three ways to support, partner strip |
| `13-support-transparency-and-faq.png` | Testimonial and Open Collective, practical questions |
| `14-support-final-cta.png` | Final call to action |

To regenerate: serve the handoff folder over HTTP, then capture at
`--window-size=1440,<page height>` with `--force-device-scale-factor=2` and crop
by section offset.
