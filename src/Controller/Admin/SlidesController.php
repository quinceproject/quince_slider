<?php
/**
 * Quince
 *
 * @author    Quince Project <quinceproject@proton.me>
 * @copyright Since 2026 Marcin Gajewski
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Quince\Slider\Controller\Admin;

use Quince\Engine\Controller\Admin\QuinceController;
use Quince\Slider\Settings\Slides;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Slajder.
 *
 * Dziedziczy po klasie z silnika, więc ekran dostaje ten sam sidebar, pasek
 * zapisu i wygląd co reszta panelu Quince.
 *
 * Lista slajdów stoi na tym samym komponencie co bloki odnośników i sekcje
 * strony głównej: wiersz z chwytem, przełącznikiem i rozwinięciem.
 */
class SlidesController extends QuinceController
{
    public function __construct(private readonly Slides $slides)
    {
    }

    public function indexAction(): Response
    {
        return $this->renderPanel('@Modules/quince_slider/views/templates/admin/slides.html.twig', [
            'layoutTitle' => 'Slider',
            'layoutTitleDomain' => 'Modules.Quinceslider.Admin',
            'quinceRoute' => 'quince_slider',

            'slides' => $this->slides->all(),
            'settings' => $this->slides->settings(),

            // Słowniki do kontrolek. Ekran nie zna wartości na pamięć: bierze
            // je stąd, więc dołożenie rodzaju tła pokazuje się w panelu bez
            // ruszania szablonu ani skryptu.
            'sources' => Slides::SOURCES,
            'backgrounds' => Slides::BACKGROUNDS,
            'fades' => Slides::FADES,
            'layouts' => Slides::LAYOUTS,
            'alignments' => Slides::ALIGNMENTS,
            'buttonStyles' => Slides::BUTTON_STYLES,
            'heights' => Slides::HEIGHTS,
            'widths' => Slides::WIDTHS,
            'maxButtons' => Slides::MAX_BUTTONS,
            'maxSpecs' => Slides::MAX_SPECS,

            'languages' => $this->languages(),
        ]);
    }

    public function saveAction(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('quince_slider_save', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', $this->trans('Invalid security token, please try again.', 'Modules.Quinceslider.Admin'));

            return $this->redirectToRoute('quince_slider');
        }

        $this->slides->save($request->request->all());
        $this->addFlash('success', $this->trans('Settings saved.', 'Modules.Quinceslider.Admin'));

        return $this->redirectToRoute('quince_slider');
    }
}
