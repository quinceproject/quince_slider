# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the versioning follows
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-11-10

### Fixed

- The countdown on a slide now counts. A slide with an end date shows the days, hours
  and minutes left in the corner of the slide (above the content on narrow screens)
  and hides it once the date has passed. In 0.1.0 the date could be set, but the
  slide showed an empty box. A date already in the past is not put on the page.

## [0.1.0] - 2026-11-10

First public release.

### Added

- A band of slides placed in a column of a Quince Engine home page section, through
  the `displayQuinceHomeSlider` hook.
- Four kinds of background: a colour, a gradient, a photo with an optional faded
  edge, or a muted YouTube or Vimeo video with a still image shown while it loads.
- Slide content: a line above the heading, the heading, a sentence, up to four short
  claims, a price with the price before and up to two buttons.
- Slides about a product: the name, the sentence, the price, the photo and the button
  address come from the catalogue, unless the shop types its own.
- Layout across the slide or in a panel, aligned left or centred, with a photo beside
  the text.
- Band settings: height, full or page width, automatic rotation with seconds per slide,
  arrows and dots.
- Back office screen with slides reordered by dragging.
- Declared dependency on Quince Engine.
