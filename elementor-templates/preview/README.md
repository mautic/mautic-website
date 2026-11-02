# Design reference previews

Rendered from the Claude Design handoff (`Membership Page.dc.html` and
`Individual Membership.dc.html`) at a 1440px viewport, cropped by section.
These show the intended look — they are not screenshots of the built pages.

The handoff's placeholder header and footer are cropped out; the site keeps its
real Elementor Theme Builder header and footer.

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

To regenerate: serve the handoff folder over HTTP, then capture at
`--window-size=1440,<page height>` with `--force-device-scale-factor=2` and crop
by section offset.
