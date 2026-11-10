# Quince Slider

**A band of slides for the home page of a PrestaShop 9.2 shop on the Quince theme.**

Quince Slider adds a band of slides to the home page: a colour, a gradient, a photo
or a video in the background, and a heading, a sentence, short claims, a price and
buttons on top. A slide can also promote a product, and then its
name, price and photo come straight from the catalogue.

> Quince Slider is a free, open-source module (OSL-3.0). There is nothing to buy.
> If it saves you time, you can support development, see
> [Supporting the project](#supporting-the-project).

---

## Features

- **Four kinds of background.** A colour or a gradient taken from the shop palette
  (a step from 1 to 12) or given as a hex code, a photo with an optional faded edge
  and a veil for photos that fight with the text, or a YouTube or Vimeo video. The
  shop pastes an ordinary video link; the module turns it into a muted background
  video, with a still image shown while it loads.
- **Content per slide.** A line above the heading, the heading, a sentence, up to four
  short claims, a price with the price before and up to two buttons (filled,
  outlined or plain). Each part is optional.
- **Slides about a product.** Give a product id and leave the fields empty: the name,
  the short description, the price with tax in the visitor's currency, the price
  before a reduction, the cover photo and the button address come from the catalogue
  and follow it when it changes. Anything typed by hand stays as typed.
- **Countdown.** Give a slide an end date and it shows the days, hours and minutes
  left, in the corner of the slide on wide screens and above the content on narrow
  ones. It hides itself when the date passes. The counting is done by the Quince
  theme, the same countdown as on product promotions.
- **Layout.** Content across the slide or in a panel at the edge, aligned left or
  centred, with or without a photo beside the text.
- **The band.** Low, medium or tall, across the whole window or within the page
  width, rotating on its own with a set number of seconds per slide, with arrows and
  dots.
- **Placed by the home page, not by the theme.** The module offers the
  `displayQuinceHomeSlider` hook and the Quince Engine home page puts it into a
  column of a section, so the band can be moved or removed without touching the
  module.
- **Hidden until there is something to show.** With no slides the band does not
  appear in the shop.

## Requirements

| Requirement | Version |
|-------------|---------|
| PrestaShop  | 9.2 (tested on 9.2.0) |
| PHP         | 8.4 (tested), 8.1 or newer required |
| Quince Engine | required |
| Quince theme | required for the look and the rotation of slides |

Quince Engine hosts the settings screen and places the band on the home page. The
module declares it as a dependency, so PrestaShop refuses to install Quince Slider
without it. The look of the band and the rotation of slides come from the Quince
theme; the module provides the content only.

## Installation

The release ZIP already contains everything the module needs. Composer and the
command line are not required.

**From the back office**

1. Download the latest **`quince_slider.zip`** from the
   [**Releases**](https://github.com/quinceproject/quince_slider/releases) page.
2. In the back office go to **Modules → Module Manager → Upload a module** and
   choose the ZIP. PrestaShop installs it.

**By copying the files to the server**

1. Unpack the release ZIP. It contains a `quince_slider` folder.
2. Upload that folder to the `modules/` directory of the shop, for example by FTP.
3. In **Modules → Module Manager** find Quince Slider and click **Install**.

**From Quince Engine** *(coming soon)*

The module list in Quince Engine will install the module straight from its release,
without downloading anything by hand.

**With Composer** *(coming soon)*

Installing with `composer require quinceproject/slider` will work once the package is
published on Packagist.

> ⚠️ **Do not** use the green **Code → Download ZIP** button on this page. That
> archive contains the source only and is **missing the `vendor/` folder**, so the
> module will not run. Always install from a
> [**Release**](https://github.com/quinceproject/quince_slider/releases).

Uninstalling the module removes the slides and their settings.

## Configuration

Open **Quince project → Slider** in the back office menu, or click **Configure** next
to the module in the Module Manager.

1. **Add a slide**, choose where its content comes from (own text or a product) and
   its background.
2. Fill in the content and the layout. Photos and still images are given as image
   addresses.
3. Drag slides by their handle to set the order.
4. Under **The band**, set the height, the width and the rotation.
5. In the Quince Engine home page screen, put the slider into a column of a section.

## Architecture

- **`Slides`** holds the slides as JSON in one configuration key and the band
  settings under the `QUINCE_SLIDER_` prefix, written through the Quince Engine
  configuration store.
- **`hookDisplayQuinceHomeSlider`** turns the slides into the visitor's language,
  fills product slides from the catalogue, builds the video embed address and renders
  `views/templates/hook/slider.tpl`.
- **Symfony controller and Twig** for the settings screen, inside the Quince Engine
  shell, with ordering by dragging.

## Development / building from source

This repository tracks **source only**. The `vendor/` directory is git-ignored, so a
`git clone` is **not** directly installable. **Start with `composer install`**: the
development environment mounts module directories straight from disk, and without
that step a fresh clone gives a broken back office.

```bash
git clone https://github.com/quinceproject/quince_slider.git
cd quince_slider
composer install
```

The folder must be called `quince_slider`, the same as the module. A clone of this
repository already has that name.

To build an installable archive, zip the `quince_slider/` folder **with its `vendor/`
directory included**, leaving out `.git/` and `.gitignore`.

## License

Code: [Open Software License 3.0 (OSL-3.0)](LICENSE). It is a reciprocal licence:
whoever modifies and distributes this code has to publish the changes under the same
licence and keep the original copyright and author notices.

**The name "Quince" is not covered by the code licence.** A fork may use the code; it
may not use the name.

© 2026 Marcin Gajewski

## Supporting the project

Quince modules are free downloads and there is nothing to buy. If the module is useful
to you, you can support its development by buying me a coffee:

**[buymeacoffee.com/marcingajewski](https://buymeacoffee.com/marcingajewski)**
