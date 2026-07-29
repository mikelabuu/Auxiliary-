---
name: Farmers Hostel
description: Boutique Farmstead — warm cream, deep emerald and brass gold for a campus hostel inside CLSU.
colors:
  cream: "oklch(96.5% 0.02 90)"
  cream-warm: "oklch(98% 0.012 85)"
  canvas-deep: "oklch(94.5% 0.025 90)"
  ink: "oklch(22% 0.02 160)"
  emerald-deep: "oklch(32% 0.06 160)"
  emerald: "oklch(44% 0.09 160)"
  gold: "oklch(75% 0.12 85)"
  gold-soft: "oklch(86% 0.09 85)"
  clsu-600: "#167C39"
  clsu-950: "#082414"
  palay-400: "#FDBB1F"
  brass-ink: "oklch(45% 0.09 70)"
  brass-deep: "oklch(62% 0.12 78)"
  ink-soft: "oklch(42% 0.025 160)"
  ink-faint: "oklch(46% 0.022 160)"
  shadow-tint: "oklch(15% 0.05 160)"
  daylight-wash: "oklch(93% 0.03 150 / 0.5)"
  ember-600: "#DC2626"
  ember-surface: "oklch(95% 0.03 25)"
  ember-edge: "oklch(58% 0.16 25 / 0.4)"
  ember-ink: "oklch(41% 0.15 25)"
  success-surface: "oklch(95% 0.035 155)"
  success-ink: "oklch(35% 0.08 158)"
typography:
  display:
    fontFamily: "'Playfair Display', ui-serif, Georgia, serif"
    fontSize: "clamp(2rem, 1.35rem + 2.4vw, 3rem)"
    fontWeight: 400
    lineHeight: 1.1
    letterSpacing: "-0.018em"
  display-sm:
    fontFamily: "'Playfair Display', ui-serif, Georgia, serif"
    fontSize: "1.75rem"
    fontWeight: 400
    lineHeight: 1.15
    letterSpacing: "-0.015em"
  heading:
    fontFamily: "'Playfair Display', ui-serif, Georgia, serif"
    fontSize: "1.25rem"
    fontWeight: 400
    lineHeight: 1.25
    letterSpacing: "-0.012em"
  title:
    fontFamily: "'Playfair Display', ui-serif, Georgia, serif"
    fontSize: "1.125rem"
    fontWeight: 500
    lineHeight: 1.3
    letterSpacing: "-0.012em"
  title-sm:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: "normal"
  body:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "normal"
  body-sm:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  caption:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 500
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "Oswald, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.2
    letterSpacing: "0.22em"
  label-sm:
    fontFamily: "Oswald, ui-sans-serif, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 400
    lineHeight: 1.2
    letterSpacing: "0.2em"
  micro:
    fontFamily: "Oswald, ui-sans-serif, system-ui, sans-serif"
    fontSize: "10px"
    fontWeight: 400
    lineHeight: 1.2
    letterSpacing: "0.2em"
rounded:
  sm: "4px"
  md: "12px"
  lg: "1rem"
  pill: "9999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1.25rem"
  lg: "2rem"
  xl: "3.5rem"
components:
  button-primary:
    backgroundColor: "{colors.emerald-deep}"
    textColor: "{colors.cream}"
    rounded: "{rounded.pill}"
    padding: "0.95rem 1.75rem"
    typography: "{typography.label}"
  button-primary-hover:
    backgroundColor: "{colors.emerald}"
    textColor: "{colors.cream}"
  input-field:
    backgroundColor: "{colors.cream-warm}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
    padding: "1.5rem 0.875rem 0.5rem"
---

# Design System: Farmers Hostel

## Overview

**Creative North Star: "The Boutique Farmstead"**

A working agricultural university runs a small hostel, and the hostel behaves like a
boutique property rather than a dormitory office. The system is warm, material and
unhurried: cream paper stock, deep evergreen, and brass — the colors of a lobby with real
wood in it, not of a SaaS dashboard. Nothing is chrome. Depth comes from paper, ink and
soft daylight shadow, never from glass or glow.

The type does the formal work. Playfair Display carries the editorial voice at large
sizes with tight negative tracking; Oswald handles every small uppercase label at wide
tracking, which is where the system's institutional character actually lives; Manrope runs
the copy and every interface control. The pairing reads as *printed matter that learned to
be an interface* — a property's stationery, signage and register, rather than a web theme.

Public surfaces are light by default. The single dark tier (`theme-night`) is the evening
rendition of the same world, not a second identity — evergreen near-black surfaces, bone
type, gold still the only accent. **Confirmed rejection:** the dark "tech console" auth
skin (mesh orbs, holographic passcards, monospace security badges, hairline grid overlays)
is not part of this world and was retired from `/login`.

**Key Characteristics:**

- Warm ivory ground, evergreen structure, brass as the single accent
- Editorial serif display against wide-tracked condensed uppercase labels
- Material depth: paper, ink, film grain, daylight shadow — never glass or glow
- Institutional restraint; the CLSU relationship is stated plainly, never dramatized

## Colors

A warm, low-chroma ground carrying two saturated institutional colors — CLSU's evergreen
and the gold of ripe palay.

### Primary
- **Deep Evergreen** (`emerald-deep`): Structural color. Primary buttons, dark panels, the header on scroll, and any surface that needs to read as built rather than printed. On cream it is the system's strongest contrast pair.
- **Evergreen** (`emerald`): The lifted state of the above — hover, active, and mid-depth panels.

### Secondary
- **Brass Gold** (`gold`): The accent, and the only one. Rules, active indicators, focus emphasis, the CLSU marker, and small ornament. It never carries body text on cream.
- **Soft Brass** (`gold-soft`): Gold on dark grounds, where full gold vibrates. Selection highlight.

### Neutral
- **Cream** (`cream`): The page. Warm ivory, never white.
- **Warm Cream** (`cream-warm`): Raised paper — cards, fields, and anything sitting on the page.
- **Deep Canvas** (`canvas-deep`): Alternating sections and recessed wells.
- **Warm Ink** (`ink`): All body and display text. A green-shifted near-black, not gray-black.

### Tertiary
- **Ember** (`ember-600`): Errors only. Never decorative, never a brand color.

### Named Rules

**The One Accent Rule.** Gold is the only accent in the system. A surface that needs a
second accent needs less content, not another hue.

**The No-White Rule.** There is no `#fff` ground in this world. Paper is cream; a pure
white rectangle reads as a hole punched in the page.

**The Ink-Not-Gray Rule.** Secondary text is warm ink at reduced opacity or tinted toward
evergreen — never a neutral gray, which goes cold against cream instantly.

## Typography

**Display Font:** Playfair Display (fallback `ui-serif, Georgia, serif`)
**Body Font:** Manrope (fallback `ui-sans-serif, system-ui, sans-serif`)
**Label Font:** Oswald (fallback `ui-sans-serif, system-ui, sans-serif`)

**Character:** Editorial and institutional at once. The serif is the property's voice; the
condensed uppercase is its signage; the sans is its paperwork.

### Hierarchy

The ramp is fine-grained and stated in the frontmatter; these are its roles. Sizes below
16px are set in `px` (the site's own convention for signage), 16px and up in `rem`.

- **Display** (Playfair 400, `clamp(2rem, 1.35rem + 2.4vw, 3rem)`, 1.1, `-0.018em`): Page and panel titles. Regular weight, never bold — size carries it.
- **Display sm** (Playfair 400, 1.75rem): Secondary panel titles.
- **Heading** (Playfair 400, 1.25rem): Section heads inside a surface.
- **Title** (Playfair 500, 1.125rem): Named objects — the property line, card subjects.
- **Title sm** (Manrope 600, 1rem): Control-group and card headings.
- **Body** (Manrope 400, 0.9375rem, 1.6): Running copy, 65–75ch measure.
- **Body sm** (Manrope 400, 0.875rem): Dense lists and secondary copy.
- **Caption** (Manrope 500, 0.8125rem): Helper text, links in running copy, switch rows.
- **Label / Label sm / Micro** (Oswald 400, 12px / 11px / 10px, `0.2em–0.26em`, uppercase): Eyebrows, field labels, nav, metadata, and every signage string.

**The Ramp Rule.** New type lands on a documented step. A one-off size (17px, 9.5px) is
drift — round to the neighbouring step rather than widening the ramp.

### Named Rules

**The Signage Rule.** Every small uppercase string in the system is Oswald at ≥0.2em
tracking. Uppercase in Manrope or Playfair is a defect.

**The Regular Display Rule.** Playfair ships at 400. Bold Playfair is not part of this
world.

**The Monospace Exclusion.** There is no mono face in the public world. Monospace worn as
a "technical" costume — security badges, fake serials, tracked status strings — was the
single biggest tell of the retired auth skin. Data and measurement may use tabular Manrope.

## Layout

A centered container with generous outer gutters; content sits on the cream page rather
than in full-width bands, except for deliberate full-bleed photography. Spacing rhythm is
built on a 0.25rem base with the practical steps in `spacing`; more space sits above a
heading than below it, binding the heading to its content.

Breakpoints follow Tailwind defaults (`sm` 640, `md` 768, `lg` 1024, `xl` 1280). The
governing responsive move is *reflow, not shrink*: multi-panel compositions stack into a
single column at `lg`, and type steps down through `clamp()` rather than by breakpoint
overrides.

Density is comfortable, not compact — this is a boutique property, and cramped controls
read as municipal.

## Elevation & Depth

Hybrid, weighted toward tonal layering. Surfaces are distinguished first by paper tone
(`cream` → `cream-warm` → `canvas-deep`), and only then by shadow. Shadows are warm and
green-shifted (they derive from `oklch(15% 0.05 160)`), long, and very soft — daylight
through a window, not a UI drop shadow. A film-grain overlay runs site-wide to break
digital flatness.

### Shadow Vocabulary
- **Boutique card** (`0 18px 40px -22px oklch(15% 0.05 160 / 0.25)`): Resting cards and panels.
- **Capsule** (`0 24px 60px -20px oklch(15% 0.05 160 / 0.35), 0 8px 24px -12px oklch(15% 0.05 160 / 0.2)`): Lifted, floating elements.
- **Boutique modal** (`0 40px 80px -30px oklch(10% 0.05 160 / 0.5)`): Overlays.
- **Night card / float**: the `theme-night` equivalents, deeper and cooler.

### Named Rules

**The Warm Shadow Rule.** No shadow in this system is neutral black. Every one carries the
evergreen hue; a `rgba(0,0,0,…)` shadow on cream turns the paper gray.

**The No-Glow Rule.** Shadows have offset and blur. A zero-offset colored halo is
decoration and does not belong here.

## Shapes

Two radii carry the system, and the contrast between them is deliberate: **pill**
(`9999px`) for anything that acts — buttons, chips, tabs, filter pills — and **small**
(`4px`) for anything that holds — fields, panels, image frames. Cards sit between at
`12px`–`1rem`.

Borders are hairlines: 1px, ink or gold at low alpha. Thick colored side-borders are not
part of the language. Photography is framed with a hairline and generous inner margin, the
way a print is matted.

## Components

### Buttons
- **Shape:** Fully rounded pill (`9999px`)
- **Primary:** Deep evergreen ground, cream label in Oswald uppercase at wide tracking, `0.95rem 1.75rem`
- **Hover / Focus:** Lifts to evergreen with a capsule shadow; transform stays ≤2px. Focus shows a visible gold ring offset from the shape.
- **Ghost:** Hairline ink border on transparent, ink label; fills to `cream-warm` on hover.

### Cards / Containers
- **Corner Style:** `12px`–`1rem`
- **Background:** `cream-warm` on a `cream` page
- **Shadow Strategy:** Boutique card at rest; capsule only when genuinely floating
- **Border:** Optional 1px ink at ~8% alpha
- **Internal Padding:** `lg` (2rem), tightening to `md` below `sm`

### Inputs / Fields
- **Style:** `cream-warm` ground, 1px hairline border, `4px` radius. Labels float from placeholder position in Oswald uppercase.
- **Focus:** Border shifts to evergreen and a gold rule draws in beneath the field. No glow.
- **Error:** Ember hairline border plus an ember message beneath; the field keeps its shape.

### Navigation
- Oswald uppercase at `0.2em`, ink on cream. Transparent over full-bleed photography, swapping to a solid cream skin on scroll. Active items carry a gold underline, never a filled pill.

## Do's and Don'ts

### Do:
- **Do** prefix auth-surface classes `fha-`, not the project-wide `fh-`. The generic prefix is shared with `06-hero.css`, whose `.fh-field` (padding + a `+ .fh-field` divider border) and `.fh-rule` (a 46px gold hairline that animates itself visible) silently leaked into the sign-in form and broke its field geometry. **The Prefix Rule: a surface-specific stylesheet gets a surface-specific prefix.**
- **Do** keep `.fha-field` hugging its input exactly — `line-height: 0` on the field, `display: block` on the input. An inline-block input adds ~12px of baseline descender inside the field, which detaches the absolutely-positioned brass rule and the reveal button from the input's real edges.
- **Do** set `align-items: start` on any field row. Grid's default `stretch` makes a bare field grow to match a sibling carrying helper text, dragging its bottom-anchored rule far below the input.
- **Do** put every small uppercase string in Oswald at ≥0.2em tracking.
- **Do** tint shadows with the evergreen hue (`oklch(15% 0.05 160)`), never neutral black.
- **Do** separate surfaces by paper tone first (`cream` → `cream-warm` → `canvas-deep`), shadow second.
- **Do** ship Playfair at weight 400 and let size create the hierarchy.
- **Do** respect the strict import order of `resources/css/public/*.css`; same-specificity rules depend on it.
- **Do** build CSS with `npm run build`, which warms the Blade view cache Tailwind scans.

### Don't:
- **Don't** use `#ffffff` as a surface. Cream is the paper.
- **Don't** introduce a second accent alongside gold.
- **Don't** use gradient text, glass, backdrop blur as decoration, or a colored halo shadow.
- **Don't** set body or secondary text in neutral gray on cream; tint it toward ink or evergreen.
- **Don't** use a monospace face on public surfaces, and never as a "technical" costume.
- **Don't** display any metric, certification, rating or price that is not confirmed real — see PRODUCT.md's *Evidence on Hand*. This world has a real institution behind it and no invented proof.
